<?php
    session_start();
    include_once 'assignment.php'; // Includes functions and classes.
    include_once 'dbConn.php';
    
    // Require admin login for these functions.
    if (! adminLoggedIn()) {
        exit();
    }
    
    header("content-type:application/json");
    
    function getDbResult($sql) {
        $db = new DbConn();
        $err = "";
        $result = $db->runQueryDirectly($sql, $err);
        if ($result == FALSE) {
            error_log("ERROR: failed to execute SQL: $sql, $err");
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        return $result;
    }
    
    function getPrefListsForCampersByGroup($edah_id, $block_id, &$camperId2Name) {
        $db = new DbConn();
        $db->isSelect = TRUE;
        $db->addColVal($edah_id, 'i');
        $db->addColVal($block_id, 'i');
        $sql = "SELECT camper_id, first, last FROM campers c, block_instances b WHERE c.edah_id = ? AND " .
        "c.session_id = b.session_id and b.block_id = ?";
        $err = "";
        $result = $db->doQuery($sql, $err);
        if ($result == FALSE) {
            error_log("WARNING: getPrefLists failed: $err");
            return;
        }
        $camperInString = "(";
        $rc = mysqli_num_rows($result);
        if ($rc == 0) {
            // No campers in this edah.
            return;
        }
        $i = 0;
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            // Map camper ID to full name (remember that the latter might not
            // be unique).
            $camperId2Name[intval($row[0])] = $row[1] . " " . $row[2];
            // Build a parenthesized CSV of camper IDs to pass to the preferences
            // query, below.
            $camperInString .= $row[0];
            if (++$i < $rc) {
                $camperInString .= ",";
            }
        }
        $camperInString .= ")";
        
        $camperId2Group2PrefList = array();
        $keylist = array("first_choice_id", "second_choice_id", "third_choice_id", "fourth_choice_id", "fifth_choice_id", "sixth_choice_id");
        $db = new DbConn();
        $db->isSelect = TRUE;
        $db->addColVal($block_id, 'i');
        // Note that $camperInString does not need to be parameterized, because we build it ourselves above.
        $sql = "SELECT * FROM preferences WHERE block_id = ? AND camper_id IN $camperInString ORDER BY group_id";
        $err = "";
        $result = $db->doQuery($sql, $err);
        if ($result == FALSE) {
            error_log("Preferences select failed: $err");
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        while ($row = $result->fetch_assoc()) {
            $group_id = intval($row["group_id"]);
            $camper_id = intval($row["camper_id"]);
            if (! array_key_exists($camper_id, $camperId2Group2PrefList)) {
                $camperId2Group2PrefList[$camper_id] = array();
            }
            if (! array_key_exists($group_id, $camperId2Group2PrefList[$camper_id])) {
                $camperId2Group2PrefList[$camper_id][$group_id] = array();
            }
            foreach ($keylist as $colKey) {
                if (! array_key_exists($colKey, $row) ||
                    $row[$colKey] == "NULL") {
                    continue; // No preference
                }
                // {camper ID -> {group ID -> (ordered list of preferred chug IDs)}}
                array_push($camperId2Group2PrefList[$camper_id][$group_id], intval($row[$colKey]));
            }
        }
        
        return $camperId2Group2PrefList;
    }
    
    // Save changes to the DB.  The "assignments" object is an associative array
    // of the form:
    // assignments[groupId][chugId] = (list of matched camper IDs)
    // We will delete and then insert into the matches table.  We'll also update
    // the assignments table, based on the pref list for each camper, and the
    // min/max for each chug.
    if (isset($_POST["save_changes"])) {
        $edah_id = $_POST["edah"];
        $block_id = $_POST["block"];
        $assignments = $_POST["assignments"];
        
        // First, grab the pref lists, and map camper ID to name.
        $camperId2Name = array();
        $camperId2Group2PrefList = getPrefListsForCampersByGroup($edah_id, $block_id, $camperId2Name);
        
        // Step through the assignments for each group, and update the assignments
        // and matches tables.
        foreach ($assignments as $groupId => $chugId2MatchList) {
            $firstCt = 0;
            $secondCt = 0;
            $thirdCt = 0;
            $fourthOrWorseCt = 0;
            
            // Grab chug limits, and create Chug objects.
            $chugId2Chug = array();
            $db = new DbConn();
            $db->isSelect = TRUE;
            $db->addColVal($groupId, 'i');
            $db->addColVal($edah_id, 'i');
            $sql = "SELECT * FROM chugim c, edot_for_chug e where c.group_id = ? AND c.chug_id = e.chug_id AND e.edah_id = ?";
            $err = "";
            $result = $db->doQuery($sql, $err);
            if ($result == FALSE) {
                error_log("Chug limit select failed: $err");
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
            while ($row = mysqli_fetch_assoc($result)) {
                $c = new Chug($row["name"], $row["max_size"], $row["min_size"], $row["chug_id"]);
                $c->group_id = intval($row["group_id"]);
                $chugId2Chug[intval($row["chug_id"])] = $c;
            }
            
            foreach ($chugId2MatchList as $chugId => $matchedCamperList) {
                if (! array_key_exists($chugId, $chugId2Chug)) {
                    error_log("WARNING: No chug found matching ID $chugId");
                    continue;
                }
                $chugRef =& $chugId2Chug[$chugId];
                foreach ($matchedCamperList as $camperId) {
                    $chugRef->assigned_count++;
                    $prefListForGroup = NULL;
                    $camperName = $camperId2Name[$camperId];
                    if (! array_key_exists($camperId, $camperId2Group2PrefList)) {
                        error_log("WARNING: No prefs for camper " . $camperName);
                    } else {
                        $group2PrefList = $camperId2Group2PrefList[$camperId];
                        if (! array_key_exists($groupId, $group2PrefList)) {
                            error_log("WARNING: No prefs for group " . $groupId . " for camper " . $camperName);
                        } else {
                            $prefListForGroup = $group2PrefList[$groupId];
                        }
                    }
                    if ($prefListForGroup != NULL) {
                        $i = 0;
                        foreach ($prefListForGroup as $prefChugId) {
                            $i++;
                            if ($prefChugId == $chugId) {
                                break;
                            }
                        }
                        if ($i == 1) {
                            $firstCt++;
                        } else if ($i == 2) {
                            $secondCt++;
                        } else if ($i == 3) {
                            $thirdCt++;
                        } else {
                            $fourthOrWorseCt++;
                        }
                    }
                    // First, delete the existing match for this block and group for this camper, if any.
                    $err = "";
                    $db = new DbConn();
                    $db->isSelect = TRUE;
                    $db->addColVal($block_id, 'i');
                    $db->addColVal($camperId, 'i');
                    $db->addColVal($groupId, 'i');
                    $sql = "SELECT m.chug_instance_id existing_instance_id from matches m, chug_instances i, chugim c WHERE " .
                    "i.chug_instance_id = m.chug_instance_id AND i.block_id = ? AND m.camper_id = ? " .
                    "AND c.chug_id = i.chug_id AND c.group_id = ?";
                    $result = $db->doQuery($sql, $err);
                    if ($result == FALSE) {
                        error_log("Existing instance ID select failed: $err");
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => $err)));
                    }
                    $row = $result->fetch_assoc();
                    $existingChugInstanceId = $row["existing_instance_id"];
                    $db = new DbConn();
                    $db->addWhereColumn("camper_id", $camperId, 'i');
                    $db->addWhereColumn("chug_instance_id", $existingChugInstanceId, 'i');
                    if (! $db->deleteFromTable("matches", $err)) {
                        error_log("Unable to delete existing match: $err");
                        exit;
                    }
                    // Next, get the instance ID for this chug in this block and group.
                    $db = new DbConn();
                    $db->isSelect = TRUE;
                    $db->addColVal($chugId, 'i');
                    $db->addColVal($block_id, 'i');
                    $db->addColVal($groupId, 'i');
                    $sql = "SELECT i.chug_instance_id new_instance_id from chug_instances i, chugim c WHERE " .
                    "i.chug_id = c.chug_id AND c.chug_id = ? AND i.block_id = ? AND c.group_id = ?";
                    $result = $db->doQuery($sql, $err);
                    if ($result == FALSE) {
                        error_log("Chug instance ID select failed: $err");
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => $err)));
                    }
                    $row = $result->fetch_assoc();
                    $newInstanceId = $row["new_instance_id"];
                    // Finally, insert the new instance into the matches table.
                    $db = new DbConn();
                    $db->addColumn("camper_id", $camperId, 'i');
                    $db->addColumn("chug_instance_id", $newInstanceId, 'i');
                    if (! $db->insertIntoTable("matches", $err)) {
                        error_log("Unable to insert match: $err");
                        header('HTTP/1.1 500 Internal Server Error');
                        die(json_encode(array("error" => $err)));
                    }
                }
            }
            // Compute assignment stats, and update the assignments table.
            $underMin = "";
            $overMax = "";
            overUnder(array_values($chugId2Chug), $underMin, $overMax);

            // Update the assignment table (metadata about this assignment) with our stats.
            $db = new DbConn();
            $db->addWhereColumn("edah_id", $edah_id, 'i');
            $db->addWhereColumn("block_id", $block_id, 'i');
            $db->addWhereColumn("group_id", $groupId, 'i');
            if (! $db->deleteFromTable("assignments", $err)) {
                error_log("Unable to delete existing assignment: $err");
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
            $db = new DbConn();
            $db->addColumn("edah_id", $edah_id, 'i');
            $db->addColumn("block_id", $block_id, 'i');
            $db->addColumn("group_id", $groupId, 'i');
            $db->addColumn("first_choice_ct", $firstCt, 'i');
            $db->addColumn("second_choice_ct", $secondCt, 'i');
            $db->addColumn("third_choice_ct", $thirdCt, 'i');
            $db->addColumn("fourth_choice_or_worse_ct", $fourthOrWorseCt, 'i');
            $db->addColumn("under_min_list", $underMin, 's');
            $db->addColumn("over_max_list", $overMax, 's');
            if (! $db->insertIntoTable("assignments", $err)) {
                error_log("Unable to insert new assignment stats: $err");
                exit;
            }
        }
        
        $retVal["ok"] = 1;
        echo json_encode($retVal);
        exit;
    }
    
    // Grab match, chug, and preference info, for display on the main leveling
    // page.
    if (isset($_POST["matches_and_prefs"])) {
        $edah_id = $_POST["edah_id"];
        $block_id = $_POST["block_id"];
        
        $edah_name = "";
        $block_name = "";
        $err = "";
        $db = new DbConn();
        $db->addSelectColumn("name");
        $db->addWhereColumn("edah_id", $edah_id, 'i');
        $result = $db->simpleSelectFromTable("edot", $err);
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $edah_name = $row[0];
        }
        $db = new DbConn();
        $db->addSelectColumn("name");
        $db->addWhereColumn("block_id", $block_id, 'i');
        $result = $db->simpleSelectFromTable("blocks", $err);
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $block_name = $row[0];
        }
        
        // Grab existing matches for all campers.  We'll use this to warn
        // if the user moves a camper into a duplicate chug.
        $existingMatches = array();
        $db = new DbConn();
        $db->isSelect = TRUE;
        $sql = "SELECT m.camper_id, c.chug_id, b.name  " .
        "FROM matches m, chugim c, chug_instances i, blocks b " .
        "WHERE m.chug_instance_id = i.chug_instance_id " .
        "AND i.chug_id = c.chug_id " .
        "AND i.block_id = b.block_id";
        $result = $db->doQuery($sql, $err);
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $camper_id = $row[0];
            $chug_id = $row[1];
            $block_name = $row[2];
            if (! array_key_exists($camper_id, $existingMatches)) {
                $existingMatches[$camper_id] = array();
            }
            $existingMatches[$camper_id][$chug_id] = $block_name;
        }
        
        // Compute the de-dup matrix, which we'll use to warn if the user
        // moves a camper to a duplicate chug.
        $deDupMatrix = array();
        $db = new DbConn();
        $db->addSelectColumn("*");
        $result = $db->simpleSelectFromTable("chug_dedup_instances_v2", $err);
        if ($result == FALSE) {
            error_log($err);
            return FALSE;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $leftChug = $row["left_chug_id"];
            $rightChug = $row["right_chug_id"];
            if (! array_key_exists($leftChug, $deDupMatrix)) {
                $deDupMatrix[$leftChug] = array();
            }
            if (! array_key_exists($rightChug, $deDupMatrix)) {
                $deDupMatrix[$rightChug] = array();
            }
            $deDupMatrix[$leftChug][$rightChug] = 1;
            $deDupMatrix[$rightChug][$leftChug] = 1;
        }
        
        // Get preferences (as strings) for these campers.
        // First, map chug ID to name and min/max.  Also, define an "unassigned"
        // chug, which we will use to flag campers still needing assignment.
        $maxIndex = 0;
        $chugId2Beta = array();
        $result = getDbResult("SELECT c.chug_id, c.name, c.min_size, c.max_size, g.name " .
                              "FROM chugim c, groups g WHERE c.group_id = g.group_id");
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $chugId2Beta[intval($row[0])] = array();
            $chugId2Beta[intval($row[0])]["name"] = $row[1];
            $chugId2Beta[intval($row[0])]["min_size"] = $row[2];
            $chugId2Beta[intval($row[0])]["max_size"] = $row[3];
            $chugId2Beta[intval($row[0])]["group_name"] = $row[4];
            $chugId2Beta[intval($row[0])]["free"] = 0; // default
            if (intval($row[0]) > $maxIndex) {
                $maxIndex = intval($row[0]);
            }
        }
        $unAssignedIndex = $maxIndex + 1;
        $chugId2Beta[$unAssignedIndex]["name"] = "Not Assigned Yet";
        $chugId2Beta[$unAssignedIndex]["min_size"] = 0;
        $chugId2Beta[$unAssignedIndex]["max_size"] = 0;
        
        // Check the matches table and compute how much space is left in each
        // chug for this block.  For chugim with space, record it in $chugId2Beta.
        $db = new DbConn();
        $db->addColVal($block_id, 'i');
        $sql = "SELECT a.chug_id chug_id, a.max_size max_size, sum(a.matched) num_matched " .
        "FROM (SELECT c.chug_id, c.max_size max_size, CASE WHEN m.matched_chug_id IS NULL THEN 0 ELSE 1 END matched " .
        "FROM chugim c LEFT OUTER JOIN (SELECT i.chug_id matched_chug_id FROM chug_instances i, matches m " .
        "WHERE i.chug_instance_id = m.chug_instance_id  AND i.block_id = ?) m " .
        "ON c.chug_id = m.matched_chug_id) a " .
        "GROUP BY chug_id";
        $result = $db->doQuery($sql, $err);
        if ($result == FALSE) {
            // For now, just log a warning, since this is only used for informational
            // display.
            error_log("WARNING: Failed to select current chug-full status: $err");
        } else {
            while ($row = $result->fetch_assoc()) {
                $idVal = intval($row["chug_id"]);
                if (! array_key_exists($idVal, $chugId2Beta)) {
                    continue;
                }
                $assigned = intval($row["num_matched"]);
                $capacity = intval($row["max_size"]);
                if ($capacity == 0) {
                    $chugId2Beta[$idVal]["free"] = "unlimited";
                } else if ($assigned < $capacity) {
                    $chugId2Beta[$idVal]["free"] = $capacity - $assigned;
                }
            }
        }

        // Next, map camper ID to an ordered list of preferred chugim, by
        // group ID.  Also, map camper ID to name.
        $camperId2Name = array();
        $camperId2Group2PrefList = getPrefListsForCampersByGroup($edah_id, $block_id, $camperId2Name);

        // Loop through groups, fetching matches as we go.
        $db = new DbConn();
        $db->addColVal($edah_id, 'i');
        $sql = "SELECT g.group_id group_id, g.name name FROM groups g, edot_for_group e " .
        "WHERE g.group_id = e.group_id AND e.edah_id = ?";
        $result = $db->doQuery($sql, $dbErr);
        if ($result == FALSE) {
            error_log("Unable to select groups: $err");
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        $groupId2Name = array();
        $groupId2ChugId2MatchedCampers = array();
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $unAssignedCampersInThisGroup = $camperId2Name; // Make a copy
            $group_id = intval($row[0]);
            $group_name = $row[1];
            $groupId2Name[$group_id] = $group_name;
            if (! array_key_exists($group_id, $groupId2ChugId2MatchedCampers)) {
                $groupId2ChugId2MatchedCampers[$group_id] = array();
            }
            // Get all chugim for this group/edah, and make an array entry.
            $db = new DbConn();
            $db->isSelect = TRUE;
            $db->addColVal($group_id, 'i');
            $db->addColVal($block_id, 'i');
            $db->addColVal($edah_id, 'i');
            $err = "";
            $result2 = $db->doQuery("SELECT c.chug_id chug_id FROM chugim c, chug_instances i, edot_for_chug ec " .
                                    "WHERE c.group_id = ? AND i.block_id = ? AND c.chug_id = i.chug_id " .
                                    "AND ec.chug_id = c.chug_id AND ec.edah_id = ?", $err);
            if ($result2 == FALSE) {
                error_log("Unable to select chugim: $err");
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
            while ($row2 = mysqli_fetch_row($result2)) {
                $chug_id = intval($row2[0]);
                $groupId2ChugId2MatchedCampers[$group_id][$chug_id] = array();
            }
            $groupId2ChugId2MatchedCampers[$group_id][$unAssignedIndex] = array();
            // Get matches for this group/block/edah/session.
            $db = new DbConn();
            $db->isSelect = TRUE;
            $db->addColVal($group_id, 'i');
            $db->addColVal($edah_id, 'i');
            $db->addColVal($block_id, 'i');
            $err = "";
            $sql = "SELECT m.camper_id, ch.chug_id FROM matches m, campers c, block_instances b, chugim ch, chug_instances i " .
            "WHERE i.block_id = b.block_id AND ch.chug_id = i.chug_id " .
            "AND ch.group_id = ? AND m.chug_instance_id = i.chug_instance_id " .
            "AND m.camper_id = c.camper_id AND c.edah_id = ? " .
            "AND b.block_id = ? AND b.session_id = c.session_id";
            $result3 = $db->doQuery($sql, $err);
            if ($result3 == FALSE) {
                error_log("Unable to select matches: $err");
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
            while ($row3 = mysqli_fetch_row($result3)) {
                $camper_id = intval($row3[0]);
                $chug_id = intval($row3[1]);
                if (! array_key_exists($chug_id, $groupId2ChugId2MatchedCampers[$group_id])) {
                    // This can happen, e.g., if we assign a chug to a different group.  It's important
                    // not to reassign chugim once campers have started selecting (need to figure out which
                    // changes we can handle).
                    error_log("WARNING: Found camper match to chug ID $chug_id, not in group $group_id");
                    continue;
                }
                array_push($groupId2ChugId2MatchedCampers[$group_id][$chug_id], $camper_id);
                unset($unAssignedCampersInThisGroup[$camper_id]); // Remove from unassigned list.
            }
            // If any campers are in the unassigned list, add them in the unassigned grouping.
            foreach ($unAssignedCampersInThisGroup as $unAssignedId => $unAssignedName) {
                error_log("No match found for $unAssignedName for $group_name - flagging as unassigned");
                array_push($groupId2ChugId2MatchedCampers[$group_id][$unAssignedIndex], $unAssignedId);
            }
            if (count($groupId2ChugId2MatchedCampers[$group_id][$unAssignedIndex]) == 0 ||
                count($groupId2ChugId2MatchedCampers[$group_id]) == 1) {
                // Do not display unassigned campers if:
                // a) We don't have any, or
                // b) There were no available chugim.
                unset($groupId2ChugId2MatchedCampers[$group_id][$unAssignedIndex]);
            }
        }
        $retVal = array();
        $retVal["camperId2Group2PrefList"] = $camperId2Group2PrefList; // {Camper ID -> {Group ID->(Chug ID pref list)}}
        $retVal["groupId2ChugId2MatchedCampers"] = $groupId2ChugId2MatchedCampers; // {Group ID->{Chug ID->(Matched camper ID list - might be empty)}}
        $retVal["groupId2Name"] = $groupId2Name; // {Group ID -> Group Name}
        $retVal["camperId2Name"] = $camperId2Name; // {Camper ID -> Camper Name}
        $retVal["chugId2Beta"] = $chugId2Beta;   // {Chug ID -> Chug Name, Min and Max}
        $retVal["edahName"] = $edah_name;
        $retVal["blockName"] = $block_name;
        $retVal["existingMatches"] = $existingMatches;
        $retVal["deDupMatrix"] = $deDupMatrix;
        
        echo json_encode($retVal);
        exit();
    }
    
    // Get the names for an edah and block.
    if (isset($_POST["names_for_id"])) {
        $edah_id = $_POST["edah_id"];
        $block_id = $_POST["block_id"];
        $edah_name = "";
        $block_name = "";
        $db = new DbConn();
        $db->isSelect = TRUE;
        $db->addSelectColumn("name");
        $db->addWhereColumn("edah_id", $edah_id, 'i');
        $err = "";
        $result = $db->simpleSelectFromTable("edot", $err);
        if ($result == FALSE) {
            error_log("Unable to select edah: $err");
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        if ($result->num_rows) {
            $row = $result->fetch_row();
            $edah_name = $row[0];
        }
        $db = new DbConn();
        $db->isSelect = TRUE;
        $db->addSelectColumn("name");
        $db->addWhereColumn("block_id", $block_id, 'i');
        $result = $db->simpleSelectFromTable("blocks", $err);
        if ($result == FALSE) {
            error_log("Unable to select block: $err");
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        if ($result->num_rows) {
            $row = $result->fetch_row();
            $block_name = $row[0];
        }
        $retVal = array(
                        'edahName' => $edah_name,
                        'blockName' => $block_name
                        );
        echo json_encode($retVal);
        exit();
    }
    
    // Get the assignment stats, first running the assignment algorithm if
    // requested.
    if (isset($_POST["reassign"]) ||
        isset($_POST["get_current_stats"])) {
        $err = "";
        $edah_id = $_POST["edah"];
        $block_id = $_POST["block"];
    
        $db = new DbConn();
        $db->addColVal($edah_id, 'i');
        $sql = "SELECT g.group_id group_id, g.name name FROM groups g, edot_for_group e " .
        "WHERE g.group_id = e.group_id AND e.edah_id = ?";
        $result = $db->doQuery($sql, $err);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        
        // Loop through groups.  Do each assignment (if requested), and grab assignment
        // stats.
        $choiceCounts = array();
        $stats = array();
        $sKeys = array("under_min_list", "over_max_list");
        $choiceKeys = array("first_choice_ct", "second_choice_ct", "third_choice_ct", "fourth_choice_or_worse_ct");
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $group_id = intval($row[0]);
            $group_name = $row[1];
            if (isset($_POST["reassign"])) {
                $ok = do_assignment($edah_id, $block_id, $group_id, $err);
                if (! $ok) {
                    header('HTTP/1.1 500 Internal Server Error');
                    die(json_encode(array("error" => $err)));
                }
            }
            $db = new DbConn();
            $db->isSelect = TRUE;
            $db->addSelectColumn("*");
            $db->addWhereColumn("edah_id", $edah_id, 'i');
            $db->addWhereColumn("group_id", $group_id, 'i');
            $db->addWhereColumn("block_id", $block_id, 'i');
            $err = "";
            $result2 = $db->simpleSelectFromTable("assignments", $err);
            if ($result2 == FALSE) {
                error_log("Unable to select assignment: $err");
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
            $row = mysqli_fetch_assoc($result2);
            if ($row === NULL) {
                continue;
            }
            // Increment choice counts
            foreach ($choiceKeys as $choiceKey) {
                if ($row["$choiceKey"] != NULL) {
                    if (! array_key_exists($choiceKey, $choiceCounts)) {
                        $choiceCounts[$choiceKey] = intval($row["$choiceKey"]);
                    } else {
                        $choiceCounts[$choiceKey] += intval($row["$choiceKey"]);
                    }
                }
            }
            // Note under-min and over-max chugim.
            foreach ($sKeys as $key) {
                if (array_key_exists($key, $row) &&
                    (! empty($row[$key]))) {
                    $stats[$key] .= "<br>" . $group_name . ": " . $row[$key];
                }
            }
        }
        $statstxt = "";
        for ($i = 0; $i < count($choiceKeys); $i++) {
            $choice = $i + 1;
            if ($choice == 4) {
                $choice .= " or worse";
            }
            $cKey = $choiceKeys[$i];
            $statstxt .= "Choice $choice count: <b>" . $choiceCounts[$cKey] . "</b><br>";
        }
        foreach ($sKeys as $key) {
            if (! array_key_exists($key, $stats)) {
                $stats[$key] = "none";
            }
        }
        $stats["statstxt"] = $statstxt;
        
        echo json_encode($stats);
        
        exit;
    }
    
    
    ?>
