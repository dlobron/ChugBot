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
        if (empty($nameErr)) {
            // Get the ID (primary key) for the name that was edited.  The database
            // enforces name uniqueness.
            if (empty($session_id)) {
                $sql = "SELECT session_id FROM sessions WHERE name=\"$name\"";
                $result = $mysqli->query($sql);
                if ($result == FALSE) {
                    $dbErr = dbErrorString($sql, $mysqli->error);
                } else if ($result->num_rows == 0) {
                    $dbErr = dbErrorString($sql, "Error: session $name not found");
                } else {
                    $row = $result->fetch_array(MYSQLI_NUM);
                    $session_id = intval($row[0]);
                }
                mysqli_free_result($result);
            }
            
            $mysqli = connect_db();
            $homeAnchor = homeAnchor();
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

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Edit Edah</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php
    echo $nameErr;
    echo $dbErr;
    echo $addedMsg;
    ?>

<body id="main_body" >

<img id="top" src="images/top.png" alt="">
<div id="form_container">

<h1><a>Edit Session</a></h1>
<form id="form_1063607" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
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
<input type="hidden" name="form_id" value="1063607" />
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
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
