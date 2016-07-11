<?php
    session_start();
    include_once 'functions.php';
    include_once 'dbConn.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    $edahId2Name = array();
    fillId2Name($edahId2Name, $dbErr,
                "edah_id", "edot");
    class Camper {
        function __construct($first, $last, $edah_id, $camper_id, $email, $edahId2Name) {
            $this->first = strtolower($first);
            $this->last = strtolower($last);
            $this->printableName = $first . " " . $last;
            $this->edah_id = $edah_id;
            if (array_key_exists($edah_id, $edahId2Name)) {
                $this->edah = $edahId2Name[$edah_id];
            } else {
                $this->edah = "-";
            }
            $this->camper_id = $camper_id;
            $this->email = strtolower($email);
        }
        
        function desc() {
            return $this->printableName .
            " (" . $this->edah . ")";
        }
        
        function debugDesc() {
            return $this->printableName .
            " (" . $this->edah . ", ID " . $this->camper_id . ")";
        }
        public $first;
        public $last;
        public $printableName;
        public $edah_id;
        public $edah;
        public $camper_id;
        public $email;
    }
    
    function potentialDup($prevCamper, $curCamper) {
        if ($prevCamper === NULL ||
            $curCamper === NULL) {
            return FALSE;
        }
        // If campers are in the same edah or have the same email, make the last name
        // match more permissive.  These required distances can be tweaked based
        // on performance.
        $maxLev = 1;
        if ($prevCamper->edah_id == $curCamper->edah_id ||
            $prevCamper->email == $curCamper->email) {
            $maxLev = 2;
        }
        // Compare Levenshtein string distance.
        $lev = levenshtein($prevCamper->last, $curCamper->last);
        if ($lev > $maxLev) {
            return FALSE;
        }
        // Insist on either the same first initial or a close Lev distance.
        $flev = levenshtein($prevCamper->first, $curCamper->first);
        if (substr($prevCamper->first, 0, 1) != substr($curCamper->first, 0, 1) &&
            $flev > $maxLev) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    $homeUrl = urlIfy("staffHome.php");
    $dbErr = $nothingToDeleteError = "";
    $numDeleted = 0;
    $pruneMatches = FALSE;
    $pruneCamperDups = FALSE;
    $confirmDelete = FALSE;
    $didDeleteOk = FALSE;
    $didMergeOk = FALSE;
    $potentialCamperDups = array();
    $wouldBeDeleted = array();
    $deleteFromMatchesIds = array();
    $dupIndices = array();
    if ($_GET) {
        $action = test_input($_GET["radioGroup"]);
        if ($action == "prune_matches") {
            $pruneMatches = TRUE;
        } else if ($action == "prune_camper_dups") {
            $pruneCamperDups = TRUE;
        }
        $confirmDelete = test_input($_GET["confirm_delete"]);
        if (! empty($_GET["dup_index"])) {
            foreach ($_GET["dup_index"] as $dup_index) {
                $dupIndex = intval(test_input($dup_index));
                array_push($dupIndices, $dupIndex);
            }
        }
    }
    if ($pruneMatches) {
        // Select matches that are not valid due to changes in allowed edot,
        // blocks, chugim, or any other category.
        $sql = "SELECT m.match_id illegal_match_id, CONCAT(c.first, ' ', c.last) camper_name, ch.name chug_name, b.name block_name, " .
        "legal_instances.chug_instance_id instance_id FROM " .
        "matches m " .
        "JOIN campers c ON c.camper_id = m.camper_id " .
        "JOIN chug_instances i ON i.chug_instance_id = m.chug_instance_id " .
        "JOIN chugim ch ON ch.chug_id = i.chug_id " .
        "JOIN blocks b ON b.block_id = i.block_id " .
        "LEFT OUTER JOIN " .
        "(SELECT i.chug_instance_id chug_instance_id, m.match_id match_id FROM " .
        "matches m, chug_instances i, edot_for_block e, campers c, block_instances bi, edot_for_chug ec, edot_for_group eg, chugim ch WHERE " .
        "m.chug_instance_id = i.chug_instance_id AND i.block_id = bi.block_id AND bi.session_id = c.session_id AND m.camper_id = c.camper_id AND " .
        "e.block_id = i.block_id AND e.edah_id = c.edah_id AND ec.chug_id = i.chug_id AND eg.edah_id = c.edah_id AND " .
        "eg.group_id = ch.group_id AND i.chug_id = ch.chug_id AND eg.edah_id = c.edah_id AND ec.edah_id = c.edah_id) legal_instances " .
        "ON m.chug_instance_id = legal_instances.chug_instance_id AND m.match_id = legal_instances.match_id " .
        "ORDER BY illegal_match_id";
        $db = new DbConn();
        $result = $db->runQueryDirectly($sql, $dbErr);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $instance_id = $row["instance_id"]; // This will be NULL if this row is not legal.
                $illegal_match_id = $row["illegal_match_id"];
                $camper_name = $row["camper_name"];
                $chug_name = $row["chug_name"];
                $block_name = $row["block_name"];
                if ($instance_id === NULL) {
                    array_push($wouldBeDeleted, "$camper_name: $chug_name, $block_name");
                    array_push($deleteFromMatchesIds, $illegal_match_id);
                }
            }
        }
        if ($confirmDelete) {
            if (count($deleteFromMatchesIds) == 0) {
                $nothingToDeleteError = "No items to delete were checked";
            }
            foreach ($deleteFromMatchesIds as $delId) {
                $db = new DbConn();
                $err = "";
                $db->addWhereColumn('match_id', $delId, 'i');
                if ($db->deleteFromTable("matches", $dbErr)) {
                    error_log("Deleted match ID $delId OK");
                    $didDeleteOk = TRUE;
                    $numDeleted++;
                } else {
                    error_log("Failed to delete obsolete assignment: $dbErr");
                    $didDeleteOk = FALSE;
                    break;
                }
            }
        }
    }
    if ($pruneCamperDups && empty($dbErr)) {
        // Grab camper data, and look for possible duplicates.
        $db = new DbConn();
        $result = $db->runQueryDirectly("SELECT * FROM campers ORDER BY edah_id, last, first, camper_id", $dbErr);
        if ($result) {
            $curMatchBases = array();
            while ($row = $result->fetch_assoc()) {
                $thisCamper = new Camper($row["first"], $row["last"], $row["edah_id"], $row["camper_id"], $row["email"], $edahId2Name);
                // Step through the current match bases, and compare to this camper.  If
                // a base does not match, remove it from the current bases.  Otherwise, add the
                // potential match to the main list.
                $newMatchBases = array();
                for ($i = 0; $i < count($curMatchBases); $i++) {
                    $cmb = $curMatchBases[$i];
                    if (potentialDup($cmb, $thisCamper)) {
                        array_push($potentialCamperDups, array($cmb, $thisCamper));
                        array_push($newMatchBases, $cmb);
                    }
                }
                array_push($newMatchBases, $thisCamper);
                $curMatchBases = $newMatchBases;
            }
        }
        if ($confirmDelete) {
            // The values in $dupIndices correspond to indices into the
            // $potentialCamperDups array.
            if (count($dupIndices) == 0) {
                $nothingToDeleteError = "No potential duplicates were checked.";
            }
            foreach ($dupIndices as $idx) {
                $zug = $potentialCamperDups[$idx];
                // The car of the pair is the original registration, and the cdr
                // is the duplicate.  We want to merge the duplicate back to the
                // original.
                $orig = $zug[0];
                $dup = $zug[1];
                error_log("Merging dup ID " . $dup->camper_id . " for " .
                          $dup->printableName . " with " . $orig->camper_id .
                          " for " . $orig->printableName);
                // Step 1: merge dup prefs to orig.
                $db = new DbConn();
                $db->addColumn("camper_id", $orig->camper_id, 'i');
                $db->addWhereColumn("camper_id", $dup->camper_id, 'i');
                if (! $db->updateTable("preferences", $dbErr)) {
                    error_log("Failed to update preferences: $dbErr");
                    $didMergeOk = FALSE;
                    break;
                }
                // Step 2: merge dup matches to orig.
                $db = new DbConn();
                $db->addColumn("camper_id", $orig->camper_id, 'i');
                $db->addWhereColumn("camper_id", $dup->camper_id, 'i');
                if (! $db->updateTable("matches", $dbErr)) {
                    error_log("Failed to update matches: $dbErr");
                    $didMergeOk = FALSE;
                    break;
                }
                // Step 3: delete dup.
                $db = new DbConn();
                $db->addWhereColumn("camper_id", $dup->camper_id, 'i');
                if (! $db->deleteFromTable("campers", $dbErr)) {
                    error_log("Failed to delete from campers: $dbErr");
                    $didMergeOk = FALSE;
                    break;
                }
                $didMergeOk = TRUE;
                $numDeleted++;
            }
        }
    }

    echo headerText("Advanced Edit Page");
    $errText = genFatalErrorReport(array($dbErr, $nothingToDeleteError));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    
    $pl = "s";
    if ($numDeleted == 1) {
        $pl = "";
    }
    if ($didDeleteOk || $didMergeOk) {
        echo "<div class=\"centered_container\">";
        echo "<h3>Deletion Successful!</h3>";
        if ($didDeleteOk) {
            echo "<p>Successfully deleted $numDeleted obsolete assignment" . $pl . ".  ";
        } else if ($didMergeOk) {
            echo "<p>Successfully merged and deleted $numDeleted duplicate camper" . $pl . ".  ";
        }
        echo "Please click <a href=\"$homeUrl\">here</a> to exit, or wait 5 seconds to be automatically redirected.<p>";
        echo "</div>";
        echo "<script type=\"text/javascript\">";
        echo "setTimeout(function () { window.location.href= '$homeUrl'; },5000);";
        echo "</script>";
        echo footerText();
        echo "</body></html>";
        exit();
    }
