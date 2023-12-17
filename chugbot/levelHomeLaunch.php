<?php
session_start();
include_once 'assignment.php';
include_once 'dbConn.php';
bounceToLogin();
$err = $dbErr = "";

if ($_SERVER["REQUEST_METHOD"] != "GET") {
    $err = errorString("Unknown request method.");
}
$edah_ids = $_GET["edah_ids"];
$group_ids = $_GET["group_ids"];
$block_id = intval(test_input($_GET["block"]));
if ($edah_ids == null || $block_id == null || $group_ids == null ||
    empty($edah_ids) || empty($group_ids)) {
    $err = errorString("Block and at least one edah and group must be specified.");
} else if (count($edah_ids) > 8) {
    $err = errorString("No more than 8 edot may be leveled together.");
}
if ($err) {
    echo genErrorPage($err);
    exit;
}

$levelHomeUrl = urlIfy("levelHome.html");
$levelHomeUrl .= "?block=$block_id";
foreach ($edah_ids as $edah_id) {
    $levelHomeUrl .= "&edah_ids[]=$edah_id";
}
foreach ($group_ids as $group_id) {
    $levelHomeUrl .= "&group_ids[]=$group_id";
}
// Check for a existing assignments for our edot.  We consider a combination
// already assigned if we have at least one assigned camper in every edah,
// for this block.
$matchedAll = true;
foreach ($edah_ids as $edah_id) {
    $db = new DbConn();
    $db->addColumn("block_id", $block_id, 'i');
    $db->addColumn("edah_id", $edah_id, 'i');
    $db->isSelect = true;
    $sql = "SELECT * FROM matches m, chug_instances i, campers c " .
        "WHERE m.chug_instance_id = i.chug_instance_id AND " .
        "i.block_id = ? AND " .
        "m.camper_id = c.camper_id AND " .
        "c.edah_id = ?";
    $result = $db->doQuery($sql, $dbErr);
    if ($result === false) {
        echo genErrorPage($dbErr);
        exit;
    }
    if ($result->num_rows == 0) {
        // No matches found for this edah/block.
        $matchedAll = false;
        break;
    }
}
if ($matchedAll) {
    // We have an existing assignment: redirect to the display/edit page.
    echo forwardNoHistory($levelHomeUrl);
    exit;
}

// We're now ready to build our assignments.  We iterate over each activity
// group that applies to this edah, and make an assignment for each one.
// Loop through groups.  Do each assignment (if requested).
$edotText = implode(", ", $edah_ids);
foreach ($group_ids as $group_id) {
    $err = "";
    $ok = do_assignment($edah_ids, $block_id, $group_id, $err);
    if (!$ok) {
        error_log("Assignment for edah $edotText, block $block_id, group $group_id failed");
        if (empty($err)) {
            $err = "Unknown assignment error";
        }
        echo genErrorPage($err);
        exit;
    }
}
// Assignments done - redirect to the assignment page.
error_log("Assigned edot $edotText, block $block_id OK");
echo forwardNoHistory($levelHomeUrl);
exit;
