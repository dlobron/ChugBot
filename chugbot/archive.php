<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';
    include_once 'constants.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    // Create an ID to name mapping for tables that we might clear.  We clear
    // these tables from the current DB unless the user instructs us not to.
    // Certain other tables will automatically be cleared due to cascading
    // deletions (e.g., chug_dedup_instances_v2 will be cleared if chugim is
    // cleared).
    $preserveTableId2Name = array();
    $preserveTableId2Name[1] = "blocks";
    $preserveTableId2Name[2] = "bunks";
    $preserveTableId2Name[3] = "chugim + groups";
    $preserveTableId2Name[4] = "edot";
    $preserveTableId2Name[5] = "sessions";
    $preserveTableId2Name[6] = "campers";
    
    function restoreCurrentDb(&$dbErr, $mysql, $mysqldump, $thisYearArchive) {
        // 1. Dump the archive database.
        error_log("Writing out archive DB");
        $dbPath = "/tmp/ardb.sql";
        error_log("Dumping archive database to $dbPath using $mysqldump");
        $cmd = "$mysqldump --user " . MYSQL_USER . " --password=" . MYSQL_PASSWD . " $thisYearArchive > $dbPath";
        $output = array();
        $retVal;
        $result = exec($cmd, $output, $retVal);
        if ($retVal) {
            $dbErr = errorString("Failed to write out archive database:\n");
            foreach ($output as $line) {
                $dbErr .= "$line";
            }
            return;
        }
        // 2. Import the archive DB data into the current DB.
        error_log("Importing archive DB to current DB");
        $cmd = $mysql . " --user " . MYSQL_USER . " --password=" . MYSQL_PASSWD . " " . MYSQL_DB . " < $dbPath";
        $retVal;
        $result = exec($cmd, $output, $retVal);
        if ($retVal) {
            $dbErr = errorString("Failed to restore to " . MYSQL_DB . " :\n");
            foreach ($output as $line) {
                $dbErr .= $line;
            }
            return;
        }
        
    }
    
    function archiveCurrentDb(&$dbErr, $preserveTables, $mysql, $mysqldump,
                              $preserveTableId2Name, $thisYearArchive) {
        // 1. Dump the current database.
        error_log("Writing out current DB contents");
        $dbPath = "/tmp/curdb.sql";
        error_log("Dumping current database to $dbPath using $mysqldump");
        $cmd = "$mysqldump --user " . MYSQL_USER . " --password=" . MYSQL_PASSWD . " " . MYSQL_DB . " > $dbPath";
        $output = array();
        $retVal;
        $result = exec($cmd, $output, $retVal);
        if ($retVal) {
            $dbErr = errorString("Failed to back up current database:\n");
            foreach ($output as $line) {
                $dbErr .= "$line";
            }
            return;
        }
        // 2. Import dumped data to the archive DB.
        error_log("Importing data to $thisYearArchive");
        $cmd = "$mysql --user " . MYSQL_USER . " --password=" . MYSQL_PASSWD . " $thisYearArchive < $dbPath";
        $retVal;
        $result = exec($cmd, $output, $retVal);
        if ($retVal) {
            $dbErr = errorString("Failed to import backup data to $thisYearArchive:\n");
            foreach ($output as $line) {
                $dbErr .= $line;
            }
            return;
        }
        // 3. Clear matches and prefs from the current DB, since these always switch over from year
        // to year.
        $delTables = array("matches", "preferences");
        foreach ($delTables as $delTable) {
            error_log("Clearing $delTable in current DB");
            $db = new DbConn();
            $result = $db->runQueryDirectly("DELETE FROM $delTable", $dbErr);
            if ($result === NULL) {
                return;
            }
        }
        // 4. Empty dynamic tables from the current DB, unless their ID appears in $preserveTables.
        foreach ($preserveTableId2Name as $delTableId => $delTableName) {
            if (array_key_exists("$delTableId", $preserveTables)) {
                error_log("Not clearing dynamic $delTableName per checkbox");
                continue;
            }
            // Some table names contain multiple tables separated by " + ".
            $tables = explode(" + ", $delTableName);
            foreach ($tables as $table) {
                error_log("Clearing dynamic $table in current DB");
                $db = new DbConn();
                $result = $db->runQueryDirectly("DELETE FROM $table", $dbErr);
                if ($result === NULL) {
                    return;
                }
            }
        }
    }
    
    // Check to see if the most recently finished summer has already been
    // archived.
    $curYearHasBeenArchived = FALSE;
    $didRestoreDb = FALSE;
    $didArchiveDb = FALSE;
    $homeUrl = homeUrl();
    $nextCampYear = yearOfUpcomingSummer();
    $curCampYear = $nextCampYear - 1;
    $restoreText = "<p>You have previously archived the database for Kayitz $curCampYear. " .
    "To restore the $curCampYear data to the current database, please click \"Restore\" below. This will " .
    "overwrite any data in the current database.  This action cannot be undone.</p>";
    $thisYearArchive = MYSQL_DB . $curCampYear;
    $dbErr = "";
    $permissionsError = "";
    $archiveYears = getArchiveYears($dbErr);
    foreach ($archiveYears as $archiveYear) {
        if ($archiveYear == $curCampYear) {
            $curYearHasBeenArchived = TRUE;
            break;
        }
    }
    $requiredPermissions = array("DELETE" => 0, "LOCK TABLES" => 0);
    $db = new DbConn();
    $result = $db->runQueryDirectly("SHOW GRANTS FOR '" . MYSQL_USER . "'@'" . MYSQL_HOST . "'", $dbErr);
    while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
        foreach ($requiredPermissions as $rp => $count) {
            if (strpos($row[0], $rp) !== FALSE) {
                $requiredPermissions[$rp]++;
            }
        }
    }
    $missingPerms = "";
    foreach ($requiredPermissions as $rp => $count) {
        if ($count === 0) {
            $missingPerms .= empty($missingPerms) ? $rp : ", $rp";
        }
    }
    if (! empty($missingPerms)) {
        $permissionsError = "The archive operation requires the following missing permissions: $missingPerms. " .
        "Please check with your site administrator, who should be able to grant these.";
    }
    $haveDb = FALSE;
    $noBackupDbError = "";
    $db = new DbConn();
    $result = $db->runQueryDirectly("SHOW DATABASES", $dbErr);
    while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
        if ($row[0] == $thisYearArchive) {
            $haveDb = TRUE;
            break;
        }
    }
    if (! $haveDb) {
        $noBackupDbError = "In order to archive, you must create a database called $thisYearArchive using cPanel or a similar " .
        "administrative tool. Please ask your site administrator to create this table, then try again.";
    }
    
    $binaryNotFoundError = "";
    $mysqldump = MYSQL_PATH . "/mysqldump";
    $mysql = MYSQL_PATH . "/mysql";
    if (! file_exists($mysqldump)) {
        $binaryNotFoundError = "DB backup utility not found at $mysqldump: check with administrator";
    }
    if (! file_exists($mysql)) {
        $binaryNotFoundError = "DB utility not found at $mysql: check with administrator";
    }

    // Check the GET data to find out what action to take.
    if ($_SERVER["REQUEST_METHOD"] == "GET" &&
        empty($dbErr) &&
        empty($binaryNotFoundError) &&
        empty($noBackupDbError)) {
        $doArchive = test_input($_GET["archive"]);
        $restoreFromArchive = test_input($_GET["restore"]);
        $preserveTables = array();
        populateActiveIds($preserveTables, "pt");
        if ($doArchive) {
            archiveCurrentDb($dbErr, $preserveTables, $mysql, $mysqldump,
                             $preserveTableId2Name, $thisYearArchive);
            if (empty($dbErr)) {
                $didArchiveDb = TRUE;
                $curYearHasBeenArchived = TRUE;
                $restoreText = "<p>Archive successful! Click \"Restore\" to undo the archive operation, " .
                "or click \"Done\" to exit.</p>";
            }
        } else if ($restoreFromArchive) {
            restoreCurrentDb($dbErr, $mysql, $mysqldump, $thisYearArchive);
            if (empty($dbErr)) {
                $restoreText = "<p>Restore succeeded!</p>";
                $didRestoreDb = TRUE;
            }
        }
    }
    
