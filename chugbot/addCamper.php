<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Add Camper Page</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php include 'functions.php';?>

<?php

    // Define variables and set to empty values.
    $edah_id = $session_id = $first = $last = $email = "";
    $edahErr = $sessionErr = $nameErr = $emailErr = $dbErr = "";
    $fromHome = FALSE;
    
    // Connect to the database.
    $mysqli = connect_db();
    
    // Grab edot, and sessions.
    $edahId2Name = array();
    $sessionId2Name = array();
    fillId2Name($mysqli, $edahId2Name, $dbErr,
                "edah_id", "edot");
    fillId2Name($mysqli, $sessionId2Name, $dbErr,
                "session_id", "sessions");
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $edah_id = test_input($_POST["edah_id"]);
        $session_id = test_input($_POST["session_id"]);
        $first = test_input($_POST["first"]);
        $last = test_input($_POST["last"]);
        $email = test_input($_POST["email"]);
        if (! empty($_POST["fromHome"])) {
            $fromHome = TRUE;
        }
        // For required inputs, throw an error if not present.  Exception: do
        // not set errors if we're coming from the home page, since the user won't
        // have set anything there.
        if ($fromHome == FALSE) {
            if (empty($edah_id)) {
                $edahErr = errorString("edah is required");
            }
            if (empty($session_id)) {
                $sessionErr = errorString("session is required");
            }
            if (empty($first) || empty($last)) {
                $nameErr = errorString("please provide first and last name");
            }
            if (empty($email)) {
                $emailErr = errorString("please include an email address");
            } else if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailErr = errorString("$email is not a valid email address");
            }
        }
        
        if (empty($nameErr) && empty($emailErr) && empty($sessionErr) &&
            empty($edahErr) && empty($camperIdErr) &&
            ($fromHome == FALSE)) {
            // Convert string to int as needed, and insert.
            $sessionNum = intval($session_id);
            $edahNum = intval($edah_id);
            $sql =
            "INSERT INTO campers (edah_id, session_id, first, last, email, needs_first_choice, active) " .
            "VALUES (\"$edahNum\", \"$sessionNum\", \"$first\", \"$last\", \"$email\", " .
            "0, 1)"; // For add, always use defaults for first choice and active.  Administrators can edit these on the edit page.
            
            $submitOk = $mysqli->query($sql);
            if ($submitOk == FALSE) {
                echo(dbErrorString($sql, $mysqli->error));
            } else {
                $camper_id = strval($mysqli->insert_id);
                $paramHash = array("camper_id" => $camper_id);
                echo(genPassToEditPageForm("editCamper.php", $paramHash));
            }
        }
    }
    
    $mysqli->close();
?>

<body id="main_body" >

<?php
    echo $dbErr;
    ?>

<img id="top" src="images/top.png" alt="">
<div id="form_container">

<h1><a>Add a Camper!</a></h1>
<form id="form_1063605" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Add a Camper!</h2>
<p>Please enter your camper's information here (<font color="red">*</font> = required field).</p>
</div>
<ul>
<li id="li_1" >
<label class="description"><font color="red">*</font> Name</label>
<span>
<input placeholder="First Name" id="first" name="first" class="element text" maxlength="255" size="16" value="<?php echo $first;?>"/>
<label>First</label>
</span>
<span>
<input placeholder="Last Name" id="last" name="last" class="element text" maxlength="255" size="24" value="<?php echo $last;?>"/>
<label>Last</label>
</span>
<span class="error"><?php echo $nameErr;?></span>
</li>
<li id="li_3" >
<label class="description" for="email"><font color="red">*</font> Email</label>
<div>
<input placeholder="Email address"  id="email" name="email" class="element text medium" type="text" maxlength="255" value="<?php echo $email;?>"/>
<span class="error"><?php echo $emailErr;?></span>
</div>
<p class="guidelines" id="guide_3"><small>Please include an email address (<b>you can use the same email for more than one camper</b>)</small></p>
</li>
<li id="li_5" >
<label class="description" for="session_id"><font color="red">*</font> Session</label>
<div>
<select class="element select medium" id="session_id" name="session_id">
<?php
    echo genPickList($sessionId2Name, $session_id, "session");
    ?>
</select>
<span class="error"><?php echo $sessionErr;?></span>
</div><p class="guidelines" id="guide_5"><small>Choose your camp session.</small></p>
</li>
<li id="li_6" >
<label class="description" for="session_id"><font color="red">*</font> Edah</label>
<div>
<select class="element select medium" id="edah_id" name="edah_id">
<?php
    echo genPickList($edahId2Name, $edah_id, "edah");
    ?>
</select>
<span class="error"><?php echo $edahErr;?></span>
</div><p class="guidelines" id="guide_6"><small>Choose your edah for this summer!</small></p>
</li>

<li class="buttons">
<input type="hidden" name="form_id" value="1063605" />
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
