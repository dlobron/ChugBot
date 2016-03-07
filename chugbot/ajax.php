<?php
    session_start();
    include_once 'functions.php';
    
    require 'PHPMailer/PHPMailerAutoload.php';
    
    // Require camper-level access to use any functions.
    if (! camperLoggedIn()) {
        exit();
    }
    
    function getCamperId() {
        $camper_id = $_SESSION["camper_id"];
        if (! isset($camper_id)) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Camper ID not set")));
        }
        return $camper_id;
    }
    
    // Return login status.
    header('content-type: application/json; charset=UTF-8');
    $mysqli = connect_db();
    if (isset($_POST["check_login"])) {
        $retVals = array();
        $retVals["loggedIn"] = isset($_SESSION['admin_logged_in']);
        $retVals["loginUrl"] = urlIfy("staffLogin.php");
        $mysqli->close();
        echo json_encode($retVals);
        exit();
    }
    
    // Get the current set of prefs for this camper (if any), so that
    // the rank page can start with the current choices.
    if (isset($_POST["get_existing_choices"])) {
        $camper_id = getCamperId();
        $sql =
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 1 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.first_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 2 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.second_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 3 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.third_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 4 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.fourth_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 5 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.fifth_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 6 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.sixth_choice_id=c.chug_id " .
        "order by blockname, groupname, rank";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database Failure")));
        }
        $existingChoices = array();
        while ($row = $result->fetch_row()) {
            $blockname = $row[0];
            $groupname = $row[1];
            $chugname = $row[2];
            $chug_id = $row[3];
            // Make a key/value pair: block/group -> ordered list of chug choices.
            $key = $blockname . "||" . $groupname;
            if (! array_key_exists($key, $existingChoices)) {
                $existingChoices[$key] = array();
            }
            $val = $chugname . "||" . $chug_id;
            array_push($existingChoices[$key], $val);
        }
        
        $mysqli->close();
        echo json_encode($existingChoices);
        exit();
    }
    
    // Update preferences for a camper, and email them a confirmation of their
    // choices if they have an email.
    if (isset($_POST["submit_prefs"])) {
        $camper_id = getCamperId();
        $pref_arrays = $_POST["pref_arrays"];
        // $pref_arrays is an array of arrays.  Each array is a list of preferred chugim,
        // in order, for a block/group tuple (for example, July 1, aleph).  The
        // first item in the array is a ||-separated tuple indicating these things,
        // for example: August 2||aleph.
        
        // First, make an associative array mapping chug ID to name (for email).
        $chugId2Name = array();
        $sql = "SELECT chug_id, name FROM chugim";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database error: can't get chug name->ID map")));
        }
        while ($row = mysqli_fetch_row($result)) {
            $chugId2Name[$row[0]] = $row[1];
        }
        
        // Next, get the first and last name, and email associated with this camper.
        $first = "";
        $last = "";
        $email = "";
        $sql = "SELECT email, first, last from campers where camper_id=$camper_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database error: can't get camper data")));
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
<p>We have received your chug preferences, <b>$first</b>!  Please review your choices to make sure they are correct.
If anything is incorrect or missing, you can go back to correct it by clicking ${homeAnchor}, or by pasting this link into your browser: $homeUrl</p>
END;
        // Delete existing selections, and insert the new ones.
        $deleteSql =
        "DELETE FROM preferences WHERE camper_id=$camper_id";
        $result = $mysqli->query($deleteSql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Delete failed")));
        }
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
                    $block_id = $row[0];
                    $sql = "SELECT group_id FROM groups WHERE name=\"$group\"";
                    $result = $mysqli->query($sql);
                    if ($result == FALSE) {
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => "Database error: block $block not found")));
                    }
                    $row = $result->fetch_row();
                    $group_id = $row[0];
                    $insertSql =
                    "INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, " .
                    "second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) " .
                    "VALUES ($camper_id, $group_id, $block_id, CHOICE1, CHOICE2, CHOICE3, CHOICE4, CHOICE5, CHOICE6)";
                    continue;
                }
                $chug_id = $chuglist[$i];
                if (! isset($chugId2Name[$chug_id])) {
                    error_log("ajax: no name found for chug ID $chug_id");
                    header('HTTP/1.1 500 Internal Server Error');
                    die(json_encode(array("error" => "Database error: chug choice $chug_id has no name in the database")));
                }
                $chugName = $chugId2Name[$chug_id];
                $email_text .= "<li>$chugName</li>\n";
                $toReplace = "CHOICE" . strval($i);
                $insertSql = str_replace($toReplace, $chug_id, $insertSql);
            }
            $email_text .= "</ol>\n";
            // Replace remaining CHOICE elements with NULL, and insert.  Note that if the camper does
            // not submit any prefs for a group/block tuple for which they are signed up, then the preferences
            // table will have NULL for all prefs.
            $insertSql = preg_replace("/CHOICE\d/i", "NULL", $insertSql);
            $result = $mysqli->query($insertSql);
            if ($result == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => "Insert failed")));
            }
        }
        
        // If we have an email address, send a confirmation email listing the
        // camper's choices.
        if ($email) {
            $sql = "SELECT * FROM admin_data";
            $result = $mysqli->query($sql);
            if ($result == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => "Database Failure")));
            }
            $row = $result->fetch_assoc();
            
            // An example of the possible parameters for PHPMailer can be found here:
            // https://github.com/Synchro/PHPMailer/blob/master/examples/gmail.phps
            // The settings below are the ones needed by CRNE's ISP, A Small Orange, as
            // of 2016.
            $mail = new PHPMailer;
            $mail->addAddress($email);
            $mail->isSMTP();
            $mail->Host = 'localhost';
            $mail->Port = 25;
            $mail->SMTPAuth = true;
            $mail->Username = $row["admin_email_username"];
            $mail->Password = $row["admin_email_password"];
            $mail->setFrom($row["admin_email"], "Camp Ramah");
            
            $mail->Subject = "Camp Ramah chug preferences for $first $last";
            $mail->isHTML(true);
            $mail->Body = $email_text;
            if (! $mail->send()) {
                error_log("Failed to send email to $email");
                error_log("Mailer error: " . $mail->ErrorInfo);
            } else {
                error_log("Sent confirmation email to $email");
            }
        } else {
            error_log("No email is configured for $first $last: Not sending confirmation");
        }
        
        // After doing the DB updates, grab the name and home URL, and return them, for
        // display in the confirmation window.
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
    
    // Get the first name for a camper ID, and leveling instructions.
    if (isset($_POST["get_first_name_and_instructions"])) {
        $camper_id = $camper_id = getCamperId();
        $sql = "SELECT c.first first, a.pref_page_instructions from campers c, admin_data a where c.camper_id = $camper_id";
        $result = $mysqli->query($sql);
        $nameMap = array();
        $nameMap["name"] = "";
        $nameMap["instructions"] = "";
        if ($result != FALSE) {
            // If we got a first name, set it.
            $row = $result->fetch_row();
            $nameMap["name"] = $row[0];
            $nameMap["instructions"] = $row[1];
        }
        
        $mysqli->close();
        echo json_encode($nameMap);
        exit();
    }
    
    // Get the chug lists corresponding to a camper's registration.  When we select, sort
    // July ahead of August.
    if (isset($_POST["get_chug_info"])) {
        $camper_id = $camper_id = getCamperId();
        $sql = "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, c.description chugdesc " .
        "FROM " .
        "campers cm, block_instances bi, blocks b, chug_instances ci, chugim c, groups g, edot_for_chug e, edot_for_block eb " .
        "WHERE " .
        "cm.camper_id = $camper_id AND " .
        "cm.session_id = bi.session_id AND " .
        "bi.block_id = b.block_id AND " .
        "b.block_id = ci.block_id AND " .
        "ci.chug_id = c.chug_id AND " .
        "e.chug_id = c.chug_id AND " .
        "e.edah_id = cm.edah_id AND " .
        "eb.edah_id = cm.edah_id AND " .
        "eb.block_id = b.block_id AND " .
        "c.group_id = g.group_id " .
        "ORDER BY CASE WHEN (blockname LIKE 'July%' OR blockname LIKE 'july%') THEN CONCAT('a', blockname) ".
        "WHEN (blockname LIKE 'Aug%' OR blockname LIKE 'aug%') THEN CONCAT('b', blockname) ELSE blockname END, ".
        "groupname, chugname";
        error_log("DBG: sql = $sql");
        
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database Failure")));
        }
        // Create an associative array with the following key/val pairs:
        // blockname/GR, where GR is another associative array with these key/val pairs:
        // groupname/list-of-chugim
        // For example: ["July 1" => ["aleph" => "cooking, swimming", "bet" => "boating, diving"], ...]
        $dataToJson = array();
        while ($row = $result->fetch_row()) {
            $blockname = $row[0];
            $groupname = $row[1];
            $chugname = $row[2];
            $chug_id = $row[3];
            $chugdesc = $row[4]; // May be empty
            if (! array_key_exists($blockname, $dataToJson)) {
                $dataToJson[$blockname] = array();
            }
            if (! array_key_exists($groupname, $dataToJson[$blockname])) {
                $dataToJson[$blockname][$groupname] = array();
            }
            $chugNameAndId2Desc = array();
            $key = $chugname . "||" . $chug_id;
            $chugNameAndId2Desc[$key] = $chugdesc;
            array_push($dataToJson[$blockname][$groupname], $chugNameAndId2Desc);
        }

        $mysqli->close();
        echo json_encode($dataToJson);
        exit();
    }

?>
