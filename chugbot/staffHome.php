<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    $dbErr = "";
    $sessionId2Name = array();
    $blockId2Name = array();
    $groupId2Name = array();
    $edahId2Name = array();
    $chugId2Name = array();
    
    $mysqli = connect_db();
    fillId2Name($mysqli, $chugId2Name, $dbErr,
                "chug_id", "chugim");
    fillId2Name($mysqli, $sessionId2Name, $dbErr,
                "session_id", "sessions");
    fillId2Name($mysqli, $blockId2Name, $dbErr,
                "block_id", "blocks");
    fillId2Name($mysqli, $groupId2Name, $dbErr,
                "group_id", "groups");
    fillId2Name($mysqli, $edahId2Name, $dbErr,
                "edah_id", "edot");
    
    ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Staff Home</title>
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

<div id="centered_container">
<h1>Admin Home</a></h1>
<h2>Camp Staff Control Panel</h2>
<p>From the left menus, you may add and edit Edot, Sessions, Blocks, Groups, and Chugim.  You may also view and edit campers according to edah.</p>
<p>The right menu launches the leveling bot for a specific Edah/Block/Group combination.</p>
<p>Please hover your mouse over a menu for further help.<p>
</div>

<div id="multi_form_container">
<?php echo genPickListForm($edahId2Name, "edah", "edot"); ?>
</div>

<div id="multi_form_container">
<?php echo genPickListForm($sessionId2Name, "session", "sessions"); ?>
</div>

<div id="multi_form_container">
<?php echo genPickListForm($blockId2Name, "block", "blocks"); ?>
</div>

<div id="multi_form_container">
<?php echo genPickListForm($groupId2Name, "group", "groups"); ?>
</div>

<div id="multi_form_container">
<?php echo genPickListForm($chugId2Name, "chug", "chugim"); ?>
</div>

<div id="footer">
<?php
    echo footerText();
?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
