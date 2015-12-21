<?php
    session_start();
    include 'functions.php';
    
    function getCamperId() {
        $camper_id = $_SESSION["camper_id"];
        if (! isset($camper_id)) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Camper ID not set")));
        }
        return $camper_id;
    }
    
    // We'll return all our data as JSON.
    header('content-type: application/json; charset=UTF-8');
    $mysqli = connect_db();
    
    // Update preferences for a camper, and email them a confirmation of their
    // choices if they have an email.
    if (isset($_POST["submit_prefs"])) {
        $camper_id = getCamperId();
        $pref_arrays = $_POST["pref_arrays"];
        // $pref_arrays is an array of arrays.  Each array is a list of preferred chugim,
        // in order, for a block/group tuple (for example, July 1, aleph).  The
        // first item in the array is a ||-separated tuple indicating these things,
        // for example: August 2||aleph.
        
        // First, make an associative array mapping chug name to ID.
        $chugName2Id = array();
        $sql = "SELECT name, chug_id FROM chugim";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database error: can't get chug name->ID map")));
        }
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $chugName2Id[$row[0]] = $row[1];
        }
        
        // Next, get the first and last name, and email associated with this camper.
        $first = "";
        $last = "";
        $email = "";
        $sql = "SELECT email, first, last from campers where camper_id=$camper_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database error: can't get chug name->ID map")));
        }
	if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $email = $row[0]; // Might be NULL
            $first = $row[1];
            $last = $row[2];
        }

        $homeAnchor = homeAnchor();
        $homeUrl = homeUrl();
        $email_text = <<<END
<html>
<head>
<title>Chug Ranking Confirmation</title>
</head>
<body>
<p>Thank you for using ChugBot, <b>$first</b>!  Please review your choices to make sure they are correct.
If anything is incorrect or missing, you can go back to ChugBot anytime to correct it by clicking ${homeAnchor},
or by pasting this link into your browser: $homeUrl</p>
END;
        foreach ($pref_arrays as $chuglist) {
            $group_id = -1;
            $block_id = -1;
            $updateSql = "";
            $deleteSql = "";
            for ($i = 0; $i < count($chuglist); $i++) {
                if ($i > 6) {
                    break; // Only choices 1-6 are counted (0 is the block/group string).
                }
                if ($i == 0) {
                    // Parse the block and group name, and grab the corresponding IDs
                    // from the database.
                    $parts = explode("||", $chuglist[0]);
                    $block = $parts[0];
                    $group = $parts[1];
                    $email_text .= "<h3>Choices for $block, group $group:</h3>\n";
                    $email_text .= "<ol>\n";
                    $sql = "SELECT block_id FROM blocks WHERE name=\"$block\"";
                    $result = $mysqli->query($sql);
                    if ($result == FALSE) {
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => "Database error: block $block not found")));
                    }
                    $row = $result->fetch_row();
                    $group_id = $row[0];
                    $sql = "SELECT group_id FROM groups WHERE name=\"$group\"";
                    $result = $mysqli->query($sql);
                    if ($result == FALSE) {
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => "Database error: block $block not found")));
                    }
                    $row = $result->fetch_row();
                    $block_id = $row[0];
                    $insertSql =
                    "INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, " .
                    "second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) " .
                    "VALUES ($camper_id, $group_id, $block_id, CHOICE1, CHOICE2, CHOICE3, CHOICE4, CHOICE5, CHOICE6)";
                    $deleteSql =
                    "DELETE FROM preferences WHERE camper_id=$camper_id AND group_id=$group_id AND block_id=$block_id";
                    continue;
                }
                $chug = $chuglist[$i];
                $email_text .= "<li>$chug</li>\n";
                if (! isset($chugName2Id[$chug])) {
                    error_log("ajax: no ID found for chug $chug");
                    header('HTTP/1.1 500 Internal Server Error');
                    die(json_encode(array("error" => "Database error: chug choice $chug has no ID in the database")));
                }
                $chug_id = $chugName2Id[$chug];
                $toReplace = "CHOICE" . strval($i);
                $insertSql = str_replace($toReplace, $chug_id, $insertSql);
            }
            $email_text .= "</ol>\n";
            // Replace remaining CHOICE elements.
            $insertSql = preg_replace("/CHOICE\d/i", "NULL", $insertSql);
            
            // Run the deletion, and then the insert.
            $result = $mysqli->query($deleteSql);
            if ($result == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => "Delete failed")));
            }
            $result = $mysqli->query($insertSql);
            if ($result == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => "Insert failed")));
            }
        }
        
        $email_text .= <<<EOM
</body>
</html>
EOM;
        
        // If we have an email address, send a confirmation email listing the
        // camper's choices.
        if (! empty($email)) {
            // TODO: Ask David what to put for From and Reply-To
            $sentOk = mail($email, "Camp Ramah Chug Choice Confirmation for $first $last",
                           $email_text, "From: info@campramahne.org");
            if (! $sentOk) {
                error_log("Failed to send email to $email");
            }
        }
        
        // After doing the DB updates, grab the name and home URL, and return them.
        $sql = "SELECT first from campers where camper_id = $camper_id";
        $result = $mysqli->query($sql);
        $retVal = array();
        if ($result != FALSE) {
            $row = $result->fetch_row();
            $retVal["name"] = $row[0];
            $retVal["homeUrl"] = homeUrl();
        }
        
        $mysqli->close();
        echo json_encode($retVal);
        exit();
    }
    
    // Get the first name for a camper ID.
    if (isset($_POST["get_first_name"])) {
        $camper_id = $camper_id = getCamperId();
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
    
    // Get the chug lists corresponding to a camper's registration.
    if (isset($_POST["get_chug_info"])) {
        $camper_id = $camper_id = getCamperId();
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
            die(json_encode(array("error" => "Database Failure")));
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
            $chugdesc = $row[3]; // May be empty
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

?>
