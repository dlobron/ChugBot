<?php
session_start();
include_once 'dbConn.php';
include_once 'functions.php';
include_once 'constants.php';
include_once 'formItem.php';
bounceToLogin();
checkLogout();

// Create an ID to name mapping for tables that we might clear.  We clear
// these tables from the current DB unless the user instructs us not to.
// Certain other tables will automatically be cleared due to cascading
// deletions (e.g., chug_dedup_instances_v2 will be cleared if chugim is
// cleared).
$preserveTableId2Name = array();
$preserveTableId2Name[1] = "blocks";
$preserveTableId2Name[2] = "bunks";
$preserveTableId2Name[3] = "chug_groups";
$preserveTableId2Name[4] = "chugim";
$preserveTableId2Name[5] = "edot";
$preserveTableId2Name[6] = "sessions";
$preserveTableId2Name[7] = "campers";

function generateToolCommand($bin_path, $database, $dir, $dbPath)
{
    $cmd = "$bin_path --host " . MYSQL_HOST . " --user " . MYSQL_USER;
    $cmd .= " --password='" . MYSQL_PASSWD . "' " . $database;
    if ($dir == "out") {
        $cmd .= " > $dbPath";
    } else if ($dir == "in") {
        $cmd .= " < $dbPath";
    } else {
        error_log("Invalid tool direction $dir");
        return NULL;
    }
    error_log("Prepared tool command \'$cmd\'");
    return $cmd;
}

function restoreCurrentDb(&$dbErr, $mysql, $mysqldump, $thisYearArchive)
{
    // 1. Dump the archive database.
    error_log("Writing out archive DB");
    $dbPath = "/tmp/ardb.sql";
    $cmd = generateToolCommand($mysqldump, $thisYearArchive, "out", $dbPath);
    if (is_null($cmd)) {
        $dbErr = errorString("Failed to generate database dump command");
        return;
    }
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
    $cmd = generateToolCommand($mysql, MYSQL_DB, "in", $dbPath);
    if (is_null($cmd)) {
        $dbErr = errorString("Failed to generate database import command");
        return;
    }
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
    $cmd = generateToolCommand($mysqldump, MYSQL_DB, "out", $dbPath);
    if (is_null($cmd)) {
        $dbErr = errorString("Failed to generate database dump command");
        return;
    }
    $output = array();
    $retVal = 0;
    $result = exec($cmd, $output, $retVal);
    if ($retVal != 0) {
        $dbErr = errorString("Failed to back up current database (return code from $mysqldump = $retVal):\n");
        foreach ($output as $line) {
            $dbErr .= "$line";
        }
        return;
    }
    // 2. Import dumped data to the archive DB.
    error_log("Importing data to $thisYearArchive");
    $cmd = generateToolCommand($mysql, $thisYearArchive, "in", $dbPath);
    if (is_null($cmd)) {
        $dbErr = errorString("Failed to generate database import command");
        return;
    }
    $retVal = 0;
    $result = exec($cmd, $output, $retVal);
    if ($retVal != 0) {
        $dbErr = errorString("Failed to import backup data to $thisYearArchive (return code from $mysql = $retVal):\n");
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
        if ($result === null) {
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
            if ($result === null) {
                return;
            }
        }
    }
}

// Check to see if the most recently finished summer has already been
// archived.
$curYearHasBeenArchived = false;
$didRestoreDb = false;
$didArchiveDb = false;
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
        $curYearHasBeenArchived = true;
        break;
    }
}
$requiredPermissions = array("DELETE" => 0, "LOCK TABLES" => 0);
$db = new DbConn();
# Older MySQL versions require the .@.host syntax.  Switch the commented lines
# if using an older version.
# $result = $db->runQueryDirectly("SHOW GRANTS FOR '" . MYSQL_USER . "'@'" . MYSQL_HOST . "'", $dbErr);
$result = $db->runQueryDirectly("SHOW GRANTS FOR '" . MYSQL_USER . "'", $dbErr);
if ($result === false) {
    $permissionsError = "Failed to show database grants: $dbErr";
    error_log($permissionsError);
} else {
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        foreach ($requiredPermissions as $rp => $count) {
            if (strpos($row[0], $rp) !== false) {
                $requiredPermissions[$rp]++;
            } else if (strpos($row[0], "GRANT ALL PRIVILEGES") !== false) {
                $requiredPermissions[$rp]++;
            }
        }
    }
}
$missingPerms = "";
foreach ($requiredPermissions as $rp => $count) {
    if ($count === 0) {
        $missingPerms .= empty($missingPerms) ? $rp : ", $rp";
    }
}
if (!empty($missingPerms)) {
    $permissionsError = "The archive operation requires the following missing permissions: $missingPerms. " .
        "Please check with your site administrator, who should be able to grant these.";
}
$haveDb = false;
$noBackupDbError = "";
$db = new DbConn();
$result = $db->runQueryDirectly("SHOW DATABASES", $dbErr);
while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
    if ($row[0] == $thisYearArchive) {
        $haveDb = true;
        break;
    }
}
if (!$haveDb) {
    // If the archive DB does not exist, try to create it.
    $db = new DbConn();
    $result = $db->runQueryDirectly("CREATE DATABASE IF NOT EXISTS $thisYearArchive COLLATE utf8_unicode_ci", $dbErr);
    if ($result === true) {
        // Created archive DB OK.
        error_log("Created new archive DB $thisYearArchive OK");
    } else {
        $noBackupDbError = "In order to archive, you must create a database called $thisYearArchive using cPanel or a similar " .
            "administrative tool. Please ask your site administrator to create this table, then try again.  This program does not have " .
            "sufficient permission to create the database.";
    }
}

