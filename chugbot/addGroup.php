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
            if ($submitOk == TRUE) {
                $paramHash = array("name" => $name);
                echo(genPassToEditPageForm("editGroup.php", $paramHash));
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
    echo $dbErr;
    echo $nameErr;
    ?>

<body id="main_body" >

<img id="top" src="images/top.png" alt="">
<div id="form_container">

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
