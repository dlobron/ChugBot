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
    // any camper except those who need their first choice or are pegged.  In this
    // latter case, we return false, and the caller should assign the camper anyway
    // (the caller tries this function first, because if a bump can be found, it's
    // better than overflowing the chug).
    function findHappierCamper($camper, $candidateChug, $assignments,
                               $happiness, $chugim, $campers) {
        // First, get our happiness level and the space left in our next-choice
        // chug.
        $ourHappiness = 0;
        if (array_key_exists($camper->camper_id, $happiness)) {
            $ourHappiness = $happiness[$camper->camper_id];
        }
        $minHappiness = $ourHappiness;
        $ourNextSpace = spaceInNextPref($assignments, $chugim, $camper);
        $maxNextSpace = $ourNextSpace;
        $happierCamperId = NULL;
        $mostFreeSpaceCamperId = NULL;
        // Loop through existing assignments, and see if any camper assigned to
        // this chug is happier than we are.  If we have a tie, return the camper
        // with the most free space in their next choice, since they are hurt least
        // by being bumped.
        // Exceptions: campers with a pegged assignment or needs_first_choice cannot
        // be bumped, so we skip them when searching for a happier camper (a happier
        // camper is a bump candidate).
        foreach ($assignments as $otherCamperId => $assignedChugId) {
            if ($assignedChugId != $candidateChug->chug_id) {
                continue;
            }
            $otherCamper = $campers[$otherCamperId];
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
        
    
    function do_assignment($edah_id, $block_id, $group_id, &$err) {
        error_log("DBG: Making assignment for edah $edah_id, block $block_id, group $group_id");
        
        // Grab the campers in this edah and block, and prefs for this group.  We determine the
        // campers in a block by joining with the block_instances table, which tells us which
        // sessions overlap with our block (campers register for sessions, not blocks, so the campers
        // table only knows about sessions).
        $campers = array();
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
            array_push($campers, $c);
        }
        
        // Grab the chugim in this group.
        $chugim = array();
        $sql = "SELECT name, max_size, min_size, chug_id FROM chugim WHERE " .
        "group_id = $group_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            return FALSE;
        }
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $c = new Chug($row[0], $row[1], $row[2], $row[3]);
            $chugim[$c->chug_id] = $c;
            error_log("DBG: Adding chug " . $row[0]);
        }
        
        // Grab the other pref lists for this block.  We'll use this to compute each camper's
        // current happiness level when we step through the existing matches in the step
        // after this one.
        $prefs = array();
        $sql = "SELECT camper_id,group_id, " .
        "IFNULL(-1,first_choice_id), IFNULL(-1,second_choice_id), IFNULL(-1,third_choice_id), " .
        "IFNULL(-1,fourth_choice_id), IFNULL(-1,fifth_choice_id), IFNULL(-1,sixth_choice_id) " .
        "FROM preferences WHERE block_id = $block_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            return FALSE;
        }
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $camper_id = intval($row[0]);
            $group_id = intval($row[1]);
            $prefs[$camper_id][$group_id] = array(); // camper ID -> group ID -> array
            for ($i = 2; $i < count($row); $i++) {
                $chug_id = intval($row[$i]);
                if ($chug_id >= 0) {
                    $prefs[$camper_id][$group_id][$chug_id] = $i - 1; // map to 1-based pref level
                }
            }
        }
        
        // Grab existing matches for this block, and arrange them in a lookup table
        // by camper ID.  We'll use this to prevent dups.  Also, if a match is pegged,
        // then we'll always assign it the same way.
        $existingMatches = array();
        $sql = "SELECT camper_id,group_id,chug_id,pegged FROM matches WHERE block_id = $block_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $err = dbErrorString($sql, $mysqli->error);
            return FALSE;
        }
        $happiness = array();
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $camper_id = intval($row[0]);
            $this_group_id = intval($row[1]);
            $chug_id = intval($row[2]);
            $pegged = intval($row[3]);
            $existingMatches[$camper_id][$group_id] = array("chug_id" => $chug_id,
                                                            "pegged" => $pegged);
            // Compute happiness level, based on this camper's prefs for this group.
            // XX: This is complicated.  The user could be re-running any of the groups, so should
            // we count the current one when we compute happiness level?  For now, only count
            // the other groups.
            if ($this_group_id == $group_id) {
                continue; // Don't count this group's assignments toward happiness score.
            }
            if (array_key_exists($camper_id, $prefs)) {
                $prefsByGid = $prefs[$camper_id];
                if (array_key_exists($this_group_id, $prefsByGid)) {
                    $prefsByChugId = $prefsByGid[$this_group_id];
                    if (! array_key_exists($camper_id, $happiness)) {
                        $happiness[$camper_id] = 0;
                    }
                    // If we have an assignment for this camper, for this group,
                    // increment the camper's happiness level accordingly.
                    $happiness[$camper_id] += $prefsByChugId[$chug_id];
                }
            }
        }
        
        // Now, run the stable marriage algorithm.  We're assigning campers to chugim.  We try
        // to assign each camper to their first choice.  If a chug is full, we bump out the
        // camper with the best (lowest) current happiness score.
        shuffle($campers);
        $assignments = array();
        while (($camper = array_shift($campers)) != NULL) {
            error_log("DBG: Assigning " . $camper->name);
            // Try to assign this camper to the first chug in their preference list, and remove
            // that chug from their list.
            $candidateChugId = array_shift($camper->prefs);
            $candidateChug = &($chugim[$candidateChugId]);
            // At this point, we check to see if this camper has already been assigned
            // to this chug in this block in a different group.  We're relying on names being
            // consistent, apart from case.  Exceptions:
            // - If the camper's existing assignment is pegged, we always keep it.
            // - If needs_first_choice is set for a camper, then we allow dups.
            // We check for the exceptions first.
            if (array_key_exists($camper->camper_id, $existingMatches)) {
                $matchesForThisCamper = $existingMatches[$camper_id];
                if (array_key_exists($group_id, $matchesForThisCamper)) {
                    $existingMatchId = $matchesForThisCamper[$group_id]["chug_id"];
                    $existingMatchPegged = $matchesForThisCamper[$group_id]["pegged"];
                    $existingChug = &($chugim[$existingMatchId]);
                    if ($existingMatchPegged) {
                        // Existing choice is pegged: keep it.
                        assign($camper, $assignments, $existingChug);
                        error_log("DBG: Keeping pegged choice " . $existingChug->name);
                        continue;
                    }
                    // At this point, we want to reject duplicates, unless needs-first-choice
                    // is set.
                    if ($candidateChug->chug_id == $existingMatch["chug_id"] &&
                        $camper->needs_first_choice == FALSE) {
                        array_push($campers, $camper);
                        error_log("DBG: Skipping duplicate " . $existingChug->name);
                        continue;
                    }
                }
                // Now, try to assign this camper to this chug.
                // - If space, or if first choice required, assign right away.
                // - Otherwise: if this camper is less happy than one assigned to that chug, assign this
                // camper, unassign the other camper, and put the other camper back into the
                // camper array.
                if ($candidateChug->chugFree()) {
                    // If there is space in the chug, assign right away.
                    error_log("DBG: Assigning to " . $candidateChug->name);
                    assign($camper, $assignments, $candidateChug);
                    continue;
                }
                error_log("DBG: Candidate chug " . $candidateChug->name . " is full - trying to bump");
                $happierCamper = findHappierCamper($camper, $candidateChug, $assignments,
                                                   $happiness, $chugim);
                    
                    
            
            
        
        
    }
