<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = $rosh_name = $rosh_phone = $comments = "";
    $nameErr = $dbErr = "";
    $fromStaffHomePage = FALSE;
    
    $mysqli = connect_db();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["fromStaffHomePage"])) {
            $fromStaffHomePage = TRUE;
            $_POST["name"] = ""; // Unset vars, in case they were selected.
            $_POST["rosh_name"] = "";
            $_POST["rosh_phone"] = "";
            $_POST["comments"] = "";
        }
        $name = test_input($_POST["name"]);
        $rosh_name = test_input($_POST["rosh_name"]);
        $rosh_phone = test_input($_POST["rosh_phone"]);
        $comments = test_input($_POST["comments"]);
        
        if (empty($name) &&
            $fromStaffHomePage == FALSE) {
            $nameErr = errorString("Name is required");
        }
        if (empty($nameErr) &&
            $fromStaffHomePage == FALSE) {
            $mysqli = connect_db();
            $sql = "INSERT INTO edot (name, rosh_name, rosh_phone, comments) " .
            "VALUES (\"$name\", \"$rosh_name\", \"$rosh_phone\", \"$comments\");";
            $submitOk = $mysqli->query($sql);
            if ($submitOk == FALSE) {
                $dbErr = dbErrorString($sql, $mysqli->error);
            }
            if ($submitOk == TRUE) {
                $paramHash = array("name" => $name,
                                   "edah_id" => $edah_id,
                                   "rosh_name" => $rosh_name,
                                   "rosh_phone" => $rosh_phone,
                                   "comments" => $comments);
                echo(genPassToEditPageForm("editEdah.php", $paramHash));
            }
        }
    }
    
    $mysqli->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Add Edah</title>
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

<h1><a>Add Edah</a></h1>
<form id="form_1063615" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Add Edah</h2>
<p>Please enter your edah information (<font color="red">*</font> = required field)</p>
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

<li id="li_2" >
<label class="description" for="name">Rosh Edah (head counselor) Name</label>
<div>
<input id="rosh_name" name="rosh_name" class="element text medium" type="text" maxlength="255" value="<?php echo $rosh_name;?>"/>
<p class="guidelines" id="guide_2"><small>Enter the head counselor name (optional)</small></p>
</div>
</li>

<li id="li_3" >
<label class="description" for="name">Rosh Edah Phone</label>
<div>
<input id="rosh_phone" name="rosh_phone" class="element text medium" type="text" maxlength="255" value="<?php echo $rosh_phone;?>"/>
<p class="guidelines" id="guide_3"><small>Phone number for the head counselor (optional)</small></p>
</div>
</li>

<li id="li_4" >
<label class="description" for="name">Comments</label>
<div>
<textarea id="comments" name="comments" class="element textarea medium" value="<?php echo $comments;?>"></textarea>
<p class="guidelines" id="guide_4"><small>Comments about this Edah (optional)</small></p>
</div>
</li>

<li class="buttons">
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
