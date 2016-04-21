<?php
    session_start();
    include_once 'functions.php';
    include_once 'formItem.php';
    include_once 'dbConn.php';
    bounceToLogin();
    
    $existingAdminEmail = $admin_email = $existingRegularUserToken = $existingRegularUserTokenHint = $existingCampName = $existingPrefInstructions = $existingCampWeb = $existingAdminEmailCc = $existingAdminEmailFromName = "";
    $dbError = $staffPasswordErr = $staffPasswordErr2 = $adminEmailCcErr = $campNameErr = "";
    $db = new DbConn();
    $err = "";
    $sql = "SELECT * from admin_data";
    $result = $db->runQueryDirectly($sql, $dbError);
    if ($result == FALSE) {
        error_log("admin_data query failed: $dbError");
    } else if ($result->num_rows != 1) {
        $dbError = dbErrorString($sql, "Bad row count for admin data");
    } else {
        $row = mysqli_fetch_assoc($result);
        $existingAdminEmail = $row["admin_email"];
        $existingAdminEmailCc = $row["admin_email_cc"];
        $existingAdminEmailFromName = $row["admin_email_from_name"];
        $existingRegularUserToken = $row["regular_user_token"];
        $existingRegularUserTokenHint = $row["regular_user_token_hint"];
        $existingCampName = $row["camp_name"];
        $existingPrefInstructions = $row["pref_page_instructions"];
        $existingCampWeb = $row["camp_web"];
        
        // Set the admin email and password to current values.  These will be
        // clobbered if we have incoming POST data - otherwise, we'll display them
        // on the initial page.
        $admin_email = $existingAdminEmail;
        $admin_email_cc = $existingAdminEmailCc;
        $admin_email_from_name = $existingAdminEmailFromName;
        $regular_user_token = $existingRegularUserToken;
        $regular_user_token_hint = $existingRegularUserTokenHint;
        $camp_name = $existingCampName;
        $pref_page_instructions = $existingPrefInstructions;
        $camp_web = $existingCampWeb;
    }
    
    $staffEmailErr = $staffPasswordErr = $staffPasswordErr2 = $existingEmailErr = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $admin_email = test_input($_POST["admin_email"]);
        $admin_email_from_name = test_input($_POST["admin_email_from_name"]);
        $staff_password = test_input($_POST["staff_password"]);
        $staff_password2 = test_input($_POST["staff_password2"]);
        $admin_email_cc = test_input($_POST["admin_email_cc"]);
        $regular_user_token = test_input($_POST["regular_user_token"]);
        $regular_user_token_hint = test_input($_POST["regular_user_token_hint"]);
        $camp_name = test_input($_POST["camp_name"]);
        $pref_page_instructions = test_input($_POST["pref_page_instructions"]);
        $camp_web = test_input($_POST["camp_web"]);
        
        // Add NULL-able column values to the DB object.
        $db = new DbConn();
        $db->addColumn("admin_email_cc", $admin_email_cc, 's');
        $db->addColumn("admin_email_from_name", $admin_email_from_name, 's');
        $db->addColumn("camp_name", $camp_name, 's');
        $db->addColumn("camp_web", $camp_web, 's');
        $db->addColumn("pref_page_instructions", $pref_page_instructions, 's');
        $db->addColumn("regular_user_token", $regular_user_token, 's');
        $db->addColumn("regular_user_token_hint", $regular_user_token_hint, 's');
        
        // Assume the email is never empty.  Only update it if a valid address was
        // given.
        if ($admin_email) {
            if (! filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $staffEmailErr = errorString("\"$admin_email\" is not a valid email address.");
            }
            $db->addColumn("admin_email", $admin_email, 's');
        } else {
            $staffEmailErr = errorString("Admin email is required");
        }
        // Camp name is required.
        if (! $camp_name) {
            $campNameErr = errorString("Camp name is required");
        }
        // If an admin CC was given, check each address for validity.  Multiple
        // address should be comma-separated, but allow space, colon, and semicolon.
        if ($admin_email_cc) {
            $ccs = preg_split("/[,:; ]/", $admin_email_cc);
            foreach ($ccs as $cc) {
                if (! filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                    $adminEmailCcErr = errorString("\"$cc\" is not a valid email address.");
                    break;
                }
            }
        }
        // Only reset the password if it's explicitly supplied.
        if ($staff_password) {
            if (strlen($staff_password) < 5 ||
                strlen($staff_password) > 255) {
                $staffPasswordErr = errorString("Password must be between 5 and 255 characters");
            }
            if ($staff_password2 != $staff_password) {
                // The repeated password must match the first.
                $staffPasswordErr2 = errorString("Passwords do not match");
            }
            $staffPasswordHashed = password_hash($staff_password, PASSWORD_DEFAULT);
            $db->addColumn("admin_password", $staffPasswordHashed, 's');
        }
                
        if (empty($staffEmailErr) &&
            empty($staffPasswordErr) &&
            empty($staffPasswordErr2) &&
            empty($adminEmailCcErr) &&
            empty($dbError) &&
            empty($campNameErr)) {
            // No errors: insert the new/updated data, and then redirect
            // to the admin home page.
            $updateOk = $db->updateTable("admin_data", $dbError);
            if ($updateOk) {
                // New data entered OK: go to the home page.  If a password was
                // validated, log the user in.
                if ($staff_password) {
                    $_SESSION['admin_logged_in'] = TRUE;
                }
                $redirUrl = urlBaseText() . "staffHome.php?update=as"; // Redir for successful email/pw change.
                header("Location: $redirUrl");
                exit();
            }
        }
    }
    
    ?>

