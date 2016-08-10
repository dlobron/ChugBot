<?php
    include_once 'dbConn.php';
    include_once 'functions.php';
    include_once 'formItem.php';
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
            $errText = genFatalErrorReport(array("Camper access code missing or incorrect.<br><br><b>Hint: $hint</b>"),
                                           FALSE,
                                           $homeUrl);
            echo $errText;
            exit();
        }
    }
    
?>

<script type="text/javascript" src="jquery/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="meta/tooltip.js"></script>

<div class="form_container">
<h1><a>Camper Home</a></h1>
<?php echo $loginMessage; ?>
<h3>Welcome, Campers and Families!</h3>
<p>This system will let you order your chug (activity) preferences for the summer.</p>
<p>If this is your <b>first time</b> picking chugim for Kayitz <?php echo yearOfUpcomingSummer(); ?>, click Start.</p>

<form class="appnitro" id="choiceForm" method="POST" />
<button title="Add a camper" class="btn btn-primary" type="submit" name="add" formaction="addCamper.php" >Start</button>
<input type="hidden" id="fromHome" name="fromHome" value="1" />
</form>

<form class="appnitro" id="choiceForm" method="GET" />
<p>If <b>you have used this system this year</b>, enter some information to find your record, and then click "Edit Camper". You can search by any combination of boxes.</p>
<ul>

<?php
    $counter = 0;
    $camperEmailField = new FormItemSingleTextField("Email address associated with camper", FALSE, "email", $counter++);
    $camperEmailField->setInputType("email");
    $camperEmailField->setInputClass("element text medium");
    $camperEmailField->setInputMaxLength(50);
    $camperEmailField->setPlaceHolder("Email address");
    $camperEmailField->setGuideText("Enter the email associated with the camper you would like to edit.");
    echo $camperEmailField->renderHtml();
    
    $firstNameField = new FormItemSingleTextField("Camper First Name", FALSE, "first", $counter++);
    $firstNameField->setInputType("text");
    $firstNameField->setInputClass("element text medium");
    $firstNameField->setInputMaxLength(255);
    $firstNameField->setPlaceHolder("First Name");
    echo $firstNameField->renderHtml();
    
    $lastNameField = new FormItemSingleTextField("Camper Last Name", FALSE, "last", $counter++);
    $lastNameField->setInputType("text");
    $lastNameField->setInputClass("element text medium");
    $lastNameField->setInputMaxLength(255);
    $lastNameField->setPlaceHolder("Last Name");
    echo $lastNameField->renderHtml();
    
    $err = "";
    $edahField = new FormItemDropDown("Edah", FALSE, "edah_id", $counter++);
    $edahField->setGuideText("Choose this camper's current edah");
    $edahField->setInputClass("element select medium");
    $edahField->setInputSingular("edah");
    $edahField->fillDropDownId2Name($err,
                                    "edah_id", "edot");
    echo $edahField->renderHtml();
    ?>

<li class="buttons">
<button title="Edit existing camper info" class="btn btn-primary" type="submit" name="edit" formaction="preEditCamper.php" >Edit Camper</button>
</li>
</ul>
<input type="hidden" id="fromHome" name="fromHome" value="1" />
</form>

<?php
    echo footerText();
?>

</body>
</html>