?>

<div class="form_container">
<h1><a>Advanced Edit</a></h1>
<form id="editForm" method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Choose Categories to Prune</h2>
<p>Choose the category from which you would like to prune obsolete items.  You will be prompted to confirm before the system deletes anything.</p>
</div>

<?php
    $checkDefault = FALSE;
    if ($pruneMatches == FALSE &&
        $pruneCamperDups == FALSE) {
        $checkDefault = TRUE;
    }
    $pruneMatchesCheckBox = new FormItemRadio("Illegal Chug Assignments", FALSE, "radioGroup", 0);
    $pruneMatchesCheckBox->setGuideText("Check this box to delete chug matches that are not valid.");
    if ($pruneMatches || $checkDefault) {
        $pruneMatchesCheckBox->radioSetChecked();
    }
    $pruneMatchesCheckBox->setInputValue("prune_matches");
    echo $pruneMatchesCheckBox->renderHtml();
    
    $pruneCamperDupsCheckBox = new FormItemRadio("Duplicate Campers", FALSE, "radioGroup", 1);
    $pruneCamperDupsCheckBox->setGuideText("Check this box to compact and prune potential camper dups.");
    if ($pruneCamperDups) {
        $pruneCamperDupsCheckBox->radioSetChecked();
    }
    $pruneCamperDupsCheckBox->setInputValue("prune_camper_dups");
    echo $pruneCamperDupsCheckBox->renderHtml();
    
    // If we have data to confirm, display the data here.
    if (count($wouldBeDeleted) > 0) {
        echo "<li>";
        echo "<p><h3>Found the following illegal chug assignments.</h3> Hit \"Confirm Delete\" to remove them, or \"Cancel\" to exit.</p>";
        echo "<div class=\"confirm_delete_box\" >";
        foreach ($wouldBeDeleted as $wouldDelText) {
            echo "$wouldDelText<br>";
        }
        echo "</div>";
        echo "</li>";
    } else if ($pruneMatches) {
        echo "<li>";
        echo "<div class=\"confirm_delete_box\" >";
        echo "<h3>No obsolete assignments were found to delete.</h3>";
        echo "</div>";
        echo "</li>";
    } else if (count($potentialCamperDups) > 0) {
        echo "<li>";
        echo "<div class=\"checkbox checkbox-primary\">";
        echo "<p><h3>The following camper registrations might be duplicates.</h3> Check the ones that really are duplicates, then click \"Merge and Prune\", or click \"Cancel\" to exit.</p>";
        echo "<ul>";
        for ($i = 0; $i < count($potentialCamperDups); $i++) {
            $zug = $potentialCamperDups[$i];
            $c1 = $zug[0];
            $c2 = $zug[1];
            echo "<li><input type=\"checkbox\" name=\"dup_index[]\" value=\"$i\" >" . $c1->desc() . "  <b>-></b>  " . $c2->desc() . "</li>";
        }
        echo "</ul>";
        echo "</div>";
        echo "</li>";
    } else if ($pruneCamperDups) {
        echo "<li>";
        echo "<div class=\"confirm_delete_box\" >";
        echo "<h3>No duplicate camper registrations were found.</h3>";
        echo "</div>";
        echo "</li>";
    }
    
    echo "<li class=\"buttons\">";
    $cancelUrl = homeUrl();
    if (count($wouldBeDeleted) > 0) {
        echo "<input class=\"btn btn-default\" type=\"submit\" name=\"submit\" value=\"Confirm Delete\" />";
        echo "<input type=\"hidden\" name=\"confirm_delete\" value=\"1\" />";
    } else if (count($potentialCamperDups) > 0) {
        echo "<input class=\"btn btn-default\" type=\"submit\" name=\"submit\" value=\"Merge and Prune\" />";
        echo "<input type=\"hidden\" name=\"confirm_delete\" value=\"1\" />";
    } else {
        echo "<input class=\"btn btn-default\" type=\"submit\" name=\"submit\" value=\"Submit\" />";
        echo "<input type=\"hidden\" name=\"confirm_delete\" value=\"0\" />";
    }
    echo "<a href=\"$cancelUrl\">Cancel</a>";
    echo "<br><br></li></ul>";
    echo "</form></div>";
    echo footerText();
?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>






        