$binaryNotFoundError = "";
$mysqldump = MYSQL_PATH . "/mysqldump";
$mysql = MYSQL_PATH . "/mysql";
if (!file_exists($mysqldump)) {
    $binaryNotFoundError = "DB backup utility not found at $mysqldump: check with administrator<br>";
}
if (!file_exists($mysql)) {
    $binaryNotFoundError .= "DB utility not found at $mysql: check with administrator<br>";
}

// Check the GET data to find out what action to take.
if ($_SERVER["REQUEST_METHOD"] == "GET" &&
    empty($dbErr) &&
    empty($binaryNotFoundError) &&
    empty($noBackupDbError)) {
    $doArchive = test_get_input("archive");
    $restoreFromArchive = test_get_input("restore");
    $preserveTables = array();
    populateActiveIds($preserveTables, "pt");
    if ($doArchive) {
        archiveCurrentDb($dbErr, $preserveTables, $mysql, $mysqldump,
            $preserveTableId2Name, $thisYearArchive);
        if (empty($dbErr)) {
            $didArchiveDb = true;
            $curYearHasBeenArchived = true;
            $restoreText = "<p>Archive successful! Click \"Restore\" to undo the archive operation, " .
                "or click \"Done\" to exit.</p>";
        }
    } else if ($restoreFromArchive) {
        restoreCurrentDb($dbErr, $mysql, $mysqldump, $thisYearArchive);
        if (empty($dbErr)) {
            $restoreText = "<p>Restore succeeded!</p>";
            $didRestoreDb = true;
        }
    }
}

?>

<?php
echo headerText("Archive Data");
$errText = genFatalErrorReport(array($dbErr, $binaryNotFoundError, $permissionsError, $noBackupDbError));
if (!is_null($errText)) {
    echo $errText;
    exit();
}
?>

<div class="card card-body mt-3 container">

<h1><a>Archive Page</a></h1>

<?php
if ($didRestoreDb) {
    $formHtml = <<<EOM
        <div>
        <div class="page-header">
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
$tableChooser = new FormItemInstanceChooser("Items to Preserve", false, "pt", 0);
$tableChooser->setId2Name($preserveTableId2Name);
$tableChooser->setActiveIdHash($preserveTables);
$tableChooser->setGuideText("Put a check next to the items you would like to carry over from $curCampYear to $nextCampYear. " .
    "Unchecked categories will be cleared from the current database.");

$formHtml = <<<EOM
    <form id="archiveForm1" method="GET" action="$formAction">
    <div>
    <div class="page-header">
    <h2>Archive Current Data</h2>
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
    $didRestoreDb == false) {
    if ($didArchiveDb) {
        $noActionButton = "<button class=\"btn btn-success\" type=\"button\" data-toggle=\"tooltip\" title=\"Done with archiving\" onclick=\"window.location.href='$homeUrl'\">Done</button>";
    } else {
        $noActionButton = "<button class=\"btn btn-link\" type=\"button\" data-toggle=\"tooltip\" title=\"Exit with no changes\" onclick=\"window.location.href='$homeUrl'\">Cancel</button>";
    }
    $formHtml = <<<EOM
        <form id="archiveForm2" method="GET" action="$formAction">
        <div>
        <div class="page-header">
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

