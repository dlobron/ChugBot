<?php
    include 'functions.php';
    
    class Chug {
        function __construct($name, $max_size, $min_size, $chug_id) {
            $this->name = $name;
            $this->max_size = intval($max_size);
            $this->min_size = intval($min_size);
            $this->chug_id = intval($chug_id);
        }
        function chugFree() {
            return ($max > $assigned_count);
        }
        public $name = "";
        public $max = 0;
        public $min = 0;
        public $chug_id = -1;
        public $assigned_count = 0;
    };
    
    class Camper {
        function __construct($camper_id, $first, $last, $needs_first_choice) {
            $this->camper_id = intval($camper_id);
            $this->first = $first;
            $this->last = $last;
            $this->needs_first_choice = intval($needs_first_choice);
        }
        public $camper_id = -1;
        public $first = "";
        public $last = "";
        public $needs_first_choice = 0;
        public $prefs = array();
    };
    
    function assign($camper, &$assignments, &$chugToAssign) {
        $assignments[$camper->camper_id] = $chugToAssign->chug_id;
        $chugToAssign->assigned_count++;
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
    
    // Reminder: if this camper has needs_first_choice set, then we can bump
    // any camper except those who need their first choice.  In this
    // latter case, we return false, and the caller should assign the camper anyway
    // (the caller tries this function first, because if a bump can be found, it's
    // better than overflowing the chug).
    function findHappierCamper($camper, $candidateChug, $ourAssignments,
                               $happiness, $chugim, $campers, $group_id) {
        // First, get our happiness level and the space left in our next-choice
        // chug.
        $ourHappiness = 0;
        if (array_key_exists($camper->camper_id, $happiness)) {
            $ourHappiness = $happiness[$camper->camper_id];
        }
        $minHappiness = $ourHappiness;
        $ourNextSpace = spaceInNextPref($ourAssignments, $chugim, $camper);
        $maxNextSpace = $ourNextSpace;
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
            if ($otherCamper->needs_first_choice) {
                // Campers who need their first choice should never be bumped.
                continue;
            }
            $theirHappiness = 0;
            if (array_key_exists($otherCamperId, $happiness)) {
                $theirHappiness = $happiness[$otherCamperId];
            }
            if ($theirHappiness < $minHappiness) {
                // We've found a camper with a lower (better) happiness level.
                // Note this camper, and update the min.
                $happierCamperId = $otherCamperId;
                $minHappiness = $theirHappiness;
            }
            $theirNextSpace = spaceInNextPref($ourAssignments, $chugim, $otherCamper);
            if ($theirNextSpace > $maxNextSpace) {
                $mostFreeSpaceCamperId = $otherCamperId;
                $maxNextSpace = $theirNextSpace;
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
        error_log("DBG: Making assignment for edah $edah_id, block $block_id, group $group_id");
        $mysqli = connect_db();
        
        // Grab the campers in this edah and block, and prefs for this group.  We determine the
        // campers in a block by joining with the block_instances table, which tells us which
        // sessions overlap with our block (campers register for sessions, not blocks, so the campers
        // table only knows about sessions).
        $campers = array();
        $camperIdsToAssign = array();
        $sql = "SELECT c.camper_id, c.first, c.last, c.needs_first_choice, " .
        "IFNULL(-1,p.first_choice_id), IFNULL(-1,p.second_choice_id), IFNULL(-1,p.third_choice_id), " .
        "IFNULL(-1,p.fourth_choice_id), IFNULL(-1,p.fifth_choice_id), IFNULL(-1,p.sixth_choice_id) " .
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
            error_log("DBG: Found camper " . $row[2]);
            for ($i = 4; $i < count($row); $i++) {
                if ($row[$i] >= 0) {
                    array_push($c->prefs, $row[$i]);
                    error_log("DBG: pref $i = " . $row[$i]);
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
            error_log("DBG: Adding chug " . $row[0]);
        }
        
        // Grab camper pref lists in this block, by group.  We'll use this to compute each camper's
        // current happiness level when we step through the existing matches in the step
        // after this one.
        $existingPrefs = array();
        $sql = "SELECT camper_id, group_id, " .
        "IFNULL(-1,first_choice_id), IFNULL(-1,second_choice_id), IFNULL(-1,third_choice_id), " .
        "IFNULL(-1,fourth_choice_id), IFNULL(-1,fifth_choice_id), IFNULL(-1,sixth_choice_id) " .
        "FROM preferences WHERE block_id = $block_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            $mysqli->close();
            return FALSE;
        }
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $camper_id = intval($row[0]);
            $group_id = intval($row[1]);
            $existingPrefs[$camper_id][$group_id] = array();
            for ($i = 2; $i < count($row); $i++) {
                $chug_id = intval($row[$i]);
                if ($chug_id >= 0) {
                    $existingPrefs[$camper_id][$group_id][$chug_id] = $i - 1; // map to 1-based pref level
                }
            }
        }
        
        // Grab existing matches for this block, for *other* groups, and arrange them in a lookup table
        // by camper ID.  We'll use this to prevent dups.  Note that when preventing
        // dups, we compare chugim by name rather than ID, since Ropes aleph will have
        // a different ID than Ropes bet.  (TODO: Verify with DO that this is right).
        // We also compute existing happiness level here, by checking each match
        // against the camper's pref list.
        $existingMatches = array();
        $sql = "SELECT m.camper_id, m.group_id, c.name FROM matches m, chugim c " .
        "WHERE m.block_id = $block_id AND m.chug_id = c.chug_id AND m.group_id != $group_id " .
        "GROUP BY 1,2";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            $mysqli->close();
            return FALSE;
        }
        $happiness = array();
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $camper_id = intval($row[0]);
            $camper = NULL;
            if (array_key_exists($camper_id, $campers)) {
                $camper = $campers[$camper_id];
            }
            $group_id = intval($row[1]);
            $chug_name_lc = strtolower($row[2]);
            $existingMatches[$camper_id][$chug_name_lc] = 1; // Note this match.
            if (array_key_exists($camper_id, $existingPrefs)) {
                // Compute the happiness level of this match.
                $prefsByGid = $existingPrefs[$camper_id];
                if (array_key_exists($group_id, $prefsByGid)) {
                    $prefsByChugId = $prefsByGid[$group_id];
                    if (! array_key_exists($camper_id, $happiness)) {
                        $happiness[$camper_id] = 0; // Initialize
                    }
                    // Increment the camper's total happiness level according to their
                    // preference for this existing match.
                    $happiness[$camper_id] += $prefsByChugId[$chug_id];
                } else {
                    error_log("WARNING: No prefs for group $group_id for camper ID $camper_id");
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
        shuffle($campers);
        $assignments = array();
        while (($camperId = array_shift($camperIdsToAssign)) != NULL) {
            $camper = &($campers[$camperId]);
            error_log("DBG: Assigning " . $camper->name);
            // Try to assign this camper to the first chug in their preference list, and remove
            // that chug from their list.
            $candidateChugId = array_shift($camper->prefs);
            $candidateChug = &($chugim[$candidateChugId]);
            // At this point, we check to see if this camper has already been assigned
            // to this chug in this block in a different group.  We're relying on names being
            // consistent, apart from case.
            if (array_key_exists($camper->camper_id, $existingMatches)) {
                $matchesForThisCamper = $existingMatches[$camper_id];
                // Check to see if this camper is already assigned to a chug with the
                // same name.
                $candidateChugLcName = strtolower($candidateChug->name);
                if (array_key_exists($candidateChugLcName, $matchesForThisCamper)) {
                    // At this point, we want to reject duplicates, unless needs-first-choice
                    // is set.
                    if ($camper->needs_first_choice == FALSE) {
                        array_push($camperIdsToAssign, $camper->camper_id);
                        error_log("DBG: Skipping duplicate " . $candidateChugLcName);
                        continue;
                    }
                }
            }
            // Now, try to assign this camper to this chug.
            if ($candidateChug->chugFree()) {
                // If there is space in the chug, assign right away, and continue to the
                // next camper.
                error_log("DBG: Assigning to " . $candidateChug->name);
                assign($camper, $assignments, $candidateChug);
                continue;
            }
            error_log("DBG: Candidate chug " . $candidateChug->name . " is full - trying to bump");
            // Try to find a happier camper who is assigned to this chug.
            $happierCamperId = findHappierCamper($camper, $candidateChug, $assignments,
                                                 $happiness, $chugim, $group_id);
            
            if ($happierCamperId == NULL) {
                // No happier camper was found.
                error_log("DBG: No happier camper found");
                if ($camper->needs_first_choice) {
                    // If this camper needs their first choice, we assign to this chug, even if it
                    // causes overflow.
                    error_log("DBG: This camper needs first choice, so assigning anyway: chug will overflow");
                    assign($camper, $assignments, $candidateChug);
                    continue;
                }
                // Otherwise, we put this camper back in the queue- we'll try again with their next choice.
                error_log("DBG: Putting this camper back in assign queue");
                array_push($camperIdsToAssign, $camper->camper_id);
                continue;
            }
            // Un-assign the happier camper from this chug, and put their ID back in the
            // assignment queue.  Assign our camper to the chug instead.
            $happierCamper = $campers[$happierCamperId];
            unassign($happierCamper, $assignments, $candidateChug);
            array_push($camperIdsToAssign, $happierCamper->camper_id);
            assign($camper, $assignments, $candidateChug);
            error_log("DBG: Unassigned happier camper " . $happierCamper->name . ", assigned " . $camper->name);
        }
        error_log("DBG: Finished assignment loop");
    
        // Now that we've done the assignment, we can insert/update the matches and assignments
        // tables.
        // TODO
        
        $mysqli->close();
        return TRUE;
    }
    
    
