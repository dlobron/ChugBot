<?php
    session_start();
    include_once 'assignment.php'; // Includes functions and classes.
    header("content-type:application/json");
    
    function getDbResult($sql) {
        $mysqli = connect_db();
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            error_log("ERROR: failed to execute SQL: $sql");
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database error")));
        }
        $mysqli->close();
        return $result;
    }
    
    function getPrefListsForCampersByGroup($edah_id, $block_id, &$camperId2Name) {
        $sql = "SELECT camper_id, first, last FROM campers c, block_instances b WHERE c.edah_id = $edah_id AND " .
        "c.session_id = b.session_id and b.block_id = $block_id";
        $result = getDbResult($sql);
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
        $result = getDbResult("SELECT * FROM preferences WHERE block_id = $block_id AND camper_id IN $camperInString ORDER BY group_id");
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
            $result = getDbResult("SELECT * FROM chugim c, edot_for_chug e where c.group_id = $groupId AND c.chug_id = e.chug_id AND e.edah_id = $edah_id");
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
                    $sql = "SELECT m.chug_instance_id existing_instance_id from matches m, chug_instances i, chugim c WHERE " .
                    "i.chug_instance_id = m.chug_instance_id AND i.block_id = $block_id AND m.camper_id = $camperId " .
                    "AND c.chug_id = i.chug_id AND c.group_id = $groupId";
                    $result = getDbResult($sql);
                    $row = $result->fetch_assoc();
                    $existingChugInstanceId = $row["existing_instance_id"];
                    $sql = "DELETE FROM matches WHERE camper_id = $camperId AND " .
                    "chug_instance_id = $existingChugInstanceId";
                    getDbResult($sql);
                    // Next, get the instance ID for this chug in this block and group.
                    $sql = "SELECT i.chug_instance_id new_instance_id from chug_instances i, chugim c WHERE " .
                    "i.chug_id = c.chug_id AND c.chug_id = $chugId AND i.block_id = $block_id AND c.group_id = $groupId";
                    $result = getDbResult($sql);
                    $row = $result->fetch_assoc();
                    $newInstanceId = $row["new_instance_id"];
                    // Finally, insert the new instance into the matches table.
                    $sql = "INSERT INTO matches (camper_id, chug_instance_id) " .
                    "VALUES ($camperId, $newInstanceId)";
                    getDbResult($sql);
                }
            }
            // Compute assignment stats, and update the assignments table.
            $underMin = "";
            $overMax = "";
            overUnder(array_values($chugId2Chug), $underMin, $overMax);

            // Update the assignment table (metadata about this assignment) with our stats.
            $sql = "DELETE FROM assignments WHERE edah_id = $edah_id AND " .
            "block_id = $block_id AND group_id = $groupId";
            getDbResult($sql);
            $sql = "INSERT INTO assignments (edah_id, block_id, group_id, first_choice_ct, second_choice_ct, third_choice_ct, " .
            "fourth_choice_or_worse_ct, under_min_list, over_max_list) " .
            "VALUES ($edah_id, $block_id, $groupId, $firstCt, $secondCt, $thirdCt, $fourthOrWorseCt, \"$underMin\", \"$overMax\")";
            getDbResult($sql);
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
        
        // Get preferences (as strings) for these campers.
        // First, map chug ID to name and min/max.  Also, define an "unassigned"
        // chug, which we will use to flag campers still needing assignment.
        $maxIndex = 0;
        $chugId2Beta = array();
        $result = getDbResult("SELECT chug_id, name, min_size, max_size FROM chugim");
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $chugId2Beta[intval($row[0])] = array();
            $chugId2Beta[intval($row[0])]["name"] = $row[1];
            $chugId2Beta[intval($row[0])]["min_size"] = $row[2];
            $chugId2Beta[intval($row[0])]["max_size"] = $row[3];
            if (intval($row[0]) > $maxIndex) {
                $maxIndex = intval($row[0]);
            }
        }
        $unAssignedIndex = $maxIndex + 1;
        $chugId2Beta[$unAssignedIndex]["name"] = "Not Assigned Yet";
        $chugId2Beta[$unAssignedIndex]["min_size"] = 0;
        $chugId2Beta[$unAssignedIndex]["max_size"] = 0;

        // Next, map camper ID to an ordered list of preferred chugim, by
        // group ID.  Also, map camper ID to name.
        $camperId2Name = array();
        $camperId2Group2PrefList = getPrefListsForCampersByGroup($edah_id, $block_id, $camperId2Name);

        // Loop through groups, fetching matches as we go.
        $result = getDbResult("SELECT group_id, name FROM groups");
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
            // Get all chugim for the group, and make an array entry.
            $result2 = getDbResult("SELECT c.chug_id chug_id FROM chugim c, chug_instances i WHERE c.group_id = $group_id AND i.block_id = $block_id AND c.chug_id = i.chug_id");
            while ($row2 = mysqli_fetch_row($result2)) {
                $chug_id = intval($row2[0]);
                $groupId2ChugId2MatchedCampers[$group_id][$chug_id] = array();
            }
            $groupId2ChugId2MatchedCampers[$group_id][$unAssignedIndex] =  array();
            // Get matches for this group/block/edah/session.
            $sql = "SELECT m.camper_id, ch.chug_id FROM matches m, campers c, block_instances b, chugim ch, chug_instances i " .
            "WHERE i.block_id = b.block_id AND ch.chug_id = i.chug_id " .
            "AND ch.group_id = $group_id AND m.chug_instance_id = i.chug_instance_id " .
            "AND m.camper_id = c.camper_id AND c.edah_id = $edah_id " .
            "AND b.block_id = $block_id AND b.session_id = c.session_id";
            $result3 = getDbResult($sql);
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
            if (count($groupId2ChugId2MatchedCampers[$group_id][$unAssignedIndex]) == 0) {
                // Don't display the unassigned chug unless we have at least one of them.
                unset($groupId2ChugId2MatchedCampers[$group_id][$unAssignedIndex]);
            }
        }
        $retVal = array();
        $retVal["camperId2Group2PrefList"] = $camperId2Group2PrefList; // {Camper ID -> {Group ID->(Chug ID pref list)}}
        $retVal["groupId2ChugId2MatchedCampers"] = $groupId2ChugId2MatchedCampers; // {Group ID->{Chug ID->(Matched camper ID list - might be empty)}}
        $retVal["groupId2Name"] = $groupId2Name; // {Group ID -> Group Name}
        $retVal["camperId2Name"] = $camperId2Name; // {Camper ID -> Camper Name}
        $retVal["chugId2Beta"] = $chugId2Beta;   // {Chug ID -> Chug Name, Min and Max}
        
        echo json_encode($retVal);
        exit();
    }
    
    // Get the names for an edah and block.
    if (isset($_POST["names_for_id"])) {
        $edah_id = $_POST["edah_id"];
        $block_id = $_POST["block_id"];
        $edah_name = "";
        $block_name = "";
        $result = getDbResult("SELECT name FROM edot where edah_id=$edah_id");
        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $edah_name = $row[0];
        }
        $result = getDbResult("SELECT name FROM blocks where block_id=$block_id");
        if ($result->num_rows > 0) {
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
        $edah_id = $_POST["edah"];
        $block_id = $_POST["block"];
    
        $result = getDbResult("SELECT group_id, name FROM groups");
        // Loop through groups.  Do each assignment (if requested), and grab assignment
        // stats.
        $err = "";
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
            $result2 = getDbResult("SELECT * FROM assignments WHERE edah_id = $edah_id AND group_id = $group_id AND block_id = $block_id");
            $row = mysqli_fetch_assoc($result2);
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
