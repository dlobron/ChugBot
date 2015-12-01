<?php
    session_start();
    include 'functions.php';
    bounceToLogin();

    // Define variables and set to empty values.
    $name = $group_id = $max_size = $min_size = "";
    $nameErr = $groupIdErr = $dbErr = $minMaxErr = "";
    $fromHome = FALSE;
    $activeBlockIds = array();
    
    // Connect to the database.
    $mysqli = connect_db();
    
    // Grab blocks and groups.
    $blockId2Name = array();
    $groupId2Name = array();
    fillId2Name($mysqli, $blockId2Name, $dbErr,
                "block_id", "blocks");
    fillId2Name($mysqli, $groupId2Name, $dbErr,
                "group_id", "groups");
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $maxSizeNum = MAX_SIZE_NUM;     // Default "no max" value.
        $minSizeNum = MIN_SIZE_NUM;     // Default "no min" value.
        $name = test_input($_POST["name"]);
        $group_id = test_input($_POST["group_id"]);
        $max_size = test_input($_POST["max_size"]);
        $min_size = test_input($_POST["min_size"]);
        if (! empty($_POST["fromHome"])) {
            $fromHome = TRUE;
        }
        // Get the blocks in which this chug is active, storing their IDs in $blockIds.
        // We don't require a user to assign blocks - the user can do so later via the edit page.
        if (array_key_exists("active_blocks", $_POST)) {
            foreach ($_POST['active_blocks'] as $active_block) {
                $blockId = test_input($active_block);
                $activeBlockIds[$blockId] = 1; // Use a hash, so we can check membership quickly below.
            }
        }
        if (! empty($max_size)) {
            $maxSizeNum = intval($max_size);
        }
        if (! empty($min_size)) {
            $minSizeNum = intval($min_size);
        }
        if ($minSizeNum > $maxSizeNum) {
            $minMaxErr = errorString("Minimum must not be larger than maximum.");
        }
        // For required inputs, throw an error if not present.  Exception: do
        // not set errors if we're coming from the home page, since the user won't
        // have set anything there.
        if ($fromHome == FALSE) {
            if (empty($group_id)) {
                $groupIdErr = errorString("Please choose a group for this chug.");
            }
            if (empty($name)) {
                $nameErr = errorString("Chug name is required");
            }
        }
        
        if (empty($dbErr) && empty($groupIdErr) && empty($nameErr) && empty($minMaxErr)) {
            $groupIdNum = intval($group_id);
            
            // First, we insert the chug into the chugim table.  We then get
            // its ID, and update chug_instances.
            $sql =
            "INSERT INTO chugim (name, group_id, max_size, min_size) " .
            "VALUES (\"$name\", $groupIdNum, $maxSizeNum, $minSizeNum)";
            $submitOk = $mysqli->query($sql);
            if ($submitOk == FALSE) {
                $dbErr = dbErrorString($sql, $mysqli->error);
            }
            $chugIdNum = $mysqli->insert_id;
            $blockIds = array();
            foreach ($activeBlockIds as $blockIdStr => $activeFlag) {
                if (! empty($dbErr)) {
                    break;
                }
                array_push($blockIds, $blockIdStr);
                // For each block, create an instance of this chug.
                $blockIdNum = intval($blockIdStr);
                $sql =
                "INSERT INTO chug_instances (chug_id, block_id) " .
                "VALUES ($chugIdNum, $blockIdNum)";
                $submitOk = $mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                }
            }
            if (empty($dbErr)) {
                $paramHash = array("chug_id" => strval($chugIdNum),
                                   "active_blocks[]" => $blockIds);
                echo(genPassToEditPageForm("editChug.php", $paramHash));
            }
        }
    }
    
    $mysqli->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Add Chug Page</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<body id="main_body" >

<?php
    echo "$dbErr";
    ?>

<img id="top" src="images/top.png" alt="">
<div id="form_container">

<h1><a>Add a Chug</a></h1>
<form id="form_1063609" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Add a Chug</h2>
<p>Please enter chug information here (<font color="red">*</font> = required field).</p>
</div>
<ul>

<li id="li_1" >
<label class="description" for="name"><font color="red">*</font> Chug Name</label>
<div>
<input placeholder="Chug name" id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
</li>

<li id="li_2" >
<label class="description" for="group_id"><font color="red">*</font> Group</label>
<div>
<select class="element select medium" id="group_id" name="group_id">
<?php
    echo genPickList($groupId2Name, $group_id, "group");
    ?>
</select>
<span class="error"><?php echo $groupErr;?></span>
</div><p class="guidelines" id="guide_2"><small>Please assign this chug to a group.</small></p>
<span class="error"><?php echo $groupIdErr;?></span>
</li>

<li id="li_3" >
<label class="description" for="active_blocks">Active Blocks</label>
<div>
<?php
    echo genCheckBox($blockId2Name, $activeBlockIds, "active_blocks");
    ?>
</select>
</div><p class="guidelines" id="guide_3"><small>Check each time block in which this chug is active (you can do this later if you are not sure).</small></p>
</li>

<li id="li_4" >
<label class="description" for="name"> Minimum participants</label>
<div>
<input id="min_size" name="min_size" class="element text medium" type="text" maxlength="4" value="<?php echo $min_size;?>"/>
</div><p class="guidelines" id="guide_4"><small>Enter the minimum number of campers needed for this chug to take place (optional: default = no minimum)</small></p>
</li>

<li id="li_5" >
<label class="description" for="name"> Max participants</label>
<div>
<input id="max_size" name="max_size" class="element text medium" type="text" maxlength="4" value="<?php echo $max_size;?>"/>
</div><p class="guidelines" id="guide_4"><small>Enter the maximum number of campers allowed in this chug at a time (optional: default = no size limit)</small></p>
<span class="error"><?php echo $minMaxErr;?></span>
</li>

<li class="buttons">
<input type="hidden" name="form_id" value="1063609" />
<input type="hidden" name="fromAddPage" id="fromAddPage" value="1" />

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
