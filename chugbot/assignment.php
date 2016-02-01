<?php
    include_once 'functions.php';
    include_once 'assignmentClasses.php';
    
    function assign($camper, &$assignments, &$chugToAssign) {
        $assignments[$camper->camper_id] = $chugToAssign->chug_id;
        $chugToAssign->assigned_count++;
        debugLog("Assigned camper ID " . $camper->camper_id . " to chug ID " . $chugToAssign->chug_id . ", name " . $chugToAssign->name);
    }
    
    function unassign($camper, &$assignments, &$chugToUnassign) {
        unset($assignments[$camper->camper_id]);
        $chugToUnassign->assigned_count--;
    }
    
    function spaceInNextPref($assignments, $chugim, $camper) {
        // Note the first chug in this camper's pref list, and compute the
        // space remaining in it per assignments compared to its max.
        if (count($camper->prefs) == 0) {
            return 0; // No preferences left.
        }
        $nextPrefChug = $chugim[$camper->prefs[0]]; // Look up by chug ID.
        $retVal = $nextPrefChug->max_size - $nextPrefChug->assigned_count;
        return ($retVal > 0) ? $retVal : 0; // The difference will be negative if oversubscribed.
    }
    
    function &chugWithMostSpace(&$chugim) {
        $maxFreeSpace = 0;
        $maxFree = NULL;
        foreach ($chugim as $chugId => $chug) {
            // Always return a chug: if all chugim have the same free space, then
            // we'll return the first one in the list.  It's crucial that we always
            // return a chug, because it guarantees that the assignment loop will
            // finish.
            if ($maxFree == NULL) {
                $maxFree =& $chug;
            }
            if ($maxFree == NULL ||
                $maxFreeSpace < ($chug->max_size - $chug->assigned_count)) {
                $maxFreeSpace = $chug->max_size - $chug->assigned_count;
                $maxFree =& $chug;
            }
        }
        
        debugLog("chugWithMostSpace: $maxFree->name");
        return $maxFree;
    }
    
    function isDuplicate($candidateChug, $matchesForThisCamper) {
        $retVal = FALSE;
        $candidateChugLcName = strtolower($candidateChug->name);
        if (array_key_exists($candidateChugLcName, $matchesForThisCamper)) {
            $retVal = TRUE; // Exact match
        }
        // Special case: don't allow duplicate cooking assignments.
        $existingCooking = preg_grep("/cooking/i", $matchesForThisCamper);
        if (count($existingCooking) &&
            preg_match("/cooking/i", $candidateChugLcName)) {
            $retVal = TRUE;
        }
        
        return $retVal;
    }
    
    // Reminder: if this camper has needs_first_choice set, then we can bump
    // any camper except those who need their first choice.  In this
    // latter case, we return false, and the caller should assign the camper anyway
    // (the caller tries this function first, because if a bump can be found, it's
    // better than overflowing the chug).
    function findHappierCamper($camper, $candidateChug, $ourAssignments,
                               $happiness, $chugim, $campers) {
        // First, get our happiness level and the space left in our next-choice
        // chug.
        debugLog("starting findHappierCamper for $camper->name");
        $ourHappiness = 0;
        if (array_key_exists($camper->camper_id, $happiness)) {
            $ourHappiness = $happiness[$camper->camper_id];
        }
        $minHappiness = $ourHappiness;
        $ourNextSpace = spaceInNextPref($ourAssignments, $chugim, $camper);
        $maxNextSpace = $ourNextSpace;
        debugLog("ourHappiness = $ourHappiness, ourNextSpace = $ourNextSpace");
        $happierCamperId = NULL;
        $mostFreeSpaceCamperId = NULL;
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
                continue;
            }
            $theirHappiness = 0;
            if (array_key_exists($otherCamperId, $happiness)) {
                $theirHappiness = $happiness[$otherCamperId];
            }
            debugLog("their happiness = $theirHappiness");
            if ($theirHappiness < $minHappiness) {
                // We've found a camper with a lower (better) happiness level.
                // Note this camper, and update the min.
                $happierCamperId = $otherCamperId;
                $minHappiness = $theirHappiness;
                debugLog("found camper ID $otherCamperId with better (lower) happiness $theirHappiness - min is now $minHappiness");
            }
            $theirNextSpace = spaceInNextPref($ourAssignments, $chugim, $otherCamper);
            if ($theirNextSpace > $maxNextSpace) {
                $mostFreeSpaceCamperId = $otherCamperId;
                $maxNextSpace = $theirNextSpace;
                debugLog("found camper ID $otherCamperId with more free space, $theirNextSpace");
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
        } else {
            return NULL;
        }
    }
    
    function do_assignment($edah_id, $block_id, $group_id, &$err) {
        debugLog("Making assignment for edah $edah_id, block $block_id, group $group_id");
        $mysqli = connect_db();
        
        // Grab the campers in this edah and block, and prefs for this group.  We determine the
        // campers in a block by joining with the block_instances table, which tells us which
        // sessions overlap with our block (campers register for sessions, not blocks, so the campers
        // table only knows about sessions).
        $campers = array();
        $camperIdsToAssign = array();
        $sql = "SELECT c.camper_id, c.first, c.last, c.needs_first_choice, " .
        "IFNULL(p.first_choice_id,-1), IFNULL(p.second_choice_id,-1), IFNULL(p.third_choice_id,-1), " .
        "IFNULL(p.fourth_choice_id,-1), IFNULL(p.fifth_choice_id,-1), IFNULL(p.sixth_choice_id,-1) " .
        "FROM campers c, block_instances b, preferences p " .
        "WHERE c.edah_id = $edah_id " .
        "AND c.session_id = b.session_id " .
        "AND b.block_id = $block_id " .
        "AND p.camper_id = c.camper_id " .
        "AND p.group_id = $group_id " .
        "AND p.block_id = b.block_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            $mysqli->close();
            return FALSE;
        }
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $c = new Camper($row[0], $row[1], $row[2], $row[3]);
            for ($i = 4; $i < count($row); $i++) {
                if ($row[$i] >= 0) {
                    array_push($c->prefs, $row[$i]);
                }
            }
            $camper_id = intval($row[0]);
            $campers[$camper_id] = $c;
            array_push($camperIdsToAssign, $camper_id);
        }
        
        // Grab the chugim in this group.
        $chugim = array();
        $sql = "SELECT name, max_size, min_size, chug_id FROM chugim WHERE " .
        "group_id = $group_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            $mysqli->close();
            return FALSE;
        }
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $c = new Chug($row[0], $row[1], $row[2], $row[3]);
            $chugim[$c->chug_id] = $c;
        }
        
        // Grab camper pref lists in this block, by group.  We'll use this to compute each camper's
        // current happiness level when we step through the existing matches in the step
        // after this one.
        $existingPrefs = array();
        $sql = "SELECT camper_id, group_id, " .
        "IFNULL(first_choice_id,-1), IFNULL(second_choice_id,-1), IFNULL(third_choice_id,-1), " .
        "IFNULL(fourth_choice_id,-1), IFNULL(fifth_choice_id,-1), IFNULL(sixth_choice_id,-1) " .
        "FROM preferences WHERE block_id = $block_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            $mysqli->close();
            return FALSE;
        }
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $camper_id = intval($row[0]);
            $gid = intval($row[1]);
            if (! array_key_exists($camper_id, $existingPrefs)) {
                $existingPrefs[$camper_id] = array();
            }
            $existingPrefs[$camper_id][$gid] = array();
            for ($i = 2; $i < count($row); $i++) {
                $chug_id = intval($row[$i]);
                if ($chug_id >= 0) {
                    $existingPrefs[$camper_id][$gid][$chug_id] = $i - 1; // map to 1-based pref level
                }
            }
        }
        
        // Grab existing matches for this block, for *other* groups, and arrange them in a lookup table
        // by camper ID.  We'll use this to prevent dups.  Note that when preventing
        // dups, we compare chugim by name rather than ID, since Ropes aleph will have
        // a different ID than Ropes bet.
        // We also compute existing happiness level here, by checking each match
        // against the camper's pref list.
        $existingMatches = array();
        $sql = "SELECT m.camper_id, m.group_id, c.name, c.chug_id FROM matches m, chugim c, campers ca " .
        "WHERE m.block_id = $block_id AND m.chug_id = c.chug_id AND m.group_id != $group_id " .
        "AND m.camper_id = ca.camper_id AND ca.edah_id = $edah_id GROUP BY 1,2";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            $mysqli->close();
            return FALSE;
        }
        $happiness = array();
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $cid = intval($row[0]);
            if (! array_key_exists($cid, $campers)) {
                // Not an error: we expect other campers to be in this block/chug/group.
                // We continue here because we're not assigning those campers in this round.
                continue;
            }
            $camper = $campers[$cid];
            $gid = intval($row[1]);
            $existingMatches[$camper->camper_id][strtolower($row[2])] = 1; // Note this match.
            if (array_key_exists($camper->camper_id, $existingPrefs)) {
                // Compute the happiness level of this match.
                $prefsByGid = $existingPrefs[$camper->camper_id];
                if (array_key_exists($gid, $prefsByGid)) {
                    $prefsByChugId = $prefsByGid[$gid];
                    if (! array_key_exists($camper->camper_id, $happiness)) {
                        $happiness[$camper->camper_id] = 0; // Initialize
                    }
                    // Increment the camper's total happiness level according to their
                    // preference for this existing match.
                    $chug_id = intval($row[3]);
                    if (array_key_exists($chug_id, $prefsByChugId)) {
                        $happiness[$camper->camper_id] += $prefsByChugId[$chug_id];
                        debugLog("Incremented happiness of $camper->name by $prefsByChugId[$chug_id] (existing match to chug ID $chug_id");
                    } else {
                        // In general, there should be a preference for assigned chugim.
                        error_log("WARNING: No preference for for $camper->name for assigned chug ID $chug_id");
                    }
                } else {
                    error_log("WARNING: No prefs for group $gid for camper ID $camper_id");
                    if ($camper != NULL) {
                        error_log("Camper name: " . $camper->name);
                    }
                }
            } else {
                // All campers should have a pref list.
                error_log("ERROR: No preferences found for camper ID $camper_id");
                if ($camper != NULL) {
                    error_log("Camper name: " . $camper->name);
                }
            }
        }
        
        // Now, run the stable marriage algorithm.  We're assigning campers to chugim.  We try
        // to assign each camper to their first choice.  If a chug is full, we bump out the
        // camper with the best (lowest) current happiness score.
        shuffle($camperIdsToAssign);
        $assignments = array();
        while (($camperId = array_shift($camperIdsToAssign)) != NULL) {
            $camper =& $campers[$camperId];
            debugLog("Assigning " . $camper->name);
            // Try to assign this camper to the first chug in their preference list, and remove
            // that chug from their list.
            $candidateChugId = NULL;
            if (count($camper->prefs) > 0) {
                $candidateChugId = array_shift($camper->prefs);
            }
            if ($candidateChugId == NULL) {
                // If we run out of preferences, assign the camper to the chug with the most
                // free space.  Note that chugWithMostSpace is guaranteed to return a chug, so
                // we know that this loop must terminate.
                $maxFreeChug =& chugWithMostSpace($chugim);
                debugLog("No more prefs: assigning to max free chug " . $maxFreeChug->name);
                assign($camper, $assignments, $maxFreeChug);
                continue;
            }
            if (! array_key_exists($candidateChugId, $chugim)) {
                error_log("ERROR: Preferred chug ID " . $candidateChugId . " not found in input set");
                $err = "Chug choices for " . $camper->name . " contains illegal chug ID " . $candidateChugId;
                return false;
            }
            $candidateChug =& $chugim[$candidateChugId];
            
            $camper->choice_level++; // Increment the choice level (it starts at zero).
            // At this point, we check to see if this camper has already been assigned
            // to this chug in this block in a different group.  We're relying on names being
            // consistent, apart from case.
            if (array_key_exists($camper->camper_id, $existingMatches)) {
                $matchesForThisCamper = $existingMatches[$camper->camper_id];
                debugLog("Have " . count($matchesForThisCamper) . " existing match, trying to assign to $candidateChug->name");
                // Check for duplicate assignment, and skip dups, unless needs_first_choice
                // is set.
                if (isDuplicate($candidateChug, $matchesForThisCamper)) {
                    if ($camper->needs_first_choice == FALSE) {
                        array_push($camperIdsToAssign, $camper->camper_id);
                        debugLog("Skipping duplicate " . $candidateChug->name);
                        continue;
                    } else {
                        debugLog("Skipping duplicate " . $candidateChug->name . ", needs first choice");
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
                                                 $happiness, $chugim, $campers);
            
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
        
        // Compile stats, and update the database.
        $firstCt = 0;
        $secondCt = 0;
        $thirdCt = 0;
        $fourthOrWorseCt = 0;
        debugLog("Finished assignment loop - results:");
        foreach ($campers as $camperId => $cdbg) {
            $assignedChugId = $assignments[$camperId];
            $assignedChug = $chugim[$assignedChugId];
            debugLog("Assigned " . $cdbg->name . ", cid " . $cdbg->camper_id . " to " . $assignedChug->name . ", choice " . $cdbg->choice_level);
            if ($cdbg->choice_level == 1) {
                $firstCt++;
            } else if ($cdbg->choice_level == 2) {
                $secondCt++;
            } else if ($cdbg->choice_level == 3) {
                $thirdCt++;
            } else {
                $fourthOrWorseCt++;
            }
            // Update the matches table with each camper's assignment.
            $sql = "DELETE FROM matches WHERE camper_id = $camperId AND block_id = $block_id AND " .
            "group_id = $group_id";
            $result = $mysqli->query($sql);
            if ($result == FALSE) {
                $err = dbErrorString($sql, $mysqli->error);
                error_log($err);
                $mysqli->close();
                return FALSE;
            }
            $sql = "INSERT INTO matches (camper_id, block_id, group_id, chug_id) " .
            "VALUES ($camperId, $block_id, $group_id, $assignedChugId)";
            $result = $mysqli->query($sql);
            if ($result == FALSE) {
                $err = dbErrorString($sql, $mysqli->error);
                error_log($err);
                $mysqli->close();
                return FALSE;
            }
        }
        $underMin = "";
        $overMax = "";
        overUnder($chugim, $underMin, $overMax);
        
        // Update the assignment table (metadata about this assignment) with our stats.
        $sql = "DELETE FROM assignments WHERE edah_id = $edah_id AND " .
        "block_id = $block_id AND group_id = $group_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            error_log($err);
            $mysqli->close();
            return FALSE;
        }
        $sql = "INSERT INTO assignments (edah_id, block_id, group_id, first_choice_ct, second_choice_ct, third_choice_ct, " .
        "fourth_choice_or_worse_ct, under_min_list, over_max_list) " .
        "VALUES ($edah_id, $block_id, $group_id, $firstCt, $secondCt, $thirdCt, $fourthOrWorseCt, \"$underMin\", \"$overMax\")";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            error_log($err);
            $mysqli->close();
            return FALSE;
        }
        
        $mysqli->close();
        return TRUE;
    }
    
    
