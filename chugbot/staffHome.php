<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';
    bounceToLogin();
    
    // Check for a query string that signals a message.
    $parts = explode("&", $_SERVER['QUERY_STRING']);
    $message = NULL;
    foreach ($parts as $part) {
        $cparts = explode("=", $part);
        if (count($cparts) != 2) {
            continue;
        }
        if ($cparts[0] == "update" &&
            $cparts[1] == "pw") {
            $message = "<font color=\"green\">Admin password updated!</font>";
            break;
        } else if ($cparts[0] == "update" &&
                   $cparts[1] == "as") {
            $message = "<font color=\"green\">Admin settings updated!</font>";
            break;
        } else if ($cparts[0] == "update" &&
                   $cparts[1] == "ex") {
            $message = "<font color=\"green\">De-duplication matrix updated!</font>";
            break;
        } else if ($cparts[0] == "error" &&
                   $cparts[1] == "ex") {
            $message = "<font color=\"red\">Error updating de-duplication matrix.</font> Please try again, or escalate to an administrator.";
            break;
        }
    }

    $matrixUrl = urlIfy("exclusionMatrix.html");
    $advancedUrl = urlIfy("advanced.php");
    $archiveUrl = urlIfy("archive.php");
    $resetUrl = urlIfy("staffReset.php");
    $levelingUrl = urlIfy("levelHomeLaunch.php");
    $reportUrl = urlIfy("report.php");
    $dbErr = "";
    $sessionId2Name = array();
    $blockId2Name = array();
    $groupId2Name = array();
    $edahId2Name = array();
    $chugId2Name = array();
    $bunkId2Name = array();
    
    fillId2Name(NULL, $chugId2Name, $dbErr,
                "chug_id", "chugim", "group_id",
                "groups");
    fillId2Name(NULL, $sessionId2Name, $dbErr,
                "session_id", "sessions");
    fillId2Name(NULL, $blockId2Name, $dbErr,
                "block_id", "blocks");
    fillId2Name(NULL, $groupId2Name, $dbErr,
                "group_id", "groups");
    fillId2Name(NULL, $edahId2Name, $dbErr,
                "edah_id", "edot");
    fillId2Name(NULL, $bunkId2Name, $dbErr,
                "bunk_id", "bunks");
    ?>

<?php
    echo headerText("Staff Home");
    
    $errText = genFatalErrorReport(array($dbErr), TRUE);
    if (! is_null($errText)) {
        echo $errText;
    }
    ?>

<?php
    if ($message) {
        $messageText = <<<EOM
<div class="container centered_container">
<h2>$message</h2>
</div>
EOM;
        echo $messageText;
    }
    ?>

<div class="centered_container container-fluid">
<h2>Camp Staff Control Panel</h2>
<p>To add and edit Edot, Sessions, Blocks, Groups, and Chugim, expand the relevant group below.  You may also view and edit campers according to edah.</p>
<p>Use the Leveling section to run the leveling algorithm.</p>
<p>For help, hover your mouse over an item, or press on mobile.<p>
<p>To prune obsolete items and merge duplicate camper registrations, click <a href="<?php echo $advancedUrl; ?>">here</a>. To archive your data at the end of a summer, and prepare the database for the next summer, click <a href="<?php echo $archiveUrl; ?>">here</a>.</p>

<form class="appnitro" action="<?php echo $resetUrl; ?>">
<button title="Click here to update the administrative settings, including staff password and camper code" class="btn btn-primary" type="submit" value="1">Edit Admin Settings</button>
</form>

<form class="appnitro" action="<?php echo $matrixUrl; ?>">
<button title="Click here to update the de-duplication settings" class="btn btn-primary" type="submit" value="1">De-Duplication Matrix</button>
</form>

</div>

<div class="panel-group" id="accordion">
<div class="multi_form_container">
<?php echo genPickListForm($edahId2Name, "edah", "edot"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($sessionId2Name, "session", "sessions"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($blockId2Name, "block", "blocks"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($groupId2Name, "group", "groups"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($chugId2Name, "chug", "chugim"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($bunkId2Name, "bunk", "bunks"); ?>
</div>
</div>

<div class="multi_form_container">
<h3>Leveling</h3>
<p>To view the leveling page, choose a time block and <b>one</b> or <b>two</b> edot, and click "Go."</p>
<p>If there is an existing saved assignment for the selected edah/edot and block, it will be displayed.  Nothing will be
changed until you click the Save or Reassign buttons on the leveling page.  If there is no existing assignment, one
will be created and then displayed.</p>
<p>If you choose two edot, make sure they share at least some chugim.</p>
<p>To generate a printable chug assigment report, click "Report".
<form id="leveling_choice_form" class="appnitro" method="get" action="<?php echo $levelingUrl; ?>">
<ul>
<li>
<label class="description" for="edah">Edah (choose one or two)</label>
<div>
<?php
    echo genCheckBox($edahId2Name, array(), "edah_ids");
    ?>
</div><p class="guidelines" id="guide_1"><small>Choose One or Two Edot.</small></p>
</li>
<li>
<label class="description" for="block">Block</label>
<div>
<select class="form-control" id="block" name="block">
<?php
    echo genPickList($blockId2Name, array(), "block");
    ?>
</select>
</div><p class="guidelines" id="guide_2"><small>Choose a Block.</small></p>
</li>
<li>
<input title="Launch the leveling page" class="btn btn-primary" type="submit" value="Level" />
</li>
</ul>
</form>

<form class="appnitro" action="<?php echo $reportUrl; ?>" method="GET">
<div class="form_description">
<p>Click "Report" to go to the camper assigment report page.</p>
</div>
<button title="Go to the Report page" class="btn btn-primary" type="submit">Report</button>
<input type="hidden" name="reset" id="reset" value="1" />
</form>
</div>

<?php
    echo footerText();
?>

</body>
</html>
