<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = "";
    $nameErr = $dbErr = "";
    
    $mysqli = connect_db();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = test_input($_POST["name"]);
        if (empty($name)) {
            $nameErr = errorString("Name is required");
        }
        if (empty($nameErr)) {
            $mysqli = connect_db();
            $sql = "INSERT INTO groups (name) VALUES (\"$name\");";
            $submitOk = $mysqli->query($sql);
            if ($submitOk == FALSE) {
                $dbErr = dbErrorString($sql, $mysqli->error);
            }
            $group_id = $mysqli->insert_id;
            if ($submitOk == TRUE) {
                $paramHash = array("group_id" => $group_id,
                                   "name" => $name);
                echo(genPassToEditPageForm("editGroup.php", $paramHash));
            }
        }
    }
    
    $mysqli->close();
?>

<?php
    echo headerText("Add Edah");
    
    $errText = genFatalErrorReport(array($dbErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Add Group</a></h1>
<form id="form_1063611" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Add Group</h2>
<p>Please enter your group information (<font color="red">*</font> = required field)</p>
</div>
<ul >

<li id="li_1" >
<label class="description" for="name"><font color="red">*</font> Group Name</label>
<div>
<input id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
<p class="guidelines" id="guide_1"><small>Choose a group name (aleph, bet, gimel, etc.)</small></p>
</div>
</li>

<li class="buttons">
<input type="hidden" name="form_id" value="1063611" />
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