<?php
    echo headerText("Edit Admin Data");
    
    $errText = genFatalErrorReport(array($dbError, $staffPasswordErr, $staffPasswordErr2, $staffEmailErr, $adminEmailCcErr, $campNameErr));
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
    $counter = 0;
    $adminEmailField = new FormItemSingleTextField("Admin Email Address", TRUE, "admin_email", $counter++);
    $adminEmailField->setInputValue($admin_email);
    $adminEmailField->setInputType("email");
    $adminEmailField->setInputClass("element text medium");
    $adminEmailField->setInputMaxLength(20);
    $adminEmailField->setPlaceHolder("leveling@campramahne.org");
    $adminEmailField->setGuideText("Enter the address of a person who can answer leveling questions.");
    $adminEmailField->setError($staffEmailErr);
    echo $adminEmailField->renderHtml();
    
    $adminEmailCcField = new FormItemSingleTextField("Admin Email CC Addresses", FALSE, "admin_email_cc", $counter++);
    $adminEmailCcField->setInputValue($admin_email_cc);
    $adminEmailCcField->setInputType("email");
    $adminEmailCcField->setInputClass("element text medium");
    $adminEmailCcField->setInputMaxLength(255);
    $adminEmailCcField->setPlaceHolder(" ");
    $adminEmailCcField->setGuideText("Enter one or more emails to be CC'ed on camper correspondence.  Separate multiple addresses with commas.");
    $adminEmailCcField->setError($adminEmailCcErr);
    echo $adminEmailCcField->renderHtml();
    
    $adminEmailFromNameField = new FormItemSingleTextField("Admin Email \"From\" Name", FALSE, "admin_email_from_name", $counter++);
    $adminEmailFromNameField->setInputValue($admin_email_from_name);
    $adminEmailFromNameField->setInputType("text");
    $adminEmailFromNameField->setInputClass("element text medium");
    $adminEmailFromNameField->setInputMaxLength(255);
    $adminEmailFromNameField->setPlaceHolder("Chug Organizer's Name");
    $adminEmailFromNameField->setGuideText("If set, this name will appear as the \"From\" name when email is sent.  If not set, the camp name will be used.");
    echo $adminEmailFromNameField->renderHtml();
    
    $regularUserTokenField = new FormItemSingleTextField("Camper Access Token", FALSE, "regular_user_token", $counter++);
    $regularUserTokenField->setInputValue($regular_user_token);
    $regularUserTokenField->setInputType("text");
    $regularUserTokenField->setInputClass("element text medium");
    $regularUserTokenField->setInputMaxLength(50);
    $regularUserTokenField->setPlaceHolder("e.g., RamahKayitz");
    $regularUserTokenField->setGuideText("The camper access token is used by non-admin users to confirm their login.  It can be any easy-to-remember string.  This value is not a password, just a token, so it should be something simple, e.g., \"RamahKayitz\".");
    echo $regularUserTokenField->renderHtml();
    
    $hintField = new FormItemTextArea("Camper Access Token Hint Phrase", FALSE, "regular_user_token_hint", $counter++);
    $hintField->setInputValue($regular_user_token_hint);
    $hintField->setInputType("text");
    $hintField->setInputClass("element textarea medium");
    $hintField->setInputMaxLength(512);
    $hintField->setPlaceHolder(" ");
    $hintField->setGuideText("Optional hint for campers who forget the access token.  Can be anything.");
    echo $hintField->renderHtml();
    
    $prefInstructions = new FormItemTextArea("Camper Instructions for Ranking", FALSE, "pref_page_instructions", $counter++);
    $prefInstructions->setInputValue($pref_page_instructions);
    $prefInstructions->setInputType("text");
    $prefInstructions->setInputClass("element textarea medium");
    $prefInstructions->setInputMaxLength(2048);
    $prefInstructions->setPlaceHolder(" ");
    $prefInstructions->setGuideText("These are the instructions campers will see on the ranking page.  HTML tags are OK.");
    echo $prefInstructions->renderHtml();
    
    $campNameField = new FormItemSingleTextField("Camp Name", TRUE, "camp_name", $counter++);
    $campNameField->setInputValue($camp_name);
    $campNameField->setInputType("text");
    $campNameField->setInputClass("element text medium");
    $campNameField->setInputMaxLength(50);
    $campNameField->setGuideText("Enter the standard name for this camp, e.g., \"Camp Ramah New England\"");
    $campNameField->setPlaceHolder("Camp Ramah New England");
    echo $campNameField->renderHtml();
    
    $campWebField = new FormItemSingleTextField("Camp Website", FALSE, "camp_web", $counter++);
    $campWebField->setInputValue($camp_web);
    $campWebField->setInputType("text");
    $campWebField->setInputClass("element text medium");
    $campWebField->setInputMaxLength(50);
    $campWebField->setGuideText("Enter your camp website, if you have one, e.g., \"www.campramahne.org\"");
    $campWebField->setPlaceHolder(" ");
    echo $campWebField->renderHtml();
    
    $staffPasswordField = new FormItemSingleTextField("New Staff Password (leave this field blank to keep staff password the same.)",
                                                      FALSE, "staff_password", $counter++);
    $staffPasswordField->setInputType("password");
    $staffPasswordField->setInputClass("element text medium");
    $staffPasswordField->setInputMaxLength(50);
    $staffPasswordField->setPlaceHolder(" ");
    $staffPasswordField->setGuideText("Leave this field and the next one blank if you do not wish to change the admin password.");
    echo $staffPasswordField->renderHtml();
    
    $staffPasswordField2 = new FormItemSingleTextField("Retype New Staff Password", FALSE, "staff_password2", $counter++);
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
</div>

<?php
    echo footerText();
?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
