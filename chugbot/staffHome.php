<?php
session_start();
include_once 'dbConn.php';
include_once 'functions.php';
bounceToLogin();
setup_camp_specific_terminology_constants();

// Check for a query string that signals a message.
$parts = explode("&", $_SERVER['QUERY_STRING']);
$message = null;
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

$db = new DbConn();
$sql = "SELECT enable_camper_importer FROM admin_data";
$err = "";
$result = $db->runQueryDirectly($sql, $err);
$enableCamperImporter = false;
if ($result) {
    $row = $result->fetch_assoc();
    if ($row) {
        $enableCamperImporter = (bool)$row["enable_camper_importer"];
    }
}

$matrixUrl = urlIfy("exclusionMatrix.html");
$advancedUrl = urlIfy("advanced.php");
$archiveUrl = urlIfy("archive.php");
$resetUrl = urlIfy("staffReset.php");
$levelingUrl = urlIfy("levelHomeLaunch.php");
$reportUrl = urlIfy("report.php");
$camperUploadUrl = urlIfy("camperUpload.php");
$dbErr = "";
$sessionId2Name = array();
$blockId2Name = array();
$groupId2Name = array();
$edahId2Name = array();
$chugId2Name = array();
$bunkId2Name = array();

fillId2Name(null, $chugId2Name, $dbErr,
    "chug_id", "chugim", "group_id",
    "chug_groups");
fillId2Name(null, $sessionId2Name, $dbErr,
    "session_id", "sessions");
fillId2Name(null, $blockId2Name, $dbErr,
    "block_id", "blocks");
fillId2Name(null, $groupId2Name, $dbErr,
    "group_id", "chug_groups");
fillId2Name(null, $edahId2Name, $dbErr,
    "edah_id", "edot");
fillId2Name(null, $bunkId2Name, $dbErr,
    "bunk_id", "bunks");
?>

<?php
echo headerText("Staff Home");

$errText = genFatalErrorReport(array($dbErr), true);
if (!is_null($errText)) {
    echo $errText;
}
?>

<?php
if ($message) {
    $messageText = <<<EOM
<div class="container well">
<h2>$message</h2>
</div>
EOM;
    echo $messageText;
}
?>

<div class="card card-body mt-3 mb-3 container">
<h2>Camp Staff Control Panel</h2>
<p>To add and edit Edot, Sessions, <?php echo ucfirst(block_term_plural) ?>, Groups, and <?php echo ucfirst(chug_term_plural) ?>, expand the relevant group below.  You may also view and edit campers according to edah.</p>
<p>Use the Leveling section to run the leveling algorithm.</p>
<p>For help, hover your mouse over an item, or press on mobile.</p>
<p>To archive your data at the end of a summer, and prepare the database for the next summer, click <a href="<?php echo $archiveUrl; ?>">here</a>.</p>


<div class="container text-center">
    <a href="<?php echo $resetUrl; ?>"  class="btn btn-primary me-2" role="button" title="Click here to update the administrative settings, including staff password and camper code">Edit Admin Settings</a>
    <a href="<?php echo $matrixUrl; ?>" class="btn btn-primary me-2" role="button" title="Click here to update the de-duplication settings">De-Duplication Matrix</a>
    <a href="<?php echo $advancedUrl; ?>" class="btn btn-primary me-2" role="button" title="Click here to prune illegal or obsolete assignments">Fix Illegal And Duplicate</a>
    <?php if ($enableCamperImporter) : ?>
        <a href="<?php echo $camperUploadUrl; ?>" class="btn btn-primary" role="button" title="Click here to upload campers">Upload Campers</a>
    <?php endif; ?>
</div>

</div>

<div class="panel-group card card-body container" id="accordion">
    <div class="accordion" id="accordion">
        <div class="accordion-item">
        <?php echo genPickListForm($edahId2Name, "edah", "edot"); ?>
        </div>

        <div class="accordion-item">
        <?php echo genPickListForm($sessionId2Name, "session", "sessions"); ?>
        </div>

        <div class="accordion-item">
        <?php echo genPickListForm($blockId2Name, "block", "blocks"); ?>
        </div>

        <div class="accordion-item">
        <?php echo genPickListForm($groupId2Name, "group", "chug_groups"); ?>
        </div>

        <div class="accordion-item">
        <?php echo genPickListForm($chugId2Name, "chug", "chugim"); ?>
        </div>

        <div class="accordion-item">
        <?php echo genPickListForm($bunkId2Name, "bunk", "bunks"); ?>
        </div>
    </div>
</div>

<div class="card card-body mt-3 mb-3 container">
<h3>Leveling</h3>
<p>To view the leveling page, choose a time <?php echo block_term_singular ?> and <b>1-8</b> edot, and click "Go."</p>
<p>If there is an existing saved assignment for the selected edah/edot and <?php echo block_term_singular ?>, it will be displayed.  Nothing will be
changed until you click the Save or Reassign buttons on the leveling page.  If there is no existing assignment, one
will be created and then displayed.</p>
<p>If you choose two edot, make sure they share at least some <?php echo chug_term_plural ?>.</p>
<p>To generate a printable <?php echo chug_term_singular ?> assigment report, click "Report".
<form id="leveling_choice_form" class="card card-body mb-3 bg-light" method="get" action="<?php echo $levelingUrl; ?>">
<ul>
<li>
<label class="description" for="edah">Edah (choose 1-8)</label>
<div id="edah_checkbox">
<?php
echo genCheckBox($edahId2Name, array(), "edah_ids");
?>
</div><p class="guidelines" id="guide_1"><small>Choose 1-8 Edot.</small></p>
</li>
<li>
<label class="description" for="group" id="group_desc">Group (choose one or more)</label>
<div id="group_checkbox">
<?php
echo genConstrainedCheckBoxScript($groupId2Name, "group_ids",
    "group_checkbox", "edah_checkbox",
    "group_desc");
?>
</div><p class="guidelines" id="guide_2"><small>Select groups to level. Groups
shown here are the ones common to all selected edot.</small></p>
</li>
<li>
<label class="description" for="block"><?php echo ucfirst(block_term_singular) ?></label>
<div>
<select class="form-control" id="block" name="block">
<?php
echo genPickList($blockId2Name, array(), "block");
?>
</select>
</div><p class="guidelines" id="guide_3"><small>Choose a <?php echo ucfirst(block_term_singular) ?>.</small></p>
</li>
<li>
<input title="Launch the leveling page" class="btn btn-primary" type="submit" value="Level" />
</li>
</ul>
</form>

<form action="<?php echo $reportUrl; ?>" method="GET">
<div class="page-header">
<small>Click "Report" to go to the camper assigment report page.</small>
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