?>

<?php
    echo headerText("Archive Data");
    $errText = genFatalErrorReport(array($dbErr, $binaryNotFoundError, $permissionsError, $noBackupDbError));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
?>

<div class="form_container">

<h1><a>Archive Page</a></h1>

<?php
    if ($didRestoreDb) {
        $formHtml = <<<EOM
        <div class="archive_form_container">
        <div class="form_description">
        $restoreText
        </div>
        <ul>
        <li class="buttons">
        <button class="btn btn-success" type="button" data-toggle="tooltip" title="Return to staff home page" onclick="window.location.href='$homeUrl'">Done</button>
        </li>
        </ul>
        </div>
EOM;
        echo $formHtml;
    }
    
    $formAction = htmlspecialchars($_SERVER["PHP_SELF"]);
    $tableChooser = new FormItemInstanceChooser("Items to Preserve", FALSE, "pt", 0);
    $tableChooser->setId2Name($preserveTableId2Name);
    $tableChooser->setActiveIdHash($preserveTables);
    $tableChooser->setGuideText("Put a check next to the items you would like to carry over from $curCampYear to $nextCampYear. " .
                                "Unchecked categories will be cleared from the current database.");
    
    $formHtml = <<<EOM
    <form id="archiveForm1" class="appnitro" method="GET" action="$formAction">
    <div class="form_description">
    <h2>Archive Current Data</h2>
    </div>
    <div class="archive_form_container">
    <div class="form_description">
    <p>To archive your current data and prepare the database for next year, please click the "Archive"
        button below.</p>
    <p>Before you archive, use the checkboxes to choose those items from the current database that you would like to keep for next year (if any).</p>
    </div>
    <ul>
    <li>
