<?php
    include_once 'dbConn.php';
    include_once 'functions.php';
    session_start();
    
    echo headerText("Camper/Family Home");
    
    // If the user is not logged in as a camper, validate the incoming access
    // code.  If none is found, display an error message.
    $loginMessage = "";
    if (! camperLoggedIn()) {
        $db = new DbConn();
        $sql = "SELECT regular_user_token,regular_user_token_hint FROM admin_data";
        $err = "";
        $result = $db->runQueryDirectly($sql, $err);
        $code = $hint = "";
        if ($result) {
            $row = $result->fetch_assoc();
            $code = $row["regular_user_token"];
            $hint = $row["regular_user_token_hint"];
        }
        
        $accessCode = test_input($_POST['camper_code']);
        $n = strlen($accessCode);
        if ($accessCode &&
            strncasecmp($accessCode, $code, $n) == 0) {
            $_SESSION['camper_logged_in'] = TRUE;
            $loginMessage = "<h3><font color=\"green\">Login successful!</font></h3>";
        } else {
            $homeUrl = urlIfy("index.php?retry=1");
            $errText = genFatalErrorReport(array("Camper access code missing or incorrect.<br><br><b>Hint: $hint</b>"));
            echo $errText;
            exit();
        }
    }
    
?>

<script type="text/javascript" src="jquery/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="meta/tooltip.js"></script>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Camper Home</a></h1>
<?php echo $loginMessage; ?>
<h3>Welcome, Campers and Families!</h3>
<p>This system will let you order your chug (activity) preferences for the summer.</p>
<p>To add a new camper to the system, please click the "Add" button.</p>
<p>To edit existing camper information, please enter the email address associated with that camper and then click "Edit".</p>

<form class="appnitro" id="choiceForm" method="POST" />
<br>
<button title="Add a new camper" class="control_button" type="submit" name="add" formaction="addCamper.php" >Add Camper</button>
<input type="hidden" id="fromHome" name="fromHome" value="1" />
</form>

<form class="appnitro" id="choiceForm" method="GET" />
<button title="Edit existing camper info" type="submit" name="edit" formaction="preEditCamper.php" >Edit Camper</button>
<span>
<input placeholder="Email associated with camper" id="email" name="email" class="element text" maxlength="255" size="50"
class="masterTooltip" title="Enter the email associated with the camper you would like to edit">
</span>

<input type="hidden" id="fromHome" name="fromHome" value="1" />
</form>

</div>

<?php
    echo footerText();
?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
