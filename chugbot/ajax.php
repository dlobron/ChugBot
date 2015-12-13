<?php
    session_start();
    include 'functions.php';
    
    // We'll return all our data as JSON.
    header('content-type: application/json; charset=UTF-8');
    
    // Get the time blocks for a camper, and the chugim in each.
    $mysqli = connect_db();
    if (isset($_POST["rank_page_camper_id"])) {
        $camper_id = test_input($_POST["rank_page_camper_id"]);
        $sql = "SELECT b.name blockname, g.name groupname, c.name chugname " .
        "FROM " .
        "campers cm, block_instances bi, blocks b, chug_instances ci, chugim c, groups g " .
        "WHERE " .
        "cm.camper_id = $camper_id AND " .
        "cm.session_id = bi.session_id AND " .
        "bi.block_id = b.block_id AND " .
        "b.block_id = ci.block_id AND " .
        "ci.chug_id = c.chug_id AND " .
        "c.group_id = g.group_id " .
        "ORDER BY blockname, groupname, chugname";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database Failure")));
        }
        // Create an associative array with the following key/val pairs:
        // blockname/GR, where GR is another associative array with these key/val pairs:
        // groupname/list-of-chugim
        // For example: ["July 1" => ["aleph" => "cooking, swimming", "bet" => "boating, diving"], ...]
        // Then, return this in JSON format.
        $dataToJson = array();
        while ($row = $result->fetch_row()) {
            $blockname = $row[0];
            $groupname = $row[1];
            $chugname = $row[2];
            if (! array_key_exists($blockname, $dataToJson)) {
                $dataToJson[$blockname] = array();
            }
            if (! array_key_exists($groupname, $dataToJson[$blockname])) {
                $dataToJson[$blockname][$groupname] = array();
            }
            array_push($dataToJson[$blockname][$groupname], $chugname);
        }

        $dbgStr = json_encode($dataToJson);
        echo json_encode($dataToJson);
        exit();
    }

?>