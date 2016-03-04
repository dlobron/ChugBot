<?php
    session_start();
    include_once 'functions.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    // Grab the existing admin email from the database.  This is presumed to exist, since
    // this is a reset page.
    $existingAdminEmail = $existingAdminEmailUserName = $existingAdminEmailPassword = $admin_email = $existingRegularUserToken = $existingRegularUserTokenHint = $existingCampName = $existingPrefInstructions = "";
    $dbErr = $staffPasswordErr = $staffPasswordErr2 = "";
    $mysqli = connect_db();
    $sql = "SELECT * from admin_data";
    $result = $mysqli->query($sql);
    if ($result == FALSE) {
        $dbError = dbErrorString($sql, $mysqli->error);
    } else if ($result->num_rows != 1) {
        $dbError = dbErrorString($sql, "Bad row count for admin data");
    } else {
        $row = mysqli_fetch_assoc($result);
        $existingAdminEmail = $row["admin_email"];
        $existingAdminEmailUserName = $row["admin_email_username"];
        $existingAdminEmailPassword = $row["admin_email_password"];
        $existingRegularUserToken = $row["regular_user_token"];
        $existingRegularUserTokenHint = $row["regular_user_token_hint"];
        $existingCampName = $row["camp_name"];
        $existingPrefInstructions = $row["pref_page_instructions"];
        
        // Set the admin email and password to current values.  These will be
        // clobbered if we have incoming POST data - otherwise, we'll display them
        // on the initial page.
        $admin_email = $existingAdminEmail;
        $admin_email_username = $existingAdminEmailUserName;
        $admin_email_password = $existingAdminEmailPassword;
        $regular_user_token = $existingRegularUserToken;
        $regular_user_token_hint = $existingRegularUserTokenHint;
        $camp_name = $existingCampName;
        $pref_page_instructions = $existingPrefInstructions;
    }
    mysqli_free_result($result);
    $mysqli->close();
    
    $staffEmailErr = $staffPasswordErr = $staffPasswordErr2 = $existingEmailErr = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $admin_email = test_input($_POST["admin_email"]);
        $staff_password = test_input($_POST["staff_password"]);
        $staff_password2 = test_input($_POST["staff_password2"]);
        $admin_email_username = test_input($_POST["admin_email_username"]);
        $admin_email_password = test_input($_POST["admin_email_password"]);
        $regular_user_token = test_input($_POST["regular_user_token"]);
        $regular_user_token_hint = test_input($_POST["regular_user_token_hint"]);
        $camp_name = test_input($_POST["camp_name"]);
        $pref_page_instructions = test_input($_POST["pref_page_instructions"]);
        
        // For admin email and user token fields, update to incoming values.  If a value is not given,
        // set the table field to NULL.
        $sql = "UPDATE admin_data SET ";
        $fields = array("admin_email_username", $admin_email_username, $existingAdminEmailUserName,
                        "admin_email_password", $admin_email_password, $existingAdminEmailPassword,
                        "regular_user_token", $regular_user_token, $existingRegularUserToken,
                        "regular_user_token_hint", $regular_user_token_hint, $existingRegularUserTokenHint,
                        "pref_page_instructions", $pref_page_instructions, $existingPrefInstructions);
        for ($i = 0; $i < count($fields); $i += 3) {
            $colName = $fields[$i];
            $newVal = $fields[$i+1];
            $existingVal = $fields[$i+2];
            $sql .= "$colName = ";
            if (is_null($newVal) || empty($newVal)) {
                $sql .= "NULL";
            } else {
                $sql .= "\"$newVal\"";
            }
            if ($i < (count($fields)-3)) {
                $sql .= ", ";
            }
        }
        // Only reset the password if it's explicitly supplied.
        if ($staff_password) {
            if (strlen($staff_password) < 5 ||
                strlen($staff_password) > 20) {
                $staffPasswordErr = errorString("Password must be between 5 and 20 characters");
            }
            if ($staff_password2 != $staff_password) {
                // The repeated password must match the first.
                $staffPasswordErr2 = errorString("Passwords do not match");
            }
            $staffPasswordHashed = password_hash($staff_password, PASSWORD_DEFAULT);
            $sql .= ", admin_password = \"$staffPasswordHashed\"";
        }
        // Assume the email is never empty.  Only update it if a valid address was
        // given.
        if ($admin_email) {
            if (! filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $staffEmailErr = errorString("\"$admin_email\" is not a valid email address.");
            }
            $sql .= ", admin_email = \"$admin_email\"";
        }
        // Same for camp name: only update it if a new string was given.
        if ($camp_name) {
            $sql .= ", camp_name = \"$camp_name\"";
        }
                
        if (empty($staffEmailErr) &&
            empty($staffPasswordErr) &&
            empty($staffPasswordErr2) &&
            empty($dbError)) {
            // No errors: insert the new/updated data, and then redirect
            // to the admin home page.
            $mysqli = connect_db();
            $result = $mysqli->query($sql);
            if ($result == FALSE) {
                $dbError = dbErrorString($sql, $mysqli->error);
            } else {
                // New data entered OK: go to the home page.  If a password was
                // validated, log the user in.
                if ($staff_password) {
                    $_SESSION['admin_logged_in'] = TRUE;
                }
                $redirUrl = urlBaseText() . "staffHome.php"; // Redir for successful email/pw change.
                header("Location: $redirUrl");
                exit();
            }
        }
    }
    
    ?>

