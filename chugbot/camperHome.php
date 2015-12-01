<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Camper/Family Home</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php include 'functions.php';?>

<?php
    session_start();
?>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<script type="text/javascript" src="meta/tooltip.js">
</script>

<body id="main_body" >

<img id="top" src="meta/top.png" alt="">
<div id="form_container">

<h1><a>Camper Home</a></h1>
<h3>Welcome to ChugBot, campers and families!</h3>
<p>ChugBot is an online system where campers and families can order their chug (activity) preferences for the summer.</p>
<p>To add
preferences for a camper, please click the "Add" button.</p>
<p>To edit camper preferences that have already  been entered, please enter the email address associated with the camper and click "Edit".</p>

<form id="choiceForm" class="appnitro" method="post" />
<button type="submit" name="edit" formaction="preEditCamper.php" >Edit Camper</button>
<span>
<input placeholder="Email associated with camper" id="email" name="email" class="element text" maxlength="255" size="50"
class="masterTooltip" title="Enter the email associated with the camper you would like to edit">
</span>
<br><br>
<input type="hidden" id="fromHome" name="fromHome" value="1" />

<button type="submit" name="add" formaction="addCamper.php" >Add Camper</button>
</form>

<div id="footer">
<?php
    echo footerText();
?>
</div>
</div>
<img id="bottom" src="meta/bottom.png" alt="">
</body>
</html>
