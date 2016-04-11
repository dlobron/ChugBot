<?php
    session_start();
    include_once 'functions.php';
    include_once 'dbConn.php';
    
    // Require camper-level access to use any functions.
    if (! camperLoggedIn()) {
        exit();
    }
    
    function getCamperId() {
        $camper_id = $_SESSION["camper_id"];
        if (! isset($camper_id)) {
            return NULL;
        }
        
        return $camper_id;
    }
    
    // Return login status.
    header('content-type: application/json; charset=UTF-8');
    if (isset($_POST["check_login"])) {
        $retVals = array();
        $retVals["loggedIn"] = isset($_SESSION['admin_logged_in']);
        $retVals["loginUrl"] = urlIfy("staffLogin.php");
        echo json_encode($retVals);
        exit();
    }
    
    // Compute and return nav text for an HTML page.
    if (isset($_POST["get_nav"])) {
        $retVal = navText();
        echo json_encode($retVal);
        exit();
    }
    
    // Get the current set of prefs for this camper (if any), so that
    // the rank page can start with the current choices.
    if (isset($_POST["get_existing_choices"])) {
        $existingChoices = array();
        $camper_id = getCamperId();
        if ($camper_id === NULL) {
            // If we were loaded from the Add Camper page, there will not
            // be any preferences for this camper yet.
            echo json_encode($existingChoices);
            exit();
        }
        $err = "";
        $db = new DbConn();
        for ($i = 0; $i < 6; $i++) {
            $db->addColVal($camper_id, 'i');
        }
        $sql =
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 1 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id = ? AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.first_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 2 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id = ? AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.second_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 3 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id = ? AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.third_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 4 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id = ? AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.fourth_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 5 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id = ? AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.fifth_choice_id=c.chug_id UNION ALL " .
        "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, 6 rank " .
        "FROM blocks b, groups g, chugim c, preferences p WHERE p.camper_id = ? AND " .
        "b.block_id=p.block_id AND g.group_id=p.group_id AND p.sixth_choice_id=c.chug_id " .
        "order by blockname, groupname, rank";
        $db->isSelect = TRUE; // Must set manually for direct queries.
        $result = $db->doQuery($sql, $err);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        
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
        
        echo json_encode($existingChoices);
        exit();
    }
    
    // Enter or update preferences for a camper, and email them a confirmation of their
    // choices.
    if (isset($_POST["submit_prefs"])) {
        $err = "";
        $camper_id = getCamperId();
        if ($camper_id === NULL) {
            // If there's no camper ID yet, it means that a newly-added camper is
            // submitting preferences for the first time.  In this case, we add
            // the camper here.  All camper columns are required except bunk_id.
            $db = new DbConn();
            $db->addColumn("first", $_SESSION["first"], 's');
            $db->addColumn("last", $_SESSION["last"], 's');
            $db->addColumn("email", $_SESSION["email"], 's');
            $db->addColumn("session_id", intval($_SESSION["session_id"]), 'i');
            $db->addColumn("edah_id", intval($_SESSION["edah_id"]), 'i');
            $bunkIdVal = NULL;
            if (array_key_exists("bunk_id", $_SESSION)) {
                $bunkIdVal = intval($_SESSION["bunk_id"]);
            }
            $db->addColumn("bunk_id", $bunkIdVal, i);
            if (! $db->insertIntoTable("campers", $err)) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
            // Grab the newly-created camper ID, for use below, and set it.
            $camper_id = $db->insertId();
            $_SESSION["camper_id"] = $camper_id;
            error_log("DBG: Submitted new camper with ID $camper_id");
        }
        
        $pref_arrays = $_POST["pref_arrays"];
        // $pref_arrays is an array of arrays.  Each array is a list of preferred chugim,
        // in order, for a block/group tuple (for example, July 1, aleph).  The
        // first item in the array is a ||-separated tuple indicating these things,
        // for example: August 2||aleph.
        // First, make an associative array mapping chug ID to name (for email).
        $db = new DbConn();
        $chugId2Name = array();
        $sql = "SELECT chug_id, name FROM chugim";
        
        $result = $db->runQueryDirectly($sql, $err);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        while ($row = mysqli_fetch_row($result)) {
            $chugId2Name[$row[0]] = $row[1];
        }
        
        // Next, get the first and last name, and email associated with this camper.
        $first = "";
        $last = "";
        $email = "";
        $db = new DbConn();
        $db->addSelectColumn("email");
        $db->addSelectColumn("first");
        $db->addSelectColumn("last");
        $db->addWhereColumn("camper_id", $camper_id, 'i');
        $err = "";
        $result = $db->simpleSelectFromTable("campers", $err);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
	if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $email = $row[0]; // Might be NULL
            $first = $row[1];
            $last = $row[2];
        }

        $homeUrl = urlIfy("camperHome.php");
        $homeAnchor = "<a href=\"$homeUrl\">here</a>";
        $email_text = <<<END