EOM;
    echo $formHtml;
    echo $tableChooser->renderHtml();
    
    $formHtml = <<<EOM
    </li>
    <li class="buttons">
    <input class="btn btn-primary" type="submit" name="submit" value="Archive" data-toggle="tooltip" title="Archive your $curCampYear data" />
    <button class="btn btn-link" type="button" data-toggle="tooltip" title="Exit with no changes" onclick="window.location.href='$homeUrl'">Cancel</button>
    <input type="hidden" name="archive" value="1" />
    </li>
    </ul>
    </form>
    </div>
EOM;
    echo $formHtml;
    
    if ($curYearHasBeenArchived &&
        $didRestoreDb == FALSE) {
        if ($didArchiveDb) {
            $noActionButton = "<button class=\"btn btn-success\" type=\"button\" data-toggle=\"tooltip\" title=\"Done with archiving\" onclick=\"window.location.href='$homeUrl'\">Done</button>";
        } else {
            $noActionButton = "<button class=\"btn btn-link\" type=\"button\" data-toggle=\"tooltip\" title=\"Exit with no changes\" onclick=\"window.location.href='$homeUrl'\">Cancel</button>";
        }
        $formHtml = <<<EOM
        <form id="archiveForm2" class="appnitro" method="GET" action="$formAction">
        <div class="archive_form_container">
        <div class="form_description">
        $restoreText
        </div>
        <ul>
        <li class="buttons">
        <button class="btn btn-danger" type="submit" name="submit" data-toggle="tooltip" title="Replace the current database with the archived $curCampYear database">Restore</button>
        $noActionButton
        <input type="hidden" name="restore" value="1" />
        </li>
        </ul>
        </form>
        </div>
EOM;
        echo $formHtml;
    }
    
    echo footerText();
?>

</body>
</html>
    
