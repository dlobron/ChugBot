<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = $edah_id = "";
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
        $edah_id = test_input($_POST["edah_id"]);
        if (empty($name)) {
            $nameErr = errorString("Name is required");
        }
        
        if (empty($nameErr)) {
            // Get the ID (primary key) for the name that was edited.  The database
            // enforces name uniqueness.
            if (empty($edah_id)) {
                $sql = "SELECT edah_id FROM edot WHERE name=\"$name\"";
                $result = $mysqli->query($sql);
                if ($result == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                } else if ($result->num_rows == 0) {
                    $dbErr = dbErrorString($sql, "Error: edah $name not found");
                } else {
                    $row = $result->fetch_array(MYSQLI_NUM);
                    $edah_id = $row[0];
                }
                mysqli_free_result($result);
            }
            $homeAnchor = staffHomeAnchor();
            $addAnother = urlBaseText() . "/addEdah.php";
            if (empty($edah_id)) {
                $dbErr = dbErrorString($sql, "Failed to add/update edah $name: could not find in database.");
            } else if ($submitData == TRUE) {
                // Insert edited data.
                $edahIdNum = intval($edah_id);
                $sql =
                "UPDATE edot SET name = \"$name\" " .
                "WHERE edah_id = $edahIdNum";
                $submitOk = $mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                } else {
                    $addedStr =
                    "<h3>$name updated!  Please edit below if needed, or return $homeAnchor.  " .
                    "To add another edah, please click <a href=\"$addAnother\">here</a>.</h3>";
                }
            } else if ($fromAddPage) {
                $addedStr =
                "<h3>$name added successfully!  Please edit below if needed, or return $homeAnchor.  " .
                "To add another edah, please click <a href=\"$addAnother\">here</a>.</h3>";
            }
        }
    }
    
    $mysqli->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Edit Edah</title>
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
<?php
    echo genSuccessMessage($addedStr);
    ?>

<body id="main_body" >

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Edit Edah</a></h1>
<form id="form_1063607" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Edit Edah</h2>
<p>Please update edah information as needed (<font color="red">*</font> = required field)</p>
</div>
<ul >

<li id="li_1" >
<label class="description" for="name">Edah Name</label>
<div>
<input id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
<p class="guidelines" id="guide_1"><small>Choose your edah name (Kochavim, Ilanot 1, etc.)</small></p>
</div>
</li>

<li class="buttons">
<input type="hidden" name="form_id" value="1063607" />
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
<?php echo staffHomeAnchor("Cancel"); ?>
</li>
</ul>
<input type="hidden" name="edah_id" id="edah_id" value="<?php echo $edah_id;?>"/>
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