<?php
    echo headerText("Edit Admin Data");
    
    $errText = genFatalErrorReport(array($dbErr, $staffPasswordErr, $staffPasswordErr2, $staffEmailErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Edit Admin Data</a></h1>

<form id="loginForm" class="appnitro"  method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Edit Admin Data</h2>
<p>Please update the staff admin data as needed. For more information about a field, hover over that field.<br>
After updating successfully, you will be directed to the camp staff home page.<br>
Required values are marked with a <font color="red">*</font>.
</p>
</div>
<ul>

<?php
    $adminEmailField = new FormItemSingleTextField("Admin Email Address", TRUE, "admin_email", 0);
    $adminEmailField->setInputValue($admin_email);
    $adminEmailField->setInputType("email");
    $adminEmailField->setInputClass("element text medium");
    $adminEmailField->setInputMaxLength(20);
    $adminEmailField->setPlaceHolder("leveling@campramahne.org");
    $adminEmailField->setGuideText("Enter the address of a person who can answer leveling questions.");
    $adminEmailField->setError($staffEmailErr);
    echo $adminEmailField->renderHtml();
    
    $adminEmailUserNameField = new FormItemSingleTextField("Admin Email User Name", FALSE, "admin_email_username", 1);
    $adminEmailUserNameField->setInputValue($admin_email_username);
    $adminEmailUserNameField->setInputType("text");
    $adminEmailUserNameField->setInputClass("element text medium");
    $adminEmailUserNameField->setInputMaxLength(50);
    $adminEmailUserNameField->setPlaceHolder("leveling@campramahne.org");
    $adminEmailUserNameField->setGuideText("Enter the username for the staff email account (this is often the same as the admin email address).");
    echo $adminEmailUserNameField->renderHtml();
    
    $adminEmailPasswordField = new FormItemSingleTextField("Admin Email Password", FALSE, "admin_email_password", 2);
    $adminEmailPasswordField->setInputValue($admin_email_password);
    $adminEmailPasswordField->setInputType("text");
    $adminEmailPasswordField->setInputClass("element text medium");
    $adminEmailPasswordField->setInputMaxLength(20);
    $adminEmailPasswordField->setPlaceHolder("Email account password");
    $adminEmailPasswordField->setGuideText("Enter the password of the staff email account (this is <b>not</b> the same as the admin password for this site).  Please do not use a valuable password, since this is not stored securely and is only used for sending email.");
    echo $adminEmailPasswordField->renderHtml();
    
    $regularUserTokenField = new FormItemSingleTextField("Camper Access Token", FALSE, "regular_user_token", 3);
    $regularUserTokenField->setInputValue($regular_user_token);
    $regularUserTokenField->setInputType("text");
    $regularUserTokenField->setInputClass("element text medium");
    $regularUserTokenField->setInputMaxLength(50);
    $regularUserTokenField->setPlaceHolder("e.g., RamahKayitz");
    $regularUserTokenField->setGuideText("The camper access token is used by non-admin users to confirm their login.  It can be any easy-to-remember string.  This value is not a password, just a token, so it should be something simple, e.g., \"RamahKayitz\".");
    echo $regularUserTokenField->renderHtml();
    
    $hintField = new FormItemTextArea("Camper Access Token Hint Phrase", FALSE, "regular_user_token_hint", 4);
    $hintField->setInputValue($regular_user_token_hint);
    $hintField->setInputType("text");
    $hintField->setInputClass("element textarea medium");
    $hintField->setInputMaxLength(512);
    $hintField->setPlaceHolder(" ");
    $hintField->setGuideText("Optional hint for campers who forget the access token.  Can be anything.");
    echo $hintField->renderHtml();
    
    $prefInstructions = new FormItemTextArea("Camper Instructions for Ranking", FALSE, "pref_page_instructions", 5);
    $prefInstructions->setInputValue($pref_page_instructions);
    $prefInstructions->setInputType("text");
    $prefInstructions->setInputClass("element textarea medium");
    $prefInstructions->setInputMaxLength(2048);
    $prefInstructions->setPlaceHolder(" ");
    $prefInstructions->setGuideText("These are the instructions campers will see on the ranking page.  HTML tags are OK.");
    echo $prefInstructions->renderHtml();
    
    $campNameField = new FormItemSingleTextField("Camp Name", TRUE, "camp_name", 6);
    $campNameField->setInputValue($camp_name);
    $campNameField->setInputType("text");
    $campNameField->setInputClass("element text medium");
    $campNameField->setInputMaxLength(50);
    $campNameField->setGuideText("Enter the standard name for this camp, e.g., \"Camp Ramah New England\"");
    $campNameField->setPlaceHolder("Camp Ramah New England");
    echo $campNameField->renderHtml();
    
    $staffPasswordField = new FormItemSingleTextField("Staff Password (<b>leave this field blank to keep it the same</b>.)", FALSE, "staff_password", 7);
    $staffPasswordField->setInputType("password");
    $staffPasswordField->setInputClass("element text medium");
    $staffPasswordField->setInputMaxLength(50);
    $staffPasswordField->setPlaceHolder(" ");
    $staffPasswordField->setGuideText("Leave this field and the next one blank if you do not wish to change the admin password.");
    echo $staffPasswordField->renderHtml();
    
    $staffPasswordField2 = new FormItemSingleTextField("Retype Staff Password", FALSE, "staff_password2", 8);
    $staffPasswordField2->setInputType("password");
    $staffPasswordField2->setInputClass("element text medium");
    $staffPasswordField2->setInputMaxLength(50);
    $staffPasswordField2->setPlaceHolder(" ");
    echo $staffPasswordField2->renderHtml();
    ?>

<li class="buttons">
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
<?php
    $cancelUrl = urlIfy("staffHome.php");
    echo "<a href=\"$cancelUrl\">Cancel</a>";
    ?>
</li>
</ul>
</form>

<div id="footer">
<?php
    echo footerText();
?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
