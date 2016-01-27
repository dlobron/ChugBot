<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = $group_id = "";
    $nameErr = $dbErr = $addedStr = "";
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
        $group_id = test_input($_POST["group_id"]);
        if (empty($name)) {
            $nameErr = errorString("Name is required");
        }
        // If coming from staff home page, parse name and ID.
        if (isset($_POST["fromStaffHomePage"])) {
            getIdAndNameFromHomeString($name, $group_id, $name,
                                       $mysqli, $dbErr);
        }
        if (empty($group_id)) {
            $nameErr = errorString("ID is required");
        }
        
        if (empty($nameErr)) {
            $homeAnchor = staffHomeAnchor();
            $addAnother = urlBaseText() . "/addGroup.php";
            if ($submitData == TRUE) {
                // Insert edited data.
                $groupIdNum = intval($group_id);
                $sql =
                "UPDATE groups SET name = \"$name\" " .
                "WHERE group_id = $groupIdNum";
                $submitOk = $mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                } else {
                    $addedStr =
                    "<h3>$name updated!  Please edit below if needed, or return $homeAnchor.  " .
                    "To add another group, please click <a href=\"$addAnother\">here</a>.</h3>";
                }
            } else if ($fromAddPage) {
                $addedStr =
                "<h3>$name added successfully!  Please edit below if needed, or return $homeAnchor.  " .
                "To add another group, please click <a href=\"$addAnother\">here</a>.</h3>";
            }
        }
    }
    
    $mysqli->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Edit Group</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php
    $errText = genFatalErrorReport(array($dbErr, $nameErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>
<?php
    echo genSuccessMessage($addedStr);
    ?>

<body id="main_body" >

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Edit Group</a></h1>
<form id="form_1063618" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Edit Group</h2>
<p>Please update group information as needed (<font color="red">*</font> = required field)</p>
</div>
<ul >

<li id="li_1" >
<label class="description" for="name"><font color="red">*</font> Group Name</label>
<div>
<input id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
<p class="guidelines" id="guide_1"><small>Update your group name as needed.</small></p>
</div>
</li>

<li class="buttons">
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
<?php echo staffHomeAnchor("Cancel"); ?>
</li>
</ul>
<input type="hidden" name="group_id" id="group_id" value="<?php echo $group_id;?>"/>
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
