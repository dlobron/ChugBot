<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';
    include_once 'constants.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    // Create an ID to name mapping for tables to preserve after archiving.
    // When we "preserve" a table, we copy it from the archived database to
    // the new one.
    $preserveTableId2Name = array();
    $preserveTableId2Name[1] = "blocks";
    $preserveTableId2Name[2] = "bunks";
    $preserveTableId2Name[3] = "chugim";
    $preserveTableId2Name[4] = "edot";
    $preserveTableId2Name[5] = "groups";
    $preserveTableId2Name[6] = "sessions";
    
    function restoreCurrentDb(&$dbErr, $mysql, $mysqldump, $thisYearArchive) {
        // 1. Dump the archive database.  Important: the database user must
        // have adequate permission, including LOCK permission, to do these
        // operations.
        error_log("Writing out archive DB contents");
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
        // 2. Drop/create the current DB.
        error_log("Creating current DB");
        $db = new DbConn();
        $result = $db->runQueryDirectly("DROP DATABASE IF EXISTS " . MYSQL_DB, $dbErr);
        if ($result === NULL) {
            return;
        }
        $result = $db->runQueryDirectly("CREATE DATABASE " . MYSQL_DB . " COLLATE utf8_unicode_ci", $dbErr);
        if ($result === NULL) {
            return;
        }
        // 3. Import dumped data into current DB.
        error_log("Importing data to current DB");
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
        // 4. Drop the backup DB.
        $db = new DbConn();
        $db->runQueryDirectly("DROP DATABASE IF EXISTS $thisYearArchive", $dbErr);
    }
    
    function archiveCurrentDb(&$dbErr, $preserveTables, $mysql, $mysqldump,
                              $preserveTableId2Name, $thisYearArchive) {
        // 1. Dump the current database.  Important: the database user must
        // have adequate permission, including LOCK permission, to do these
        // operations.
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
        // 2. Create the archive database.
        error_log("Creating backup DB $thisYearArchive");
        $db = new DbConn();
        $result = $db->runQueryDirectly("DROP DATABASE IF EXISTS $thisYearArchive", $dbErr);
        if ($result === NULL) {
            return;
        }
        $result = $db->runQueryDirectly("CREATE DATABASE $thisYearArchive COLLATE utf8_unicode_ci", $dbErr);
        if ($result === NULL) {
            return;
        }
        // 3. Import dumped data to the archive DB.
        error_log("Importing data to $thisYearArchive");
        $cmd = $mysql . " --user " . MYSQL_USER . " --password=" . MYSQL_PASSWD . " $thisYearArchive < $dbPath";
        $retVal;
        $result = exec($cmd, $output, $retVal);
        if ($retVal) {
            $dbErr = errorString("Failed to import backup data to $thisYearArchive:\n");
            foreach ($output as $line) {
                $dbErr .= $line;
            }
            return;
        }
        // 4. Empty camper-specific tables in the current db.
        $delTables = array("campers", "matches", "preferences");
        foreach ($delTables as $delTable) {
            error_log("Clearing $delTable in current DB");
            $db = new DbConn();
            $result = $db->runQueryDirectly("DELETE FROM $delTable", $dbErr);
            if ($result === NULL) {
                return;
            }
        }
        // 5. Empty dynamic tables, unless their ID appears in $preserveTables.
        foreach ($preserveTableId2Name as $delTableId => $delTableName) {
            if (array_key_exists("$delTableId", $preserveTables)) {
                error_log("Not clearing dynamic $delTableName per checkbox");
                continue;
            }
            error_log("Clearing dynamic $delTableName in current DB");
            $db = new DbConn();
            $result = $db->runQueryDirectly("DELETE FROM $delTableName", $dbErr);
            if ($result === NULL) {
                return;
            }
        }
    }
    
    // Check to see if the most recently finished summer has already been
    // archived.  An archive DB has the same name as the MYSQL_DB constant,
    // but with the archive year appended after an underscore (e.g.,
    // camprama_chugbot_db_2016 would be an archive of the summer 2016 data).
    // If the current year has not been archived, we will display an "archive"
    // button, with a checklist to choose which tables should be preserved.
    // If the current year is already archived, display a "restore" button, with
    // a warning that the restore operation will clobber the data currently in
    // the database.
    $curYearHasBeenArchived = FALSE;
    $didRestoreDb = FALSE;
    $nextCampYear = yearOfUpcomingSummer();
    $curCampYear = $nextCampYear - 1;
    $restoreText = "<p>Our database shows that you have already archived the database for Kayitz $curCampYear. " .
    "To restore the archived data to the current database, please click \"Restore\" below. This will " .
    "overwrite any data in the current database.  This action cannot be undone.</p>";
    $thisYearArchive = MYSQL_DB . "_" . $curCampYear;
    $dbErr = "";
    $db = new DbConn();
    $result = $db->runQueryDirectly("SHOW DATABASES", $dbErr);
    while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
        if ($row[0] === $thisYearArchive) {
            $curYearHasBeenArchived = TRUE;
            break;
        }
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
        empty($binaryNotFoundError)) {
        $doArchive = test_input($_GET["archive"]);
        $restoreFromArchive = test_input($_GET["restore"]);
        $preserveTables = array();
        populateActiveIds($preserveTables, "pt");
        if ($doArchive) {
            archiveCurrentDb($dbErr, $preserveTables, $mysql, $mysqldump,
                             $preserveTableId2Name, $thisYearArchive);
            if (empty($dbErr)) {
                $curYearHasBeenArchived = TRUE;
                $restoreText = "<p>Archive successful! Click \"Restore\" to undo the archive operation, " .
                "or click \"Staff Home\" at left to exit this page.</p>";
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
    $errText = genFatalErrorReport(array($dbErr, $binaryNotFoundError));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Archive Page</a></h1>

<form id="archiveForm" class="appnitro" method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Archive Page</h2>
</div>
<?php
    if ($didRestoreDb) {
        $homeUrl = homeUrl();
        $formHtml = <<<EOM
        <div class="form_description">
        $restoreText
        </div>
        <ul>
        <li class="buttons">
        <input type="button" value="Done" onclick="window.location.href='$homeUrl'" />
        </li>
        </ul>
EOM;
        echo $formHtml;
    } else if ($curYearHasBeenArchived) {
        $formHtml = <<<EOM
        <div class="form_description">
        $restoreText
        </div>
        <ul>
        <li class="buttons">
        <input class="button_text" type="submit" name="submit" value="Restore" onclick="return confirm(\"Please confirm you wish "
            "to replace the current database with the archived $curCampYear database\")" />
        <input type="hidden" name="restore" value="1" />
        </li>
        </ul>
EOM;
        echo $formHtml;
    } else {
        $tableChooser = new FormItemInstanceChooser("Items to Preserve", FALSE, "pt", 0);
        $tableChooser->setId2Name($preserveTableId2Name);
        $tableChooser->setActiveIdHash($preserveTables);
        $tableChooser->setGuideText("Put a check next to the items you would like to carry over from $curCampYear to $nextCampYear. " .
                                    "Unchecked categories will be cleared from the current database.");
        
        $formHtml = <<<EOM
        <div class="form_description">
        <p>To archive data for summer $curCampYear and prepare the database for $nextCampYear, please click the "Archive"
            button below.</p>
        <p>Before you archive, use the checkboxes to choose those items from $curCampYear that you'd like to keep in the database
            for $nextCampYear (if any).
        </p>
        </div>
        <ul>
        <li>
EOM;
        echo $formHtml;
        echo $tableChooser->renderHtml();
        $formHtml = <<<EOM
        </li>
        <li class="buttons">
        <input class="button_text" type="submit" name="submit" value="Archive" onclick="return confirm(\"Please confirm you wish "
            "to archive your $curCampYear data\")" />
        <input type="hidden" name="archive" value="1" />
        </li>
        </ul>
EOM;
        echo $formHtml;
    }
    
    echo footerText();
?>

</body>
</html>
    