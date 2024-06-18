<?php
include_once 'dbConn.php';
include_once 'functions.php';
include_once 'formItem.php';
session_start();
checkLogout();
setup_camp_specific_terminology_constants();

$loggedIn = (adminLoggedIn() || camperLoggedIn());

$campName = "Camp Ramah";
$hint = "No hint available";
$adminEmail = "";
$db = new DbConn();
$err = "";
$sql = "SELECT camp_name, regular_user_token_hint, admin_email FROM admin_data";
$result = $db->runQueryDirectly($sql, $err);
if ($result) {
    $row = $result->fetch_assoc();
    if ($row) {
       if (array_key_exists("camp_name", $row)) {
         $campName = $row["camp_name"];
       }
       if (array_key_exists("regular_user_token_hint", $row)) {
       	  $hint = $row["regular_user_token_hint"];
       }
       if (array_key_exists("admin_email", $row)) {
          $adminEmail = $row["admin_email"];
       }
    }
}

if ($loggedIn) {
    $codeMessage = "Click \"Go\" to access the camper site";
} else {
    $codeMessage = "To get started, please enter the camper code and click \"Go\".  If you forgot the code, hover your mouse over the input box for a hint.";
}
$parts = explode("&", $_SERVER['QUERY_STRING']);
foreach ($parts as $part) {
    $cparts = explode("=", $part);
    if (count($cparts) != 2) {
        continue;
    }
    if ($cparts[0] == "retry" &&
        $cparts[1]) {
        $contactText = "email us";
        if ($adminEmail) {
            $contactText = "<a href=\"mailto:$adminEmail?Subject=Access%20code%20help%20needed\">email us</a>";
        }
        $codeMessage = "Please try the camper code again, and click \"Go!\".  If you still cannot log in, please $contactText for help.  Hint: $hint";
    }
}

echo headerText("Welcome");
?>

<div class="card card-body mt-3 container">
<h1><a>Welcome</a></h1>
<form id="camperForm" class="form-group" method="GET">
<div class="page-header">
<h2>Welcome to the <?php echo $campName; ?> <?php echo chug_term_singular ?> preference ranking system!</h3>
<p><?php echo $codeMessage; ?></p>
</div>
<ul>

<?php
if (!$loggedIn) {
    $ccField = new FormItemSingleTextField("Camper Access Code", true, "camper_code", 0);
    $ccField->setInputType("text");
    $ccField->setInputClass("element text medium");
    $ccField->setInputMaxLength(50);
    $ccField->setPlaceHolder("Access code");
    $ccField->setGuideText("Hint: $hint");
    echo $ccField->renderHtml();
}
?>

</ul>

<button class="btn btn-success mt-3" type="submit" form="camperForm" formaction="camperHome.php">Go</button>
</form>

<form id="staffForm" class="form-group" method="post">
<button class = "btn btn-light btn-outline-secondary mt-3" type="submit" name="staffInit" form="staffForm" formaction="staffLogin.php" value="1">Staff</button>
</form>
</div>

<?php
echo footerText();
?>

</body>
</html>
