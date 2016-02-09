<?php
    include 'functions.php';
    session_start();
    
    echo headerText("Camper/Family Home");
?>

<script type="text/javascript" src="jquery/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="meta/tooltip.js"></script>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Camper Home</a></h1>
<h3>Welcome to ChugBot, campers and families!</h3>
<p>ChugBot is an online system where campers and families can order their chug (activity) preferences for the summer.</p>
<p>To add a camper to the system, please click the "Add" button.</p>
<p>To edit camper information that has already been entered, please enter the email address associated with that camper and click "Edit".</p>

<form class="appnitro" id="choiceForm" method="post" />
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
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
