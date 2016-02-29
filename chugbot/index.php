<?php
    include_once 'functions.php';
    include_once 'formItem.php';
    session_start();
    
    $campName = "Camp Ramah";
    $hint = "No hint available";
    $adminEmail = "";
    $mysqli = connect_db();
    $sql = "SELECT camp_name, regular_user_token_hint, admin_email FROM admin_data";
    $result = $mysqli->query($sql);
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
    mysqli_free_result($result);
    
    $codeMessage = "To get started, please enter the camper code and click \"Go!\".  If you forgot the code, hover your mouse over the input box for a hint.";
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
            $codeMessage = "Please try the camper code again, and click \"Go!\".  If you still cannot log in, please $contextText for help.  Hint: $hint";
        }
    }
    
    echo headerText("Welcome");
?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Welcome</a></h1>

<form id="camperForm" class="appnitro" method="post">
<div class="form_description">
<h2>Welcome to the <?php echo $campName; ?> chug preference ranking system!</h3>
<p><?php echo $codeMessage; ?></p>
</div>
<ul>

<?php
    $ccField = new FormItemSingleTextField("Camper Access Code", TRUE, "camper_code", 0);
    $ccField->setInputValue($admin_email);
    $ccField->setInputType("text");
    $ccField->setInputClass("element text medium");
    $ccField->setInputMaxLength(20);
    $ccField->setPlaceHolder("Access code");
    $ccField->setGuideText("Hint: $hint");
    echo $ccField->renderHtml();
?>

</ul>

<button class="control_button" type="submit" name="camperInit" form="camperForm" formaction="camperHome.php">Go</button>
</form>

<form id="staffForm" class="appnitro" method="post">
<button type="submit" name="staffInit" form="staffForm" formaction="staffLogin.php" value="1">Admin</button>
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
