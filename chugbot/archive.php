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
$preserveTableId2Name[3] = "chugim + chug_groups";
$preserveTableId2Name[4] = "edot";
$preserveTableId2Name[5] = "sessions";
$preserveTableId2Name[6] = "campers";

function restoreCurrentDb(&$dbErr, $thisYearArchive)
{
    $nextCampYear = yearOfUpcomingSummer();
    $curCampYear = $nextCampYear - 1;
    $db = new DbConn();
    $archive_db = new DbConn($curCampYear);
    $result = $archive_db->runQueryDirectly("SHOW TABLES", $dbErr);
    $i = 0;
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $dbErr = "";
        $table = $row[$i];
        $r2 = $db->runQueryDirectly("SELECT * FROM $table", $dbErr);
        if ($dbErr) {
            $dbErr = "Restore failed to select from table: $dbErr";
            return;
        }
        // If the table has no rows, restore it.
        if ($r2->num_rows > 0) {
            continue;
        }
        $db->runQueryDirectly("INSERT INTO $table SELECT * FROM $thisYearArchive " . "." . "$table", $dbErr);
        if ($dbErr) {
            $dbErr = "Restore failed to populate table: $dbErr";
            return;
        }
    }
    $db->runQueryDirectly("SET FOREIGN_KEY_CHECKS = 1", $dbErr);
    return;
}

function archiveCurrentDb(&$dbErr, $preserveTables, $preserveTableId2Name) {
    $nextCampYear = yearOfUpcomingSummer();
    $curCampYear = $nextCampYear - 1;
    $db = new DbConn();
    $archive_db = new DbConn($curCampYear);
    $result = $db->runQueryDirectly("SHOW TABLES", $dbErr);
    $i = 0;
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $table = $row[$i];
        $archive_db->runQueryDirectly("DROP TABLE IF EXISTS $table;", $dbErr);
        if ($dbErr) {
            $dbErr = "Archive failed to drop table: $dbErr";
            return;
        }
        $archive_db->runQueryDirectly("CREATE TABLE $table AS SELECT * FROM " . MYSQL_DB . ".$table;", $dbErr);
        if ($dbErr) {
            $dbErr = "Archive failed to create table: $dbErr";
            return;
        }
    }
    // Clear matches and prefs from the current DB, since these always switch over from year
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
    // Empty dynamic tables from the current DB, unless their ID appears in $preserveTables.
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
$mysqldump = trim(`export PATH="\$PATH:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"; which mysqldump`);
$mysql = trim(`export PATH="\$PATH:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"; which mysql`);
if (!file_exists($mysqldump)) {
    $binaryNotFoundError = "DB backup utility mysqldump not found in PATH: check with administrator";
}
if (!file_exists("$mysql")) {
    $binaryNotFoundError = "DB utility mysql not found in PATH (got $mysql): check with administrator";
}

// Check the GET data to find out what action to take.
if ($_SERVER["REQUEST_METHOD"] == "GET" &&
    empty($dbErr) &&
    empty($binaryNotFoundError) &&
    empty($noBackupDbError)) {
    $doArchive = false;
    if (array_key_exists("archive", $_GET)) {
        $doArchive = test_input($_GET["archive"]);
    }
    $restoreFromArchive = false;
    if (array_key_exists("restore", $_GET)) {
        $restoreFromArchive = test_input($_GET["restore"]);
    }
    $preserveTables = array();
    populateActiveIds($preserveTables, "pt");
    if ($doArchive) {
        archiveCurrentDb($dbErr, $preserveTables, $preserveTableId2Name);
        if (empty($dbErr)) {
            $didArchiveDb = true;
            $curYearHasBeenArchived = true;
            $restoreText = "<p>Archive successful! Click \"Restore\" to undo the archive operation, " .
                "or click \"Done\" to exit.</p>";
        }
    } else if ($restoreFromArchive) {
        restoreCurrentDb($dbErr, $thisYearArchive);
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

<div class="well well-white container">

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
EOM;
echo $formHtml;
echo $tableChooser->renderHtml();

$formHtml = <<<EOM
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

