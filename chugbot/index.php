<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>ChugBot Home</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php include 'functions.php';?>

<?php
    session_start();
?>

<body id="main_body" >

<img id="top" src="meta/top.png" alt="">
<div id="form_container">

<h1><a>Welcome</a></h1>
<h3>Welcome to ChugBot!</h3>
<i>Please click a button to get started</i>

<form id="choiceForm" class="appnitro" method="post" />
<button type="submit" name="camperInit" form="choiceForm" formaction="camperHome.php" >Campers and Families</button>
<br><br>
<button type="submit" name="staffInit" form="choiceForm" formaction="staffLogin.php" value="1">Admin Staff</button>
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
