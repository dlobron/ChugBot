<?php
    session_start();
    include 'functions.php';
    
    // We'll return all our data as JSON.
    header('content-type: application/json; charset=UTF-8');
    
    // Get the first name for a camper ID.
    $mysqli = connect_db();
    if (isset($_POST["get_first_name"])) {
        $camper_id = $_SESSION["camper_id"];
        $sql = "SELECT first from campers where camper_id = $camper_id";
        $result = $mysqli->query($sql);
        $nameMap = array();
        $nameMap["name"] = "";
        if ($result != FALSE) {
            // If we got a first name, set it.
            $row = $result->fetch_row();
            $nameMap["name"] = $row[0];
        }
        
        $mysqli->close();
        $dbg  = json_encode($nameMap);
        echo json_encode($nameMap);
        exit();
    }
    
    if (isset($_POST["get_chug_info"])) {
        $camper_id = $_SESSION["camper_id"];
        $sql = "SELECT b.name blockname, g.name groupname, c.name chugname, c.description chugdesc " .
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
            $chugdesc = $row[3]; // May be empty
            if (! array_key_exists($blockname, $dataToJson)) {
                $dataToJson[$blockname] = array();
            }
            if (! array_key_exists($groupname, $dataToJson[$blockname])) {
                $dataToJson[$blockname][$groupname] = array();
            }
            $chugName2Desc = array();
            $chugName2Desc[$chugname] = $chugdesc;
            array_push($dataToJson[$blockname][$groupname], $chugName2Desc);
        }

        $mysqli->close();
        echo json_encode($dataToJson);
        exit();
    }

?>