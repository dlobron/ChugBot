<?php
    session_start();
    include_once 'functions.php';
    include_once 'dbConn.php';
    
    // Require admin-level access to use any functions.
    if (! adminLoggedIn()) {
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
        $db->addOrderByClause("GROUP BY name ORDER BY name ");
        $err = "";
        $result = $db->simpleSelectFromTable("chugim", $err);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        $chugMap = array();
        while ($row = $result->fetch_assoc()) {
            array_push($chugMap, $row["name"]);
        }
        $retVal["chugMap"] = $chugMap;
        
        $matrixMap = array();
        $db = new DbConn();
        $db->addSelectColumn("*");
        $result = $db->simpleSelectFromTable("chug_dedup_instances", $err);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        while ($row = $result->fetch_assoc()) {
            if (! array_key_exists($row["left_chug_name"], $matrixMap)) {
                $matrixMap[$row["left_chug_name"]] = array();
            }
            $matrixMap[$row["left_chug_name"]][$row["right_chug_name"]] = 1;
        }
        $retVal["matrixMap"] = $matrixMap;
        
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
                    $db->addColumn("left_chug_name", $leftChug, 's');
                    $db->addColumn("right_chug_name", $rightChug, 's');
                    $insertOk = $db->insertIntoTable("chug_dedup_instances", $err);
                    if (! $insertOk) {
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => $err)));
                    }
                } else {
                    // Delete the tuple, if it exists.
                    $db->addWhereColumn("left_chug_name", $leftChug, 's');
                    $db->addWhereColumn("right_chug_name", $rightChug, 's');
                    $delOk = $db->deleteFromTable("chug_dedup_instances", $err);
                    if (! $delOk) {
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


?>