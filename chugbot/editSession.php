<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = $session_id = $addedMsg = "";
    $nameErr = $dbErr = "";
    $submitData = FALSE;
    $fromAddPage = FALSE;
    
    $mysqli = connect_db();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (! empty($_POST["fromAddPage"])) {
            $fromAddPage = TRUE;
        }
        if (! empty($_POST["submitData"])) {
            $submitData = TRUE;
        }
        $name = test_input($_POST["name"]);
        $session_id = test_input($_POST["session_id"]);
        if (empty($name)) {
            $nameErr = errorString("Name is required");
        }
        if (isset($_POST["fromStaffHomePage"])) {
            getIdAndNameFromHomeString($name, $session_id, $name,
                                       $mysqli, $dbErr);
        }
        if (empty($session_id)) {
            $nameErr = errorString("ID is required");
        }
        
        if (empty($nameErr)) {            
            $mysqli = connect_db();
            $homeAnchor = staffHomeAnchor();
            $addAnother = urlBaseText() . "/addSession.php";
            if (session_id == -1) {
                $errStr = errorString("Failed to add/update session");
            } else if ($submitData == TRUE) {
                // Insert edited data.
                $sql =
                "UPDATE sessions SET name = \"$name\" " .
                "WHERE session_id = \"$session_id\"";
                $submitOk = $mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                } else {
                    $addedMsg = "<h3>$name updated!  Please edit below if needed, or " .
                    "return $homeAnchor.  To add another session, please click <a href=\"$addAnother\">here</a>.</h3>";
                }
            } else if ($fromAddPage) {
                $addedMsg = "<h3>$name added successfully!  Please edit below if needed, or " .
                "return $homeAnchor.  To add another session, please click <a href=\"$addAnother\">here</a>.</h3>";
            }
        }
    }
    
    $mysqli->close();
?>

<?php
    echo headerText("Edit Session");
    
    $errText = genFatalErrorReport(array($dbErr, $nameErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>
<?php
    echo genSuccessMessage($addedMsg);
    ?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Edit Session</a></h1>
<form id="form_1063617" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Edit Session</h2>
<p>Please update session information as needed (<font color="red">*</font> = required field)</p>
</div>
<ul >

<li id="li_1" >
<label class="description" for="name">Session Name</label>
<div>
<input id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
<p class="guidelines" id="guide_1"><small>Edit session name here as needed.</small></p>
</div>
</li>

<li class="buttons">
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
<?php echo staffHomeAnchor("Cancel"); ?>
</li>
</ul>
<input type="hidden" name="session_id" id="session_id" value="<?php echo $session_id;?>"/>
<input type="hidden" name="submitData" value="1">
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