<html><body>
<h3>Preferences Recorded!</h3>
<p>We have received your chug preferences, <b>$first</b>!  Please review your choices to make sure they are correct.
If anything is incorrect or missing, you can go back to correct it by clicking ${homeAnchor}, or by pasting this link into your browser: $homeUrl</p>
END;
        // Delete existing selections, and insert the new ones.
        $err = "";
        $db = new DbConn();
        $db->addWhereColumn("camper_id", $camper_id, 'i');
        $delOk = $db->deleteFromTable("preferences", $err);
        if ($delOk == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        // Make an array of index to choice column.  The placeholder is needed because the preferred chug lists are 1-based (the 0th
        // item is the block||group string).
        $choiceCols = array("placeholder", "first_choice_id","second_choice_id","third_choice_id","fourth_choice_id","fifth_choice_id","sixth_choice_id");
        foreach ($pref_arrays as $chuglist) {
            $db = new DbConn();
            $group_id = -1;
            $block_id = -1;
            for ($i = 0; $i < count($chuglist); $i++) {
                if ($i >= count($choiceCols)) {
                    break;
                }
                if ($i == 0) {
                    // Parse the block and group name, and grab the corresponding IDs
                    // from the database.
                    $parts = explode("||", $chuglist[0]);
                    $block = $parts[0];
                    $group = $parts[1];
                    $email_text .= "<h3>Choices for $block, group $group:</h3>\n";
                    $email_text .= "<ol>\n";
                    $db2 = new DbConn();
                    $db2->addSelectColumn("block_id");
                    $db2->addWhereColumn("name", $block, 's');
                    $err = "";
                    $result = $db2->simpleSelectFromTable("blocks", $err);
                    if ($result == FALSE) {
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => $err)));
                    }
                    $row = $result->fetch_row();
                    $block_id = $row[0];
                    $db2 = new DbConn();
                    $db2->addSelectColumn("group_id");
                    $db2->addWhereColumn("name", $group, 's');
                    $err = "";
                    $result = $db2->simpleSelectFromTable("groups", $err);
                    if ($result == FALSE) {
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => $err)));
                    }
                    $row = $result->fetch_row();
                    $group_id = $row[0];
                    $db->addColumn("camper_id", $camper_id, 'i');
                    $db->addColumn("group_id", $group_id, 'i');
                    $db->addColumn("block_id", $block_id, 'i');
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
                $db->addColumn($choiceCols[$i], $chug_id, 'i');
            }
            $email_text .= "</ol>\n";
            // Set remaining columns to NULL.
            for (; $i < count($choiceCols); $i++) {
                $db->addColumn($choiceCols[$i], NULL, 'i');
            }
            $err = "";
            $result = $db->insertIntoTable("preferences", $err);
            if ($result == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
        }
        
        // If we have an email address, send a confirmation email listing the
        // camper's choices.
        if ($email) {
            $db = new DbConn();
            $sql = "SELECT * FROM admin_data";
            $err = "";
            $result = $db->runQueryDirectly($sql, $err);
            if ($result == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
            $row = $result->fetch_assoc();
            $mailError = "";
            $sentOk = sendMail($email,
                             "Camp Ramah chug preferences for $first $last",
                             $email_text,
                             $row,
                             $mailError);
        } else {
            error_log("No email is configured for $first $last: Not sending confirmation");
        }

        // After doing the DB updates, grab the name and home URL, and return them, for
        // display in the confirmation window.
        $db = new DbConn();
        $db->addSelectColumn("first");
        $db->addSelectColumn("email");
        $db->addWhereColumn("camper_id", $camper_id, 'i');
        $err = "";
        $result = $db->simpleSelectFromTable("campers", $err);
        $retVal = array();
        if ($result != FALSE) {
            $row = $result->fetch_row();
            $retVal["name"] = $row[0];
            $retVal["email"] = $row[1];
            $retVal["homeUrl"] = homeUrl();
        }
        
        echo json_encode($retVal);
        exit();
    }
    
    // Get the first name for a camper ID, and leveling instructions.
    if (isset($_POST["get_first_name_and_instructions"])) {
        $nameMap = array();
        $nameMap["name"] = "";
        $nameMap["instructions"] = "";
        $sql = "";
        $db = new DbConn();
        $db->isSelect = TRUE;
        $camper_id = getCamperId();
        if ($camper_id === NULL) {
            // If the camper does not have an ID yet, then this is a newly-added
            // camper.  We expect their name to be in the SESSION array.
            $nameMap["name"] = $_SESSION["first"];
            $sql = "SELECT pref_page_instructions instructions from admin_data";
        } else {
            $sql = "SELECT c.first name, a.pref_page_instructions instructions from campers c, admin_data a where c.camper_id = ?";
            $db->addColVal($camper_id, 'i');
        }
        $err = "";
        $result = $db->doQuery($sql, $err);
        if ($result) {
            $row = $result->fetch_assoc();
            $nameMap["instructions"] = $row["instructions"];
            if ($camper_id !== NULL) {
                $nameMap["name"] = $row["name"];
            }
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        
        echo json_encode($nameMap);
        exit();
    }
    
    // Get the chug lists corresponding to a camper's edah and session.  When we select, sort
    // July ahead of August.
    if (isset($_POST["get_chug_info"])) {
        $camper_id = getCamperId();
        $db = new DbConn();
        $db->isSelect = TRUE;
        $sql = "";
        if ($camper_id) {
            // If we have a camper ID, use it to grab the chug lists.
            $sql = "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, c.description chugdesc " .
            "FROM " .
            "campers cm, block_instances bi, blocks b, chug_instances ci, chugim c, groups g, edot_for_chug e, edot_for_block eb " .
            "WHERE " .
            "cm.camper_id = ? AND " .
            "cm.session_id = bi.session_id AND " .
            "bi.block_id = b.block_id AND " .
            "b.block_id = ci.block_id AND " .
            "ci.chug_id = c.chug_id AND " .
            "e.chug_id = c.chug_id AND " .
            "e.edah_id = cm.edah_id AND " .
            "eb.edah_id = cm.edah_id AND " .
            "eb.block_id = b.block_id AND " .
            "c.group_id = g.group_id ";
            $db->addColVal($camper_id, 'i');
        } else {
            // If we do not have a camper ID, deduce the chug lists from the edah and session,
            // both of which we expect to be present in the session array.
            if (! (array_key_exists("session_id", $_SESSION) &&
                   array_key_exists("edah_id", $_SESSION))) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => "New camper missing session or edah ID")));
            }
            $session_id = $_SESSION["session_id"];
            $edah_id = $_SESSION["edah_id"];
            $sql = "SELECT b.name blockname, g.name groupname, c.name chugname, c.chug_id chug_id, c.description chugdesc " .
            "FROM " .
            "block_instances bi, blocks b, chug_instances ci, chugim c, groups g, edot_for_chug e, edot_for_block eb " .
            "WHERE " .
            "bi.session_id = ? AND " .
            "bi.block_id = b.block_id AND " .
            "b.block_id = ci.block_id AND " .
            "ci.chug_id = c.chug_id AND " .
            "e.chug_id = c.chug_id AND " .
            "e.edah_id = ? AND " .
            "eb.edah_id = e.edah_id AND " .
            "eb.block_id = b.block_id AND " .
            "c.group_id = g.group_id ";
            $db->addColVal($session_id, 'i');
            $db->addColVal($edah_id, 'i');
        }
        // Order July ahead of August, for UI clarity.
        $sql .= "ORDER BY CASE WHEN (blockname LIKE 'July%' OR blockname LIKE 'july%') THEN CONCAT('a', blockname) ".
        "WHEN (blockname LIKE 'Aug%' OR blockname LIKE 'aug%') THEN CONCAT('b', blockname) ELSE blockname END, ".
        "groupname, chugname";
        $err = "";
        $result = $db->doQuery($sql, $err);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
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

        echo json_encode($dataToJson);
        exit();
    }

?>
