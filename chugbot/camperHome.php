<?php
include_once 'dbConn.php';
include_once 'functions.php';
include_once 'formItem.php';
session_start();

setup_camp_specific_terminology_constants();

echo headerText("Camper/Family Home");

// If the user is not logged in as a camper, validate the incoming access
// code.  If none is found, display an error message.
$loginMessage = "";
if (!camperLoggedIn()) {
    $db = new DbConn();
    $sql = "SELECT regular_user_token,regular_user_token_hint FROM admin_data";
    $err = "";
    $result = $db->runQueryDirectly($sql, $err);
    $code = $hint = "";
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row) {
            $code = $row["regular_user_token"];
            $hint = $row["regular_user_token_hint"];
        }
    }

    $accessCode = null;
    if (array_key_exists('camper_code', $_POST)) {
        $accessCode = test_post_input('camper_code');
    }
    if (!$accessCode) {
        $accessCode = test_get_input('camper_code');
    }

    $n = strlen($accessCode);
    if ($accessCode &&
        strncasecmp($accessCode, $code, $n) == 0) {
        $_SESSION['camper_logged_in'] = true;
        $loginMessage = "<h3><font color=\"green\">Login successful!</font></h3>";
    } else {
        $homeUrl = urlIfy("index.php?retry=1");
        $errText = genFatalErrorReport(array("Camper access code missing or incorrect.<br><br><b>Hint: $hint</b>"),
            false,
            $homeUrl);
        echo $errText;
        exit();
    }
}

?>

<div class="well well-white container" id="accordion">
<h1><a>Camper Home</a></h1>
<?php echo $loginMessage; ?>
<h3>Welcome, Campers and Families!</h3>
<p>If this is your first time picking <?php echo chug_term_plural ?> for this summer, click First Time for <?php echo yearOfCurrentSummer(); ?>. If you have used the system this year to enter earlier preferences, click Update Existing.</p>

<div class="panel panel-default">
<div class="panel-heading">
<h4 class="panel-title">
    <a data-toggle="collapse" data-parent="#accordion" href="#choiceForm1">First Time for <?php echo yearOfCurrentSummer(); ?></a>
</h4>
</div>
<div id="choiceForm1" class="panel-collapse collapse panel-body">
<form method="POST" />
    <button title="Add a camper" class="btn btn-primary" type="submit" name="add" formaction="addCamper.php" >Start</button>
    <input type="hidden" id="fromHome" name="fromHome" value="1" />
</form>
</div>

<div class="panel-heading">
<h4 class="panel-title">
    <a data-toggle="collapse" data-parent="#accordion" href="#choiceForm2">Update Existing</a>
</h4>
</div>
<div id="choiceForm2" class="panel-collapse collapse panel-body">
  <form method="GET" />
     <p>Please enter data below to retrieve your record. You may fill in any combination of boxes.</p>
  <ul>

<?php
$counter = 0;
// David O. requested that we remove email search for now.
/*
$camperEmailField = new FormItemSingleTextField("Email address associated with camper", FALSE, "email", $counter++);
$camperEmailField->setInputType("email");
$camperEmailField->setInputClass("element text medium");
$camperEmailField->setInputMaxLength(50);
$camperEmailField->setPlaceHolder("Email address");
$camperEmailField->setGuideText("Enter the email associated with the camper you would like to edit.");
echo $camperEmailField->renderHtml();
 */

$firstNameField = new FormItemSingleTextField("Camper First Name", false, "first", $counter++);
$firstNameField->setInputType("text");
$firstNameField->setInputClass("element text medium");
$firstNameField->setInputMaxLength(255);
$firstNameField->setPlaceHolder("First Name");
echo $firstNameField->renderHtml();

$lastNameField = new FormItemSingleTextField("Camper Last Name", false, "last", $counter++);
$lastNameField->setInputType("text");
$lastNameField->setInputClass("element text medium");
$lastNameField->setInputMaxLength(255);
$lastNameField->setPlaceHolder("Last Name");
echo $lastNameField->renderHtml();

$err = "";
$edahField = new FormItemDropDown("Edah", false, "edah_id", $counter++);
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
</div>

<?php
echo footerText();
?>

</body>
</html>
