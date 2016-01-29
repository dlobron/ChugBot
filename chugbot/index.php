<?php
    include 'functions.php';
    session_start();
    
    echo headerText("Welcome");
?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

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

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
