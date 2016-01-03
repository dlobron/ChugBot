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
    
    function assign($camper, &$assignments, $existingChug) {
        $assignments[$camper->camper_id] = $existingChug->chug_id;
        $existingChug->assigned_count++;
    }
    
    function do_assignment($edah_id, $block_id, $group_id, &$err) {
        error_log("DBG: Making assignment for edah $edah_id, block $block_id, group $group_id");
        
        // Grab the campers in this edah and block.  We determine the campers in a block
        // by joining with the block_instances table, which tells us which sessions overlap
        // with our block.
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
        while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
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
        while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
            $c = new Chug($row[0], $row[1], $row[2], $row[3]);
            $chugim[$c->chug_id] = $c;
            error_log("DBG: Adding chug " . $row[0]);
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
        while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
            $camper_id = intval($row[0]);
            $group_id = intval($row[1]);
            $chug_id = intval($row[2]);
            $pegged = intval($row[3]);
            $existingMatches[$camper_id][$group_id] = array("chug_id" => $chug_id,
                                                            "pegged" => $pegged);
        }
        
        // Now, run the stable marriage algorithm.  We're assigning campers to chugim.  We try
        // to assign each camper to their first choice.  If a chug is full, we bump out the
        // camper with the best (lowest) current happiness score.
        shuffle($campers);
        $happiness = array();
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
                // camper, unassign the other camper from %assignments, and put this camper back into the
                // camper array.
                if ($candidateChug->chugFree()) {
                    // If there is space in the chug, assign right away.
                    error_log("DBG: Assigning to " . $candidateChug->name);
                    assign($camper, $assignments, $candidateChug);
                    continue;
                }
                error_log("DBG: Candidate chug " . $candidateChug->name . " is full");
                if ($candidateChug->needs_first_choice) {
                    // If the chug is full, but this camper needs their first choice, try to bump
                    // an existing camper, and then assign.  The bump should always work, except
                    // if all existing campers are either pegged or also need-first-choice: in that
                    // case, we go over the limit, and let the staff edit.
                    
                    
                    
            
            
        
        
    }
