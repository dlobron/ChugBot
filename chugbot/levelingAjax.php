<?php
    session_start();
    include 'assignment.php';
    header("content-type:application/json");
    
    function getDbResult($sql) {
        $mysqli = connect_db();
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database error")));
        }
        $mysqli->close();
        return $result;
    }
    
    // Grab match, chug, and preference info, for display on the main leveling
    // page.
    if (isset($_POST["matches_and_prefs"])) {
        $edah_id = $_POST["edah_id"];
        $block_id = $_POST["block_id"];
        
        // Grab the campers in this edah.
        $camperId2Name = array();
        $result = getDbResult("SELECT camper_id, first, last FROM campers WHERE edah_id = $edah_id");
        $camperInString = "(";
        $rc = mysqli_num_rows($result);
        $i = 0;
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
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
        
        // Get preferences (as strings) for these campers.
        // First, map chug ID to name, by group.  We'll use this in a few places
        // below.
        $groupId2ChugId2Name = array();
        $result = getDbResult("SELECT group_id, chug_id, name FROM chugim");
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $group_id = intval($row[0]);
            $chug_id = intval($row[1]);
            if (! array_key_exists($group_id, $groupId2ChugId2Name)) {
                $groupId2ChugId2Name[$group_id] = array();
            }
            $groupId2ChugId2Name[$group_id][$chug_id] = $row[2]; // {group ID -> (chug ID -> name, ...), ...}
        }
        
        // Next, map camper ID to an ordered list of preferred chugim, by
        // group Id.
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
                if ($row[$colKey] == "NULL") {
                    continue;
                }
                // {camper ID -> {group ID -> (ordered list of preferred chug names)}}
                array_push($camperId2Group2PrefList[$camper_id][$group_id], intval($row[$colKey]));
            }
        }
        // Loop through groups, fetching matches as we go.
        $result = getDbResult("SELECT group_id, name FROM groups");
        $groupId2Name = array();
        $groupId2ChugId2MatchedCampers = array();
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $group_id = intval($row[0]);
            $group_name = $row[1];
            $groupId2Name[$group_id] = $group_name;
            if (! array_key_exists($group_id, $groupId2ChugId2MatchedCampers)) {
                $groupId2ChugId2MatchedCampers[$group_id] = array();
            }
            $result2 = getDbResult("SELECT camper_id, chug_id FROM matches WHERE block_id = $block_id and group_id = $group_id");
            while ($row2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
                $camper_id = intval($row2[0]);
                $chug_id = intval($row2[1]);
                if (! array_key_exists($chug_id, $groupId2ChugId2MatchedCampers[$group_id])) {
                    $groupId2ChugId2MatchedCampers[$group_id][$chug_id] = array();
                }
                array_push($groupId2ChugId2MatchedCampers[$group_id][$chug_id], $camper_id);
            }
        }
        $retVal = array();
        $retVal["groupId2ChugId2Name"] = $groupId2ChugId2Name;
        $retVal["camperId2Group2PrefList"] = $camperId2Group2PrefList;
        $retVal["groupId2ChugId2MatchedCampers"] = $groupId2ChugId2MatchedCampers;
        $retVal["groupId2Name"] = $groupId2Name;
        
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
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
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
                if (! empty($row[$key]) ) {
                    if (array_key_exists($key, $stats)) {
                        $stats[$key] .= ", " . $row[$key];
                    } else {
                        $stats[$key] = $row[$key];
                    }
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
