<?php
    session_start();
    include_once 'functions.php';
    include_once 'dbConn.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    $homeUrl = urlIfy("staffHome.php");
    $dbErr = "";
    $numDeleted = 0;
    $pruneMatches = FALSE;
    $prunePrefs = FALSE;
    $confirmDelete = FALSE;
    $didDeleteOk = FALSE;
    $wouldBeDeleted = array();
    $deleteFromMatchesIds = array();
    if ($_GET) {
        $pruneMatches = test_input($_GET["prune_matches"]);
        $prunePrefs = test_input($_GET["prune_prefs"]);
        $confirmDelete = test_input($_GET["confirm_delete"]);
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
    // The query below finds illegal camper prefs, but it takes a very long time to run: each choice level took about 40
    // seconds on my laptop, with about 3300 prefs in the system.  I don't think there's ever much reason to prune illegal
    // preferences, so I'm commenting this out for now.  If we ever decide we need to be able to prune illegal prefs, we
    // should work to make this query faster, and then uncomment this section and also the prunePrefsCheckBox section below.
    /*
    if ($prunePrefs && empty($dbErr)) {
        $template = "SELECT p.preference_id pref_id, p.CHOICECOL choice_id, legal_choice_n.preference_id legal_pref_id, 'CHOICECOL' col, " .
        "CONCAT(c.first, ' ', c.last) camper_name, b.name block_name, g.name group_name " .
        "FROM " .
        "preferences p " .
        "JOIN campers c ON c.camper_id = p.camper_id " .
        "JOIN groups g ON g.group_id = p.group_id " .
        "JOIN blocks b ON b.block_id = p. block_id " .
        "LEFT OUTER JOIN " .
        "(SELECT p.preference_id preference_id, p.CHOICECOL CHOICECOL, p.camper_id camper_id, p.group_id group_id, p.block_id block_id FROM " .
        "preferences p, campers c, edot_for_block eb, edot_for_chug ec, chugim ch, edot_for_group eg WHERE " .
        "p.camper_id = c.camper_id AND c.edah_id = ec.edah_id AND ec.chug_id = p.CHOICECOL AND " .
        "p.block_id = eb.block_id AND eb.edah_id = c.edah_id AND eb.edah_id = c.edah_id AND " .
        "eg.group_id = ch.group_id AND eg.edah_id = c.edah_id AND " .
        "ch.group_id = p.group_id) legal_choice_n " .
        "ON p.preference_id = legal_choice_n.preference_id AND " .
        "p.CHOICECOL = legal_choice_n.CHOICECOL AND " .
        "p.camper_id = legal_choice_n.camper_id AND " .
        "p.group_id = legal_choice_n.group_id AND " .
        "p.block_id = legal_choice_n.block_id " .
        "GROUP BY pref_id";
        $choices = array("first_choice_id", "second_choice_id", "third_choice_id", "fourth_choice_id", "fifth_choice_id", "sixth_choice_id");
        $deleteHash = array();
        foreach ($choices as $choice) {
            $sql = preg_replace('/CHOICECOL/', $choice, $template);
            $db = new DbConn();
            error_log("DBG: Running $sql");
            $result = $db->runQueryDirectly($sql, $dbErr);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $pref_id = $row["pref_id"];
                    $choice_id = $row["choice_id"];
                    $legal_pref_id = $row["legal_pref_id"];
                    $camper_name = $row["camper_name"];
                    $block_name = $row["block_name"];
                    $group_name = $row["group_name"];
                    $col = $row["col"];
                    // Look for rows where choice_id is not NULL, but legal_pref_id is NULL.
                    if ($choice_id !== NULL &&
                        $legal_pref_id === NULL) {
                        if (! array_key_exists($pref_id, $deleteHash)) {
                            $deleteHash[$pref_id] = array();
                        }
                        array_push($deleteHash[$pref_id], $col);
                        array_push($wouldBeDeleted, "$camper_name: $col ($block_name, $group_name)");
                    }
                }
            }
        }
        if ($confirmDelete) {
            foreach ($deleteHash as $pref_id => $cols_to_null) {
                foreach ($cols_to_null as $col) {
                    $db = new DbConn();
                    $db->addColumn($col, NULL, 's');
                    $db->addWhereColumn("preference_id", $pref_id, 'i');
                    if ($db->updateTable("preferences", $dbErr)) {
                        error_log("Removed preference OK");
                        $didDeleteOk = TRUE;
                    } else {
                        error_log("Failed to delete obsolete preference: $dbErr");
                        $didDeleteOk = FALSE;
                        break;
                    }
                }
            }
        }
    }
     */
    
    echo headerText("Advanced Edit Page");
    $errText = genFatalErrorReport(array($dbErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    
    if ($didDeleteOk) {
        echo "<div class=\"centered_container\">";
        echo "<h3>Deletion Successful!</h3>";
        echo "<p>Successfully deleted $numDeleted obsolete entries.  Please click <a href=\"$homeUrl\">here</a> to exit, or wait 5 seconds to be automatically redirected.<p>";
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
<form id="editForm" class="appnitro"  method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Choose Categories to Prune</h2>
<p>Choose the categories from which you would like to prune obsolete items.  You will be prompted to confirm before the system deletes anything.</p>
</div>

<?php
    $pruneMatchesCheckBox = new FormItemCheckBox("Chug Assignments", FALSE, "prune_matches", 0);
    $pruneMatchesCheckBox->setGuideText("Check this box to delete chug matches that are not valid.");
    $pruneMatchesCheckBox->setInputValue($pruneMatches);
    echo $pruneMatchesCheckBox->renderHtml();
    
    /*
    $prunePrefsCheckBox = new FormItemCheckBox("Preferences", FALSE, "prune_prefs", 1);
    $prunePrefsCheckBox->setGuideText("Check this box to delete chug preferences that are not valid.");
    $prunePrefsCheckBox->setInputValue($prunePrefs);
    echo $prunePrefsCheckBox->renderHtml();
     */
    
    // If we have data to confirm, display the data here.
    if (count($wouldBeDeleted) > 0) {
        echo "<li>";
        echo "<h3>The following chug assignments would be permanently deleted. Hit \"Confirm Delete\" to delete them, or \"Cancel\" to exit this page without deleting anything.</h3>";
        echo "<div class=\"confirm_delete_box\" >";
        foreach ($wouldBeDeleted as $wouldDelText) {
            echo "$wouldDelText<br>";
        }
        echo "</div>";
        echo "</li>";
    } else if ($pruneMatches) {
        echo "<li>";
        echo "<div class=\"confirm_delete_box\" >";
        echo "<h3>No obsolete items were found to delete.</h3>";
        echo "</div>";
        echo "Click <a href=\"$homeUrl\">here</a> to exit.";
        echo "</li>";
    }
    
    echo "<li class=\"buttons\">";
    $cancelUrl = homeUrl();
    if (count($wouldBeDeleted) > 0) {
        echo "<input class=\"button_text\" type=\"submit\" name=\"submit\" value=\"Confirm Delete\" />";
        echo "<input type=\"hidden\" name=\"confirm_delete\" value=\"1\" />";
    } else {
        echo "<input class=\"button_text\" type=\"submit\" name=\"submit\" value=\"Submit\" />";
        echo "<input type=\"hidden\" name=\"confirm_delete\" value=\"0\" />";
    }
    echo "<a href=\"$cancelUrl\">Cancel</a>";
    echo "</li></ul>";
    echo "</form></div>";
    echo footerText();
?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>






        