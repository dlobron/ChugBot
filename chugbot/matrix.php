<?php
session_start();
include_once 'functions.php';
include_once 'dbConn.php';
setup_camp_specific_terminology_constants();
checkLogout();

// Require admin-level access to use any functions.
if (!adminLoggedIn()) {
    $err = "ERROR: Admin user not logged in: matrix.php forbidden";
    error_log($err);
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(array("error" => $err)));
}

// Get chugim, and exclusion status.
if (isset($_POST["get_chug_map"])) {
    $retVal = array();
    $db = new DbConn();
    $db->addSelectColumn("name");
    $db->addSelectColumn("chug_id");
    $db->addOrderByClause("GROUP BY name, chug_id ORDER BY name ");
    $err = "";
    $result = $db->simpleSelectFromTable("chugim", $err);
    if ($result == false) {
        header('HTTP/1.1 500 Internal Server Error');
        die(json_encode(array("error" => $err)));
    }
    $chugMap = array();
    $chugIds = array();
    while ($row = $result->fetch_assoc()) {
        array_push($chugMap, $row["name"]);
        array_push($chugIds, $row["chug_id"]);
    }
    $retVal["chugMap"] = $chugMap;
    $retVal["chugIds"] = $chugIds;

    $matrixMap = array();
    $db = new DbConn();
    $db->addSelectColumn("*");
    $result = $db->simpleSelectFromTable("chug_dedup_instances_v2", $err);
    if ($result == false) {
        header('HTTP/1.1 500 Internal Server Error');
        die(json_encode(array("error" => $err)));
    }
    while ($row = $result->fetch_assoc()) {
        if (!array_key_exists($row["left_chug_id"], $matrixMap)) {
            $matrixMap[$row["left_chug_id"]] = array();
        }
        $matrixMap[$row["left_chug_id"]][$row["right_chug_id"]] = 1;
    }
    $retVal["matrixMap"] = $matrixMap;
    $retVal["chugimTerm"] = chug_term_plural;
    $retVal["chugTerm"] = chug_term_singular;
    $retVal["blockTerm"] = block_term_singular;

    echo json_encode($retVal);
    exit();
}

// Update the exclusion table.  If a chug combination is checked, we want
// to add it (unless it exists).  If it's unchecked, we want to delete it if
// it's there.
if (isset($_POST["update_table"])) {
    $err = "";
    $checkMap = $_POST["checkMap"];
    foreach ($checkMap as $leftChug => $rightChug2Checked) {
        foreach ($rightChug2Checked as $rightChug => $checked) {
            $db = new DbConn();
            if ($checked) {
                // Add the tuple, unless it exists.
                $db->addIgnore();
                $db->addColumn("left_chug_id", $leftChug, 'i');
                $db->addColumn("right_chug_id", $rightChug, 'i');
                $insertOk = $db->insertIntoTable("chug_dedup_instances_v2", $err);
                if (!$insertOk) {
                    header('HTTP/1.1 500 Internal Server Error');
                    die(json_encode(array("error" => $err)));
                }
            } else {
                // Delete the tuple, if it exists.
                $db->addWhereColumn("left_chug_id", $leftChug, 'i');
                $db->addWhereColumn("right_chug_id", $rightChug, 'i');
                $delOk = $db->deleteFromTable("chug_dedup_instances_v2", $err);
                if (!$delOk) {
                    header('HTTP/1.1 500 Internal Server Error');
                    die(json_encode(array("error" => $err)));
                }
            }
        }
    }

    $ok = 1;
    echo json_encode($ok);
    exit();
}
