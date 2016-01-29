<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = $bunk_id = "";
    $nameErr = $dbErr = $addedStr = "";
    $edahIdsForBunk = array();
    $edahId2Name = array();
    $submitData = FALSE;
    $fromAddPage = FALSE;
    
    $mysqli = connect_db();
    fillId2Name($mysqli, $edahId2Name, $dbErr,
                "edah_id", "edot");
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = test_input($_POST["name"]);
        if (empty($name)) {
            $nameErr = errorString("Bunk name/number is required");
        }
        if (! empty($_POST["fromAddPage"])) {
            $fromAddPage = TRUE;
        }
        if (! empty($_POST["submitData"])) {
            $submitData = TRUE;
        }
        $bunk_id = test_input($_POST["bunk_id"]);
        
        // If coming from staff home page, parse name and ID.
        if (isset($_POST["fromStaffHomePage"])) {
            getIdAndNameFromHomeString($name, $bunk_id, $name,
                                       $mysqli, $dbErr);
        }
        
        if (empty($bunk_id)) {
            $nameErr = dbErrorString($sql, "Failed to add/update bunk $name: need bunk ID.");
        }
        
        if (! empty($_POST['edah_ids'])) {
            foreach ($_POST['edah_ids'] as $edah_id) {
                $edahId = test_input($edah_id);
                if (empty($edahId)) {
                    continue;
                }
                $edahIdsForBunk[$edahId] = 1;
            }
        }
        if (empty($nameErr) && empty($dbErr)) {
            if (empty($_POST['edah_ids'])) {
                // If no active edah IDs were submitted, we need to get the active
                // edah IDs from the database.
                populateActiveInstances($mysqli,
                                        $edahIdsForBunk,
                                        $dbErr,
                                        "bunk_id",
                                        $bunk_id,
                                        "edah_id",
                                        "bunk_instances");
            }
            $homeAnchor = staffHomeAnchor();
            $addAnother = urlBaseText() . "/addBunk.php";
            if ($submitData == TRUE) {
                // Update the bunk name, if needed.
                $bunkIdNum = intval($bunk_id);
                $sql =
                "UPDATE bunks SET name = \"$name\" " .
                "WHERE bunk_id = $bunkIdNum";
                $submitOk = $mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                }
                // Update the instances of this bunk.
                updateActiveInstances($mysqli,
                                      $edahIdsForBunk,
                                      $submitOk,
                                      $dbErr,
                                      "bunk_id",
                                      $bunkIdNum,
                                      "edah_id",
                                      "bunk_instances");
                if ($submitOk) {
                    $addedStr =
                    "<h3>$name updated!  Please edit below if needed, or return $homeAnchor.  " .
                    "To add another bunk, please click <a href=\"$addAnother\">here</a>.</h3>";
                }
            } else if ($fromAddPage) {
                $addedStr =
                "<h3>$name added successfully!  Please edit below if needed, or return $homeAnchor.  " .
                "To add another bunk, please click <a href=\"$addAnother\">here</a>.</h3>";
            }
        }
    }
    
    $mysqli->close();
?>

<?php
    echo headerText("Edit Bunk);
    
    $errText = genFatalErrorReport(array($dbErr, $nameErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>
<?php
    echo genSuccessMessage($addedStr);
    ?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Edit Bunk</a></h1>
<form id="form_1063621" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Edit Bunk</h2>
<p>Please edit information for this bunk (<font color="red">*</font> = required field).</p>
<p>A bunk has a name or number (required), and can optionally be associated with one or more Edot.</p>
</div>
<ul>

<li id="li_1" >
<label class="description" for="name"><font color="red">*</font> Bunk Name/Number</label>
<div>
<input id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
<p class="guidelines" id="guide_1"><small>Choose a name or number for this bunk ("1", "12", "Tikvah Village", etc.)</small></p>
</div>
</li>

<li id="li_2" >
<label class="description" for="edah_ids"> Edot</label>
<div>
<?php
    echo genCheckBox($edahId2Name, $edahIdsForBunk, "edah_ids");
    ?>
</select>
</div><p class="guidelines" id="guide_2"><small>Associate this bunk with one or more Edot (optional).</small></p>
</li>

<li class="buttons">

<input type="hidden" name="form_id" value="1063621" />
<input type="hidden" name="bunk_id" id="bunk_id" value="<?php echo $bunk_id;?>"/>
<input type="hidden" name="submitData" id="submitData" value="1" />
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
<?php echo staffHomeAnchor("Cancel"); ?>
</li>
</ul>
</form>
<div id="footer">
<?php
    echo footerText();
    ?>
</div>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
