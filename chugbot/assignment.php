<?php
    include_once 'functions.php';
    include_once 'assignmentClasses.php';
    include_once 'dbConn.php';
    
    // Require camper-level permission to run any of these functions.
    if (! camperLoggedIn()) {
        exit();
    }
    
    function assign($camper, &$assignments, &$chugToAssign) {
        $assignments[$camper->camper_id] = $chugToAssign->chug_id;
        $chugToAssign->assigned_count++;
        debugLog("Assigned camper ID " . $camper->camper_id . " to chug ID " . $chugToAssign->chug_id . ", name " . $chugToAssign->name);
    }
    
    function unassign($camper, &$assignments, &$chugToUnassign) {
        unset($assignments[$camper->camper_id]);
        $chugToUnassign->assigned_count--;
    }
    
    function spaceInNextPref($assignments, $chugId2Chug, $camper) {
        // Note the first chug in this camper's pref list, and compute the
        // space remaining in it per assignments compared to its max.
        if (count($camper->prefs) == 0) {
            return 0; // No preferences left.
        }
        if (empty($camper->prefs)) {
            return 0;
        }
        if (! array_key_exists($camper->prefs[0], $chugId2Chug)) {
            return 0;
        }
        $nextPrefChug = $chugId2Chug[$camper->prefs[0]]; // Look up by chug ID.
        $retVal = $nextPrefChug->max_size - $nextPrefChug->assigned_count;
        return ($retVal > 0) ? $retVal : 0; // The difference will be negative if oversubscribed.
    }
    
    function chugWithMostSpace($chugIds, $chugId2Chug) {
        $maxFreeSpace = 0;
        $maxFreeId = NULL;
        foreach ($chugIds as $chugId => $val) {
            if (! array_key_exists($chugId, $chugId2Chug)) {
                error_log("ERROR: No chug found for ID $chugId");
                return NULL;
            }
            $chug = $chugId2Chug[$chugId];
            // Always return a chug: if all chugim have the same free space, then
            // we'll return the first one in the list.  It's crucial that we always
            // return a chug, because it guarantees that the assignment loop will
            // finish.
            if ($maxFreeId == NULL) {
                $maxFreeId = $chugId;
            }
            if ($maxFreeSpace < ($chug->max_size - $chug->assigned_count)) {
                $maxFreeSpace = $chug->max_size - $chug->assigned_count;
                $maxFreeId = $chugId;
            }
        }

        return $maxFreeId;
    }
    
    function isDuplicate($candidateChug, $matchesForThisCamper, $deDupMatrix) {
        $candidateChugId = $candidateChug->chug_id;
        if (array_key_exists($candidateChugId, $deDupMatrix)) {
            // Grab the set of chugim with which the candidate chug may not
            // be duplicated.
            $forbiddenToDupSet = $deDupMatrix[$candidateChugId];
            foreach ($matchesForThisCamper as $existingMatch) {
                if (array_key_exists($existingMatch, $forbiddenToDupSet)) {
                    // We've found an existing match that appears in the
                    // candidate chug's de-dup list: this means the candidate
                    // is a duplicate and should not be assigned unless an
                    // override is in place.
                    return TRUE;
                }
            }
        }
        
        return FALSE;
    }
    
    // Reminder: if this camper has needs_first_choice set, then we can bump
    // any camper except those who need their first choice.  In this
    // latter case, we return false, and the caller should assign the camper anyway
    // (the caller tries this function first, because if a bump can be found, it's
    // better than overflowing the chug).
    function findHappierCamper($camper, $candidateChug, $ourAssignments,
                               $happiness, $chugId2Chug, $campers) {
        // First, get our happiness level and the space left in our next-choice
        // chug.
        debugLog("starting findHappierCamper for $camper->name");
        $ourHappiness = 0;
        if (array_key_exists($camper->camper_id, $happiness)) {
            $ourHappiness = $happiness[$camper->camper_id];
        }
        $minHappiness = $ourHappiness;
        $ourNextSpace = spaceInNextPref($ourAssignments, $chugId2Chug, $camper);
        $maxNextSpace = $ourNextSpace;
        debugLog("ourHappiness = $ourHappiness, ourNextSpace = $ourNextSpace");
        $happierCamperId = NULL;
        $mostFreeSpaceCamperId = NULL;
        $happiestCamperOfLotId = NULL;
        $minHappinessOfLot = MAX_SIZE_NUM;
        // Loop through existing assignments, and see if any camper assigned to
        // this chug is happier than we are.  If we have a tie, return the camper
        // with the most free space in their next choice, since they are hurt least
        // by being bumped.
        // Exceptions: campers with needs_first_choice cannot
        // be bumped, so we skip them when searching for a happier camper (a happier
        // camper is a bump candidate).
        // Note that we call this function in a loop, making our algorithm n^2.  I think
        // this is acceptable, given the relatively small size of inputs.  If speed
        // becomes an issue, we can revisit this to see if we can avoid looping over
        // all our assignments (I think we have to consider all campers assigned to this
        // chug, though, because we're looking for the best bumpout candidate).
        foreach ($ourAssignments as $otherCamperId => $assignedChugId) {
            if ($assignedChugId != $candidateChug->chug_id) {
                continue;
            }
            $otherCamper = $campers[$otherCamperId];
            debugLog("considering other camper assigned to this chug, $otherCamper->name");
            if ($otherCamper->needs_first_choice) {
                // Campers who need their first choice should never be bumped.
                debugLog("can't bump camper ID $otherCamperId because they need first choice");
                continue;
            }
            $theirHappiness = 0;
            if (array_key_exists($otherCamperId, $happiness)) {
                $theirHappiness = $happiness[$otherCamperId];
            }
            debugLog("their happiness = $theirHappiness");
            if ($theirHappiness < $minHappinessOfLot) {
                $happiestCamperOfLotId = $otherCamperId;
                $minHappinessOfLot = $theirHappiness;
                debugLog("happiest so far, with happiness of $theirHappiness");
            }
            if ($theirHappiness < $minHappiness) {
                // We've found a camper with a lower (better) happiness level.
                // Note this camper, and update the min.
                $happierCamperId = $otherCamperId;
                $minHappiness = $theirHappiness;
                debugLog("found camper $otherCamper->name with better (lower) happiness $theirHappiness - min is now $minHappiness");
            }
            $theirNextSpace = spaceInNextPref($ourAssignments, $chugId2Chug, $otherCamper);
            if ($theirNextSpace > $maxNextSpace) {
                $mostFreeSpaceCamperId = $otherCamperId;
                $maxNextSpace = $theirNextSpace;
                debugLog("found camper ID $otherCamper->name with more free space, $theirNextSpace");
            }
        }
        // If we have a happier camper, return their ID.  Otherwise, if we have
        // a camper with more free space, return their ID.  Otherwise, return NULL.
        // Note: it might be better to favor next-free-space over happiness: try
        // experimenting with both.
        if ($happierCamperId != NULL) {
            return $happierCamperId;
        } elseif ($mostFreeSpaceCamperId != NULL) {
            return $mostFreeSpaceCamperId;
        } elseif ($camper->needs_first_choice) {
            // Special case: if this camper needs their first choice, then we have
            // to bump, if someone is available.  Bump the happiest from our
            // input set.
            debugLog("No happier camper found, but $camper->name needs first choice, so bumping happiest available, with ID $happiestCamperOfLotId");
            return $happiestCamperOfLotId;
        } else {
            // No happier camper was found, and this camper does not require first
            // choice.
            return NULL;
        }
    }
    
    function do_assignment($edah_ids, $block_id, $group_id, &$err) {
        $edotText = "";
        debugLog("Starting do_assignment");
        // Get the names of our edot, block, and group, for logging and error printing.
        $edahId2Name = array();
        foreach ($edah_ids as $edah_id) {
            $db = new DbConn();
            $db->addSelectColumn("name");
            $db->addWhereColumn("edah_id", $edah_id, 'i');
            $result = $db->simpleSelectFromTable("edot", $err);
            if ($result == FALSE) {
                error_log($err);
                return FALSE;
            }
            $row = mysqli_fetch_assoc($result);
            if ($edotText) {
                $edotText .= "+";
            }
            $edotText .= $row["name"];
            $edahId2Name[$edah_id] = $row["name"];
        }
        $db = new DbConn();
        $db->addSelectColumn("name");
        $db->addWhereColumn("block_id", $block_id, 'i');
        $result = $db->simpleSelectFromTable("blocks", $err);
        if ($result == FALSE) {
            error_log($err);
            return FALSE;
        }
        $row = mysqli_fetch_assoc($result);
        $blockName = $row["name"];
        $db = new DbConn();
        $db->addSelectColumn("name");
        $db->addWhereColumn("group_id", $group_id, 'i');
        $result = $db->simpleSelectFromTable("groups", $err);
        if ($result == FALSE) {
            error_log($err);
            return FALSE;
        }
        $row = mysqli_fetch_assoc($result);
        $groupName = $row["name"];
        debugLog("Starting assignment loop for edah/ot $edotText, block $blockName, group $groupName");
        
        // Grab the campers in this edah and block, and prefs for this group.  We determine the
        // campers in a block by joining with the block_instances table, which tells us which
        // sessions overlap with our block (campers register for sessions, not blocks, so the campers
        // table only knows about sessions).
        // We use a left outer join on preferences because we want to include campers who do not
        // have any preferences in the system (staff might want to assign them manually).
        $campers = array();
        $camperIdsToAssign = array();
        $camperId2EdahId = array();
        foreach ($edah_ids as $edah_id) {
            $db = new DbConn();
            $db->addColVal($group_id, 'i');
            $db->addColVal($block_id, 'i');
            $db->addColVal($edah_id, 'i');
            $db->addColVal($block_id, 'i');
            $db->isSelect = TRUE;
            $sql = "SELECT c.camper_id, c.first, c.last, c.needs_first_choice, " .
            "prefs.fr firstpref, prefs.sc secpref, prefs.th thirdpref, prefs.frth fourthpref, prefs.ff fifthpref, prefs.sxth sixthpref " .
            "FROM block_instances b, campers c " .
            "LEFT OUTER JOIN " .
            "(SELECT p.group_id group_id, p.block_id block_id, p.camper_id camper_id, " .
            "IFNULL(p.first_choice_id,-1) fr, IFNULL(p.second_choice_id,-1) sc, " .
            "IFNULL(p.third_choice_id,-1) th, IFNULL(p.fourth_choice_id,-1) frth, " .
            "IFNULL(p.fifth_choice_id,-1) ff, IFNULL(p.sixth_choice_id,-1) sxth " .
            "FROM preferences p, block_instances b, campers c " .
            "WHERE c.camper_id = p.camper_id AND p.block_id = b.block_id AND c.session_id = b.session_id) prefs " .
            "ON prefs.group_id = ? AND prefs.block_id = ? AND prefs.camper_id = c.camper_id " .
            "WHERE c.edah_id = ? AND c.session_id = b.session_id AND b.block_id = ? AND c.inactive = 0";
            $result = $db->doQuery($sql, $err);
            if ($result == FALSE) {
                error_log($err);
                return FALSE;
            }
            while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                $c = new Camper($row[0], $row[1], $row[2], $row[3]);
                for ($i = 4; $i < count($row); $i++) {
                    if ($row[$i] >= 0) {
                        array_push($c->prefs, $row[$i]);
                    }
                }
                $camper_id = intval($row[0]);
                $campers[$camper_id] = $c;
                array_push($camperIdsToAssign, $camper_id);
                $camperId2EdahId[$camper_id] = $edah_id;
            }
        }
        if (count($campers) == 0) {
            error_log("No campers found for edah $edotText, block $blockName, group $groupName: not assigning.");
            return TRUE; // This is not an error, so return true.
        }
        
        // Grab the available chugim for this group/block, for each edah.  The chug must have an
        // instance in this block, must be available to the edah, and must be in this group.
        // Note that the same edah can, and often will, be available to more than one edah.
        // We need to use a lookup table here, because edot might have overlapping but distinct
        // sets of allowed chugim.  A chug ca appear in more than one set, but there must only ever
        // be a single object for that chug, because we need to update its assigned count (for example)
        // when we assign a camper, regardless of the camper's edah.
        $chugIdsForEdot = array(); // Map edah ID->chug ID->1 (existence hash).
        $chugId2Chug = array();  // Map chug ID to chug object.
        foreach ($edah_ids as $edah_id) {
            $db = new DbConn();
            $db->addColVal($group_id, 'i');
            $db->addColVal($edah_id, 'i');
            $db->addColVal($edah_id, 'i');
            $db->addColVal($block_id, 'i');
            $db->addColVal($block_id, 'i');
            $db->isSelect = TRUE;
            $sql = "SELECT c.name, c.max_size, c.min_size, c.chug_id FROM chugim c, edot_for_chug e, edot_for_block b, chug_instances i WHERE " .
            "c.group_id = ? AND c.chug_id = e.chug_id AND e.edah_id = ? AND b.edah_id = ? AND b.block_id = ? AND " .
            "i.chug_id = c.chug_id AND i.block_id = ?";
            $result = $db->doQuery($sql, $err);
            if ($result == FALSE) {
                error_log($err);
                return FALSE;
            }
            while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                if (! array_key_exists($edah_id, $chugIdsForEdot)) {
                    $chugIdsForEdot[$edah_id] = array();
                }
                $chugIdsForEdot[$edah_id][$row[3]] = 1;
                if (! array_key_exists($row[3], $chugId2Chug)) {
                    $chugId2Chug[$row[3]] = new Chug($row[0], $row[1], $row[2], $row[3]);
                }
            }
            if (! array_key_exists($edah_id, $chugIdsForEdot)) {
                // Each edah being assigned must have at least one chug available.
                error_log("No chugim found for edah " . $edahId2Name[$edah_id] .
                          ", block $blockName, group $groupName: not assigning.");
                return TRUE; // This is not an error, so return true.
            }
        }
        
        // Map chug ID to chug instance ID, for this block.  We'll use this when we create entries
        // in the matches table.
        $chugInstanceIdForChugId = array();
        $db = new DbConn();
        $db->addSelectColumn("*");
        $db->addWhereColumn("block_id", $block_id, 'i');
        $db->isSelect = TRUE;
        $result = $db->simpleSelectFromTable("chug_instances", $err);
        if ($result == FALSE) {
            error_log($err);
            return FALSE;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $chugInstanceIdForChugId[$row["chug_id"]] = $row["chug_instance_id"];
        }
        
        // Grab camper pref lists in this block, by group.  We'll use this to compute each camper's
        // current happiness level when we step through the existing matches in the step
        // after this one.
        $existingPrefs = array();
        $db = new DbConn();
        $db->addColVal($block_id, 'i');
        $db->isSelect = TRUE;
        $sql = "SELECT p.camper_id camper_id, p.group_id group_id, " .
        "IFNULL(first_choice_id,-1), IFNULL(second_choice_id,-1), IFNULL(third_choice_id,-1), " .
        "IFNULL(fourth_choice_id,-1), IFNULL(fifth_choice_id,-1), IFNULL(sixth_choice_id,-1) " .
        "FROM preferences p, campers c WHERE p.camper_id = c.camper_id AND " .
        "p.block_id = ? AND (";
        $edahText = "";
        foreach ($edah_ids as $edah_id) {
            $db->addColVal($edah_id, 'i');
            if (empty($edahText)) {
                $edahText = "c.edah_id = ?";
            } else {
                $edahText .= " OR c.edah_id = ?";
            }
        }
        $sql .= $edahText . ")";
        $result = $db->doQuery($sql, $err);
        if ($result == FALSE) {
            error_log($err);
            return FALSE;
        }
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $camper_id = intval($row[0]);
            $gid = intval($row[1]);
            if (! array_key_exists($camper_id, $existingPrefs)) {
                $existingPrefs[$camper_id] = array();
            }
            $existingPrefs[$camper_id][$gid] = array();
            for ($i = 2; $i < count($row); $i++) {
                $chug_id = intval($row[$i]);
                if ($chug_id >= 0) {
                    // Map to zero-based pref level.  It's important for this to
                    // be zero-based, because multiple first choices should
                    // equal "perfect happiness", i.e., a score of zero.
                    $existingPrefs[$camper_id][$gid][$chug_id] = $i - 2;
                }
            }
        }
        
        // Grab the de-dup matrix.
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
        
        // Grab existing matches for all campers in this block/group, and
        // arrange them in a lookup table by camper ID.  We'll use this to prevent dups.
        // We also compute existing happiness level here, by checking each match
        // against the camper's pref list.
        // We have to exclude the group and block that we are currently matching,
        // because otherwise all current assignments will look like dups!
        $existingMatches = array();
        $db = new DbConn();
        $db->isSelect = TRUE;
        $sql = "SELECT m.camper_id, c.group_id, c.name, c.chug_id " .
        "FROM matches m, chug_instances i, chugim c, campers ca " .
        "WHERE m.chug_instance_id = i.chug_instance_id AND i.chug_id = c.chug_id " .
        "AND ca.camper_id = m.camper_id AND " .
        $edahText = "";
        foreach ($edah_ids as $edah_id) {
            $db->addColVal($edah_id, 'i');
            if (empty($edahText)) {
                $edahText = "(ca.edah_id = ?";
            } else {
                $edahText .= " OR ca.edah_id = ?";
            }
        }
        $sql .= $edahText . ") ";
        $db->addColVal($block_id, 'i');
        $db->addColVal($group_id, 'i');
        $sql .= "AND NOT (i.block_id = ? AND c.group_id = ?) ORDER BY 1,2";
        $result = $db->doQuery($sql, $err);
        if ($result == FALSE) {
            error_log($err);
            return FALSE;
        }
        $happiness = array();
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $cid = intval($row[0]);
            if (! array_key_exists($cid, $campers)) {
                // Not an error: we expect other campers to be in this block/chug/group.
                // We continue here because we're not assigning those campers in this round.
                continue;
            }
            $camper = $campers[$cid];
            $gid = intval($row[1]);
            if (! array_key_exists($camper->camper_id, $existingMatches)) {
                $existingMatches[$camper->camper_id] = array();
            }
            array_push($existingMatches[$camper->camper_id], $row[3]); // Add chug ID to existing match list.
            if (array_key_exists($camper->camper_id, $existingPrefs)) {
                // Compute the happiness level of this match.
                $prefsByGid = $existingPrefs[$camper->camper_id];
                if (array_key_exists($gid, $prefsByGid)) {
                    $prefsByChugId = $prefsByGid[$gid];
                    if (! array_key_exists($camper->camper_id, $happiness)) {
                        $happiness[$camper->camper_id] = 0; // Initialize
                    }
                    // Increment the camper's total happiness level according to
                    // their preference for this existing match.
                    $chug_id = intval($row[3]);
                    if (array_key_exists($chug_id, $prefsByChugId)) {
                        $happiness[$camper->camper_id] += $prefsByChugId[$chug_id];
                        debugLog("Incremented happiness of $camper->name by $prefsByChugId[$chug_id] (existing match to chug ID $chug_id)");
                    } else {
                        // In general, there should be a preference for assigned chugim.
                        error_log("WARNING: No preference found for $camper->name for assigned chug ID $chug_id");
                    }
                } else {
                    error_log("WARNING: No prefs for group $gid for camper " . $camper->name);
                    if ($camper != NULL) {
                        error_log("Camper name: " . $camper->name);
                    }
                }
            } else {
                // Campers should have a pref list: warn if we don't find one.
                error_log("WARNING: No preferences found for camper " . $camper->name);
            }
        }
        
        // Now, run the stable marriage algorithm.  We're assigning campers to chugim.  We try
        // to assign each camper to their first choice.  If a chug is full, we bump out the
        // camper with the best (lowest) current happiness score.
        shuffle($camperIdsToAssign);
        $assignments = array();
        while (($camperId = array_shift($camperIdsToAssign)) != NULL) {
            $camper =& $campers[$camperId];
            $edah_id = $camperId2EdahId[$camperId];
            $chugIdHashForThisEdah = $chugIdsForEdot[$edah_id]; // Eligible chugim for assignment.
            debugLog("Assigning " . $camper->name);
            // Try to assign this camper to the first chug in their preference list, and remove
            // that chug from their list.
            $candidateChugId = NULL;
            if (count($camper->prefs) > 0) {
                $candidateChugId = array_shift($camper->prefs);
            }
            if (! array_key_exists($camperId, $camperId2EdahId)) {
                // If we have a camper's ID, we expect to have their edah ID, so
                // log a warning.
                error_log("No edah cound for camper " . $camper->name);
                continue;
            }
            if ($candidateChugId == NULL) {
                // If we run out of preferences, assign the camper to the chug with the most
                // free space.  Note that chugWithMostSpace is guaranteed to return a chug, so
                // we know that this loop must terminate.
                $maxFreeChugId = chugWithMostSpace($chugIdHashForThisEdah, $chugId2Chug);
                // The max-free chug should never be NULL.  If it is, we have to exit.
                if ($maxFreeChugId === NULL) {
                    error_log("ERROR: Cannot find max free chug: can't continue");
                    return FALSE;
                }
                $maxFreeChug =& $chugId2Chug[$maxFreeChugId];
                debugLog("No more prefs: assigning to max free chug " . $maxFreeChug->name);
                assign($camper, $assignments, $maxFreeChug);
                continue;
            }
            if (! array_key_exists($candidateChugId, $chugIdHashForThisEdah)) {
                // This could occur if the allowed edot for a chug or edah were
                // changed after preferences were set.  We can't easily correct
                // this, so just log a warning for now.
                error_log("WARNING: Preferred chug ID " . $candidateChugId . " for camper $camper->name" .
                          " not found in allowed chug set (set has " . count($chugIdHashForThisEdah) . " edot)");
                $err = "Chug choices for " . $camper->name . " contained illegal chug ID " . $candidateChugId;
                array_push($camperIdsToAssign, $camper->camper_id); // Try the next pref.
                continue;
            }
            $candidateChug =& $chugId2Chug[$candidateChugId];
            $camper->choice_level++; // Increment the choice level (it starts at zero).
            // At this point, we check for duplicate assignment.
            if (array_key_exists($camper->camper_id, $existingMatches)) {
                $matchesForThisCamper = $existingMatches[$camper->camper_id];
                debugLog("Have " . count($matchesForThisCamper) .
                         " existing matches, trying to assign to $candidateChug->name (ID $candidateChugId), which has a max of $candidateChug->max_size" .
                         " and current assign count of $candidateChug->assigned_count");
                // Check for duplicate assignment, and skip dups, unless needs_first_choice
                // is set.
                if (isDuplicate($candidateChug, $matchesForThisCamper, $deDupMatrix)) {
                    if ($camper->needs_first_choice == FALSE) {
                        array_push($camperIdsToAssign, $camper->camper_id);
                        debugLog("Skipping duplicate " . $candidateChug->name);
                        continue;
                    } else {
                        debugLog("Allowing duplicate " . $candidateChug->name . ", needs first choice");
                    }
                }
            }
            // Now, try to assign this camper to this chug.
            if ($candidateChug->chugFree()) {
                // If there is space in the chug, assign right away, and continue to the
                // next camper.
                debugLog("Have space, assigning to chug: " . $candidateChug->name);
                assign($camper, $assignments, $candidateChug);
                continue;
            }
            debugLog("Candidate chug " . $candidateChug->name . " is full - trying to bump");
            // Try to find a happier camper who is assigned to this chug.
            $happierCamperId = findHappierCamper($camper, $candidateChug, $assignments,
                                                 $happiness, $chugId2Chug, $campers);
            
            if ($happierCamperId == NULL) {
                // No happier camper was found.
                debugLog("No happier camper found");
                if ($camper->needs_first_choice) {
                    // If this camper needs their first choice, we assign to this chug, even if it
                    // causes overflow.
                    debugLog("This camper needs first choice, so assigning anyway: chug will overflow");
                    assign($camper, $assignments, $candidateChug);
                    continue;
                }
                // Otherwise, we put this camper back in the queue- we'll try again with their next choice.
                debugLog("Putting this camper back in assign queue");
                array_push($camperIdsToAssign, $camper->camper_id);
                continue;
            }
            // Un-assign the happier camper from this chug, and put their ID back in the
            // assignment queue.  Assign our camper to the chug instead.
            $happierCamper = $campers[$happierCamperId];
            unassign($happierCamper, $assignments, $candidateChug);
            array_push($camperIdsToAssign, $happierCamper->camper_id);
            assign($camper, $assignments, $candidateChug);
            debugLog("Unassigned happier camper " . $happierCamper->name . ", assigned " . $camper->name);
        }
        
        // Log results, and update the database.
        debugLog("Finished assignment loop - results:");
        foreach ($campers as $camperId => $cdbg) {
            $edah_id = $camperId2EdahId[$camperId];
            if ($edah_id == NULL) {
                error_log("ERROR: Can't find edah for assigned camper ID $camperId");
                return FALSE;
            }
            $edahName = $edahId2Name[$edah_id];
            $assignedChugId = $assignments[$camperId];
            if ($assignedChugId == NULL) {
                error_log("ERROR: Failed to assign chug for camper ID $camperId, edah $edahName");
                return FALSE;
            }
            $assignedChug = $chugId2Chug[$assignedChugId];
            if ($assignedChug == NULL) {
                error_log("ERROR: Assigned camper ID $camperId to illegal chug ID $assignedChugId");
                return FALSE;
            }
            $assignedChugInstanceId = $chugInstanceIdForChugId[$assignedChugId];
            if ($assignedChugInstanceId == NULL) {
                error_log("ERROR: Assigned camper ID $camperId chug ID $assignedChugId, which has no instance for block $block_id");
                return FALSE;
            }
            
            debugLog("Assigned " . $cdbg->name . ", cid " . $cdbg->camper_id . ", edah " . $edahName . " to " .
                     $assignedChug->name . ", choice " . $cdbg->choice_level);
            // Update the matches table with each camper's assignment.
            $db = new DbConn();
            $db->addColVal($cdbg->camper_id, 'i');
            $db->addColVal($block_id, 'i');
            $db->addColVal($group_id, 'i');
            $sql = "DELETE FROM matches WHERE camper_id = ? AND chug_instance_id IN " .
            "(SELECT chug_instance_id FROM chug_instances i, chugim c WHERE i.block_id = ? " .
            "AND i.chug_id = c.chug_id AND c.group_id = ?)";
            $result = $db->doQuery($sql, $err);
            if ($result == FALSE) {
                error_log($err);
                return FALSE;
            }
            $db = new DbConn();
            $db->addSelectColumn("chug_instance_id");
            $db->addWhereColumn("chug_id", $assignedChugId, 'i');
            $db->addWhereColumn("block_id", $block_id, 'i');
            $db->isSelect = TRUE;
            $result = $db->simpleSelectFromTable("chug_instances", $err);
            if ($result == FALSE) {
                error_log($err);
                return FALSE;
            }
            $row = $result->fetch_assoc();
            $db = new DbConn();
            $db->addColumn("camper_id", $cdbg->camper_id, 'i');
            $db->addColumn("chug_instance_id", $assignedChugInstanceId, 'i');
            $result = $db->insertIntoTable("matches", $err);
            if ($result == FALSE) {
                error_log($err);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
