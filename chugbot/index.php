<?php
include_once 'dbConn.php';
include_once 'functions.php';
include_once 'formItem.php';
session_start();

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
    if ($row["camp_name"]) {
        $campName = $row["camp_name"];
    }
    if ($row["regular_user_token_hint"]) {
        $hint = $row["regular_user_token_hint"];
    }
    if ($row["admin_email"]) {
        $adminEmail = $row["admin_email"];
    }
}

if ($loggedIn) {
    $codeMessage = "Click \"Go\" to access the camper site";
} else {
    $codeMessage = "To get started, please enter the camper code and click \"Go!\".  If you forgot the code, hover your mouse over the input box for a hint.";
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

<div class="well well-white container">
<h1><a>Welcome</a></h1>
<form id="camperForm" class="form-group" method="GET">
<div class="page-header">
<h2>Welcome to the <?php echo $campName; ?> chug preference ranking system!</h3>
<p><?php echo $codeMessage; ?></p>
</div>
<ul>

<?php
if (!$loggedIn) {
    $ccField = new FormItemSingleTextField("Camper Access Code", true, "camper_code", 0);
    $ccField->setInputValue($admin_email);
    $ccField->setInputType("text");
    $ccField->setInputClass("element text medium");
    $ccField->setInputMaxLength(50);
    $ccField->setPlaceHolder("Access code");
    $ccField->setGuideText("Hint: $hint");
    echo $ccField->renderHtml();
}
?>

</ul>

<button class="btn btn-default" type="submit" form="camperForm" formaction="camperHome.php">Go</button>
</form>

<form id="staffForm" class="form-group" method="post">
<button class = "btn btn-default" type="submit" name="staffInit" form="staffForm" formaction="staffLogin.php" value="1">Admin</button>
</form>
</div>

<?php
echo footerText();
?>

</body>
</html>
