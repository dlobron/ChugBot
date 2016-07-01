<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';
    include_once 'constants.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    // Create an ID to name mapping for tables to preserve.  This is only
    // visible within this file.
    $preserveTableId2Name = array();
    $preserveTableId2Name[0] = "blocks";
    $preserveTableId2Name[1] = "bunks";
    $preserveTableId2Name[2] = "campers";
    $preserveTableId2Name[3] = "chugim";
    $preserveTableId2Name[4] = "edot";
    $preserveTableId2Name[5] = "groups";
    $preserveTableId2Name[5] = "matches";
    $preserveTableId2Name[5] = "preferences";
    $preserveTableId2Name[5] = "sessions";

    // Check to see if the most recently finished summer has already been
    // archived.  An archive DB has the same name as the MYSQL_DB constant,
    // but with the archive year appended after an underscore (e.g.,
    // camprama_chugbot_db_2016 would be an archive of the summer 2016 data).
    // If the current year has not been archived, we will display an "archive"
    // button, with a checklist to choose which tables should be preserved.
    // If the current year is already archived, display a "restore" button, with
    // a warning that the restore operation will clobber the data currently in
    // the database.
    $curYearHasBeenArchived = FALSE:
    $nextCampYear = yearOfUpcomingSummer();
    $curCampYear = $nextCampYear - 1;
    $thisYearArchive = MYSQL_DB . "_" . $curCampYear;
    $dbErr = "";
    $db = new DbConn();
    $result = $db->runQueryDirectly("SHOW DATABASES", $dbErr);
    while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
        if ($row[0] === $thisYearArchive) {
            $curYearHasBeenArchived = TRUE;
            break;
        }
    }
    // Check the GET data to find out what action to take.
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $doArchive = test_input($_GET["archive"]);
        $restoreFromArchive = test_input($_GET["restore"]);
        $preserveTables = array();
        populateActiveIds($preserveTables, "pt");
        
        // TODO: Take action here, and then redirect or display an error
        // message.
        
    }
    
?>

<?php
    echo headerText("Archive Data");
    $errText = genFatalErrorReport(array($dbErr));
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
<h2>Archive Data</h2>
</div>
<?php
    if ($curYearHasBeenArchived) {
        $formHtml = <<<EOM
        <div class="form_description">
        <p>Our database shows that you have already archived data for summer $curCampYear. To restore the archived data to
            the current database, please click "Restore" below.  Note that this will overwrite the data currently in the
            database.  This action cannot be undone.
        </p>
        </div>
        <ul>
        <li class="buttons">
        <input class="button_text" type="submit" name="submit" value="Restore" onclick="return confirm(\"Please confirm you wish "
            "to replace the current database with the archived $curCampYear data\")" />
        <input class="hidden" name="restore" value="1" />
        </li>
        </ul>
EOM;
        echo $formHtml;
    } else {
        $tableChooser = new FormItemInstanceChooser("Tables to Preserve", FALSE, "pt", 0);
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
        $tableChooser->renderHtml();
        $formHtml = <<<EOM
        </li>
        <li class="buttons">
        <input class="button_text" type="submit" name="submit" value="Archive" onclick="return confirm(\"Please confirm you wish "
            "to archive your $curCampYear data\")" />
        <input class="hidden" name="archive" value="1" />
        </li>
        </ul>
EOM;
        echo $formHtml;
    }
    
    echo footerText();
?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
    