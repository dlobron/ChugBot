<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = $session_id = $block_id = "";
    $nameErr = $dbErr = $addedStr = "";
    $sessionIdsForBlock = array();
    $sessionId2Name = array();
    $submitData = FALSE;
    $fromAddPage = FALSE;
    
    $mysqli = connect_db();
    fillId2Name($mysqli, $sessionId2Name, $dbErr,
                "session_id", "sessions");
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = test_input($_POST["name"]);
        if (empty($name)) {
            $nameErr = errorString("Block name is required");
        }
        if (! empty($_POST["fromAddPage"])) {
            $fromAddPage = TRUE;
        }
        if (! empty($_POST["submitData"])) {
            $submitData = TRUE;
        }
        $block_id = test_input($_POST["block_id"]);
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
            // Get the ID (primary key) for the name that was edited.  The database
            // enforces name uniqueness.
            if (empty($block_id)) {
                $sql = "SELECT block_id FROM blocks WHERE name=\"$name\"";
                $result = $mysqli->query($sql);
                if ($result == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                } else if ($result->num_rows == 0) {
                    $dbErr = dbErrorString($sql, "Error: block $name not found in database.");
                } else {
                    $row = $result->fetch_array(MYSQLI_NUM);
                    $block_id = $row[0];
                }
                mysqli_free_result($result);
            }
            if (empty($_POST['session_ids'])) {
                // If no active session IDs were submitted, we need to get the active
                // sessions IDs from the database.
                populateActiveInstances($mysqli,
                                        $sessionIdsForBlock,
                                        $dbErr,
                                        "block_id",
                                        $block_id,
                                        "session_id",
                                        "block_instances");
            }
            $homeAnchor = staffHomeAnchor();
            $addAnother = urlBaseText() . "/addBlock.php";
            if (empty($block_id)) {
                $dbErr = dbErrorString($sql, "Failed to add/update block $name: need block ID.");
            } else if ($submitData == TRUE) {
                // Update the block name, if needed.
                $blockIdNum = intval($block_id);
                $sql =
                "UPDATE blocks SET name = \"$name\" " .
                "WHERE block_id = $blockIdNum";
                $submitOk = $mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                }
                // Update the instances of this block: we need to insert
                // new instances and delete ones that no longer exist.
                // For now, we do this by deleting all instances of the block, and inserting
                // all the incoming ones.
                updateBlockInstances($mysqli,
                                     $sessionIdsForBlock,
                                     $submitOk,
                                     $dbErr,
                                     $blockIdNum,
                                     array());
                if ($submitOk) {
                    $addedStr =
                    "<h3>$name updated!  Please edit below if needed, or return $homeAnchor.  " .
                    "To add another block, please click <a href=\"$addAnother\">here</a>.</h3>";
                }
            } else if ($fromAddPage) {
                $addedStr =
                "<h3>$name added successfully!  Please edit below if needed, or return $homeAnchor.  " .
                "To add another block, please click <a href=\"$addAnother\">here</a>.</h3>";
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
    echo $dbErr;
    echo $nameErr;
    echo $addedStr;
    ?>

<body id="main_body" >

<img id="top" src="images/top.png" alt="">
<div id="form_container">

<h1><a>Edit Block</a></h1>
<form id="form_1063610" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Edit Block</h2>
<p>Please edit information for this block (<font color="red">*</font> = required field).</p>
<p>A block is a time period for an activity: for example, weeks 1-2 of July.  Each
block is associated with one or more sessions: for example, "July 1" might be associated with July and July+August sessions
(a session is the unit of time that a camper signs up for).</p>
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
</div><p class="guidelines" id="guide_5"><small>Choose each session that contains this block.</small></p>
</li>

<li class="buttons">

<input type="hidden" name="form_id" value="1063610" />
<input type="hidden" name="block_id" id="block_id" value="<?php echo $block_id;?>"/>
<input type="hidden" name="submitData" id="submitData" value="1" />
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
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
