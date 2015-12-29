<?php
    session_start();
    include 'functions.php';
    
    require 'PHPMailer/PHPMailerAutoload.php';
    
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
        "SELECT b.name blockname, g.name groupname, c.name chugname, 1 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.first_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name, 2 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.second_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name, 3 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.third_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name, 4 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.fourth_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name, 5 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.fifth_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name, 6 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id=$camper_id AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.sixth_choice_id=c.chug_id " .
        "order by blockname, groupname, rank";
        error_log("DBG: $sql");
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
            // Make a key/value pair: block/group -> ordered list of chug choices.
            $key = $blockname . "||" . $groupname;
            if (! array_key_exists($key, $existingChoices)) {
                $existingChoices[$key] = array();
            }
            array_push($existingChoices[$key], $chugname);
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
<p>Thank you for using ChugBot, <b>$first</b>!  Please review your choices to make sure they are correct.
If anything is incorrect or missing, you can go back to ChugBot anytime to correct it by clicking ${homeAnchor},
or by pasting this link into your browser: $homeUrl</p>
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
            // Replace remaining CHOICE elements with NULL, and insert.
            $insertSql = preg_replace("/CHOICE\d/i", "NULL", $insertSql);
            $result = $mysqli->query($insertSql);
            if ($result == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => "Insert failed")));
            }
        }
        
        // If we have an email address, send a confirmation email listing the
        // camper's choices.
        if (! empty($email)) {
            // TODO: Ask for host mail parameters.  They can live in the database
            // with the other staff info.  The parameters we need can be found here:
            // https://github.com/Synchro/PHPMailer/blob/master/examples/gmail.phps
            // For now, we can test locally with GMail SMTP, but make sure not to check
            // in a hard-coded password to GitHub!
            // I think we need: mail server host, mail server port, encryption (none/ssl/tls),
            // SMTP auth (yes/no), username, password, from address, reply-to address (if not
            // the same).  We only need username and password if SMTP auth is true.
            // Hm- maybe it's better to have no auth, since we can't store the password
            // in hashed form (no way to retrieve it).
            $mail = new PHPMailer;
            $mail->Subject = "Camp Ramah Chug Choice Confirmation for $first $last";
            $mail->Body = $email_text;
            $mail->addAddress($email);
            $mail->isHTML(true);
            // $mail->addReplyTo(TODO);
            // $mail->setFrom(TODO);
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
    
    // Get the chug lists corresponding to a camper's registration.  When we select, sort
    // July ahead of August.
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
        "ORDER BY CASE WHEN (blockname LIKE 'July%' OR blockname LIKE 'july%') THEN CONCAT('a', blockname) ".
        "WHEN (blockname LIKE 'Aug%' OR blockname LIKE 'aug%') THEN CONCAT('b', blockname) ELSE blockname END, ".
        "groupname, chugname";
        
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
