<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = $session_id = "";
    $nameErr = $dbErr = "";
    $sessionIdsForBlock = array();
    $sessionId2Name = array();
    
    $mysqli = connect_db();
    fillId2Name($mysqli, $sessionId2Name, $dbErr,
                "session_id", "sessions");    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = test_input($_POST["name"]);
        if (empty($name)) {
            $nameErr = errorString("Block name is required");
        }
        if (! empty($_POST['session_ids'])) {
            foreach ($_POST['session_ids'] as $session_id) {
                $sessionId = test_input($session_id);
                if (empty($sessionId)) {
                    continue;
                }
                $sessionIdsForBlock[$sessionId] = 1;
            }
        }
        if (empty($nameErr) && empty($dbErr)) {
            $sql = "INSERT INTO blocks (name) VALUES (\"$name\")";
            $submitOk = $mysqli->query($sql);
            if ($submitOk == FALSE) {
                $dbErr = dbErrorString($sql, $mysqli->error);
            }
            $blockIdNum = $mysqli->insert_id;
            updateBlockInstances($mysqli,
                                 $sessionIdsForBlock,
                                 $submitOk,
                                 $dbErr,
                                 $blockIdNum);
            if ($submitOk == TRUE) {
                // Note that we need to use the name, not the ID here.
                $paramHash = array("name" => $name,
                                   "session_ids[]" => array_keys($sessionIdsForBlock));
                echo(genPassToEditPageForm("editBlock.php", $paramHash));
            }
        }
    }
    
    $mysqli->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Add Block</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php
    $errText = genFatalErrorReport(array($dbErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<body id="main_body" >

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Add Block</a></h1>
<form id="form_1063608" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Add Block</h2>
<p>Please enter information for this block (<font color="red">*</font> = required field).</p>
<p>A block is a time period for an activity: for example, weeks 1-2 of July.  Each
block is associated with one or more sessions: for example, "July 1" might be associated with July and July+August sessions
(a session is the unit of time that a camper signs up for).  You can add or edit sessions for this block later if you are
not sure right now which sessions to assign.</p>
</div>
<ul>

<li id="li_1" >
<label class="description" for="name"><font color="red">*</font> Block Name</label>
<div>
<input id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
<p class="guidelines" id="guide_1"><small>Choose a name for this block ("July Week 1", "Mini Session Aleph", etc.)</small></p>
</div>
</li>

<li id="li_2" >
<label class="description" for="session_ids"> Sessions</label>
<div>
<?php
    echo genCheckBox($sessionId2Name, $sessionIdsForBlock, "session_ids");
    ?>
</select>
</div><p class="guidelines" id="guide_5"><small>Choose each session that contains this block (you can do this later if you are not sure now).</small></p>
</li>

<li class="buttons">
<input type="hidden" name="fromAddPage" id="fromAddPage" value="1" />
<input type="hidden" name="form_id" value="1063608" />
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
