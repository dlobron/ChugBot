<?php
session_start();
include_once 'functions.php';
include_once 'formItem.php';
include_once 'dbConn.php';
bounceToLogin();
checkLogout();
setup_camp_specific_terminology_constants();

$existingAdminEmail = $admin_email = $existingRegularUserToken = $existingRegularUserTokenHint = $existingCampName = $existingPrefInstructions = $existingCampWeb = $existingAdminEmailCc = $existingAdminEmailFromName = $existingPrefCount = $existingSendConfirmEmail = $existingChugTermSingular = $existingChugTermPlural = $existingBlockTermSingular = $existingBlockTermPlural = "";
$deletableTableId2Name = array();
$deletableTableActiveIdHash = array();
$dbError = $adminPasswordErr = $adminPasswordErr2 = $roshPasswordErr = $roshPasswordErr2 = $chug_leaderPasswordErr = $chug_leaderPasswordErr2 = $adminEmailCcErr = $campNameErr = $prefCountError = $campTerminologyErr = "";

$db = new DbConn();
$err = "";
$sql = "SELECT * from admin_data";
$result = $db->runQueryDirectly($sql, $dbError);
if ($result == false) {
    error_log("admin_data query failed: $dbError");
} else if ($result->num_rows != 1) {
    $dbError = dbErrorString($sql, "Bad row count for admin data");
} else {
    $row = mysqli_fetch_assoc($result);
    $existingAdminEmail = $row["admin_email"];
    $existingAdminEmailCc = $row["admin_email_cc"];
    $existingAdminEmailFromName = $row["admin_email_from_name"];
    $existingEnableChugimImporter = $row["enable_chugim_importer"];
    $existingEnableCamperImporter = $row["enable_camper_importer"];
    $existingEnableCamperCreation = $row["enable_camper_creation"];
    $existingEnableSelectionProcess = $row["enable_selection_process"];
    $existingSendConfirmEmail = $row["send_confirm_email"];
    $existingRegularUserToken = $row["regular_user_token"];
    $existingRegularUserTokenHint = $row["regular_user_token_hint"];
    $existingCampName = $row["camp_name"];
    $existingPrefInstructions = $row["pref_page_instructions"];
    $existingCampWeb = $row["camp_web"];
    $existingPrefCount = $row["pref_count"];
    $existingChugTermSingular = $row["chug_term_singular"];
    $existingChugTermPlural = $row["chug_term_plural"];
    $existingBlockTermSingular = $row["block_term_singular"];
    $existingBlockTermPlural = $row["block_term_plural"];

    // Set the admin email and password to current values.  These will be
    // clobbered if we have incoming POST data - otherwise, we'll display them
    // on the initial page.
    $admin_email = $existingAdminEmail;
    $admin_email_cc = $existingAdminEmailCc;
    $admin_email_from_name = $existingAdminEmailFromName;
    $enable_chugim_importer = $existingEnableChugimImporter;
    $enable_camper_importer = $existingEnableCamperImporter;
    $enable_camper_creation = $existingEnableCamperCreation;
    $enable_selection_process = $existingEnableSelectionProcess;
    $send_confirm_email = $existingSendConfirmEmail;
    $regular_user_token = $existingRegularUserToken;
    $regular_user_token_hint = $existingRegularUserTokenHint;
    $camp_name = $existingCampName;
    $pref_page_instructions = $existingPrefInstructions;
    $camp_web = $existingCampWeb;
    $pref_count = $existingPrefCount;
    $chug_term_singular = $existingChugTermSingular;
    $chug_term_plural = $existingChugTermPlural;
    $block_term_singular = $existingBlockTermSingular;
    $block_term_plural = $existingBlockTermPlural;
}
// Grab existing category_tables data, if we don't have an update below.
$db = new DbConn();
$result = $db->runQueryDirectly("SELECT * FROM category_tables", $dbError);
if ($result == false) {
    error_log("category_tables select failed: $dbError");
} else {
    while ($row = $result->fetch_assoc()) {
        $table = $row["name"];
        $tableId = $row["category_table_id"];
        $active = $row["delete_ok"];
        if ($active) {
            $deletableTableActiveIdHash[$tableId] = 1;
        }
        $deletableTableId2Name[$tableId] = $table;
    }
}

$staffEmailErr = $adminPasswordErr = $adminPasswordErr2 = $roshPasswordErr = $roshPasswordErr2 = $chug_leaderPasswordErr = $chug_leaderPasswordErr2 = $existingEmailErr = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_email = test_post_input("admin_email");
    $admin_email_from_name = test_post_input("admin_email_from_name");
    $send_confirm_email = boolval(test_post_input("send_confirm_email"));
    $enable_chugim_importer = boolval(test_post_input("enable_chugim_importer"));
    $enable_camper_importer = boolval(test_post_input("enable_camper_importer"));
    $enable_camper_creation = boolval(test_post_input("enable_camper_creation"));
    $enable_selection_process = boolval(test_post_input("enable_selection_process"));
    $admin_password = test_post_input("admin_password");
    $admin_password2 = test_post_input("admin_password2");
    $rosh_password = test_post_input("rosh_password");
    $rosh_password2 = test_post_input("rosh_password2");
    $chug_leader_password = test_post_input("chug_leader_password");
    $chug_leader_password2 = test_post_input("chug_leader_password2");
    $admin_email_cc = test_post_input("admin_email_cc");
    $regular_user_token = test_post_input("regular_user_token");
    $regular_user_token_hint = test_post_input("regular_user_token_hint");
    $camp_name = test_post_input("camp_name");
    $pref_page_instructions = test_post_input("pref_page_instructions");
    $camp_web = test_post_input("camp_web");
    $pref_count = test_post_input("pref_count");
    $chug_term_singular = test_post_input("chug_term_singular");
    $chug_term_plural = test_post_input("chug_term_plural");
    $block_term_singular = test_post_input("block_term_singular");
    $block_term_plural = test_post_input("block_term_plural");
    $edah_term_singular = test_post_input("edah_term_singular");
    $edah_term_plural = test_post_input("edah_term_plural");

    // Update the deletable tables.  We start by setting all tables to not
    // be editable, and then we enable ones that are active.
    $deletableTableActiveIdHash = array(); // Reset, then populate from POST data.
    populateActiveIds($deletableTableActiveIdHash, "deletable_tables");
    $db = new DbConn();
    $result = $db->runQueryDirectly("UPDATE category_tables SET delete_ok = 0",
        $dbError);
    if ($result == false) {
        error_log("category_tables reset failed: $dbError");
    } else {
        foreach ($deletableTableActiveIdHash as $tableId => $active) {
            $db = new DbConn();
            $db->addColumn("delete_ok", 1, 'i');
            $db->addWhereColumn("category_table_id", $tableId, 'i');
            $result = $db->updateTable("category_tables", $dbError);
            if ($result == false) {
                error_log("category_tables update failed: $dbError");
                break;
            }
        }
    }

    // Add NULL-able column values to the DB object.
    $db = new DbConn();
    $db->addColumn("admin_email_cc", $admin_email_cc, 's');
    $db->addColumn("admin_email_from_name", $admin_email_from_name, 's');
    $db->addColumn("camp_name", $camp_name, 's');
    $db->addColumn("camp_web", $camp_web, 's');
    $db->addColumn("pref_page_instructions", $pref_page_instructions, 's');
    $db->addColumn("regular_user_token", $regular_user_token, 's');
    $db->addColumn("regular_user_token_hint", $regular_user_token_hint, 's');
    $db->addColumn("send_confirm_email", $send_confirm_email, 'i');
    $db->addColumn("enable_chugim_importer", $enable_chugim_importer, 'i');
    $db->addColumn("enable_camper_importer", $enable_camper_importer, 'i');
    $db->addColumn("enable_camper_creation", $enable_camper_creation, 'i');
    $db->addColumn("enable_selection_process", $enable_selection_process, 'i');
    $db->addColumn("chug_term_singular", strtolower($chug_term_singular), 's');
    $db->addColumn("chug_term_plural", strtolower($chug_term_plural), 's');
    $db->addColumn("block_term_singular", strtolower($block_term_singular), 's');
    $db->addColumn("block_term_plural", strtolower($block_term_plural), 's');
    $db->addColumn("edah_term_singular", strtolower($edah_term_singular), 's');
    $db->addColumn("edah_term_plural", strtolower($edah_term_plural), 's');

    // Assume the email is never empty.  Only update it if a valid address was
    // given.
    if ($admin_email) {
        if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $staffEmailErr = errorString("\"$admin_email\" is not a valid email address.");
        }
        $db->addColumn("admin_email", $admin_email, 's');
    } else {
        $staffEmailErr = errorString("Admin email is required");
    }
    // Camp name is required.
    if (!$camp_name) {
        $campNameErr = errorString("Camp name is required");
    }
    // If an admin CC was given, check each address for validity.  Multiple
    // address should be comma-separated, but allow space, colon, and semicolon.
    if ($admin_email_cc) {
        $ccs = preg_split("/[,:; ]/", $admin_email_cc);
        foreach ($ccs as $cc) {
            if (!filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                $adminEmailCcErr = errorString("\"$cc\" is not a valid email address.");
                break;
            }
        }
    }
    // Only reset the admin password if it's explicitly supplied.
    if ($admin_password) {
        if (strlen($admin_password) < 5 ||
            strlen($admin_password) > 255) {
            $adminPasswordErr = errorString("Admin password must be between 5 and 255 characters");
        }
        if ($admin_password2 != $admin_password) {
            // The repeated password must match the first.
            $adminPasswordErr2 = errorString("Admin passwords do not match");
        }
        $adminPasswordHashed = password_hash($admin_password, PASSWORD_DEFAULT);
        $db->addColumn("admin_password", $adminPasswordHashed, 's');
    }
    // Only reset the rosh password if it's explicitly supplied.
    if ($rosh_password) {
        if (strlen($rosh_password) < 5 ||
            strlen($rosh_password) > 255) {
            $roshPasswordErr = errorString("Rosh/Yoetzet password must be between 5 and 255 characters");
        }
        if ($rosh_password2 != $rosh_password) {
            // The repeated password must match the first.
            $roshPasswordErr2 = errorString("Rosh/Yoetzet passwords do not match");
        }
        $roshPasswordHashed = password_hash($rosh_password, PASSWORD_DEFAULT);
        $db->addColumn("rosh_yoetzet_password", $roshPasswordHashed, 's');
    }
    // Only reset the chug leader password if it's explicitly supplied.
    if ($chug_leader_password) {
        if (strlen($chug_leader_password) < 5 ||
            strlen($chug_leader_password) > 255) {
            $chug_leaderPasswordErr = errorString("Chug Leader password must be between 5 and 255 characters");
        }
        if ($chug_leader_password2 != $chug_leader_password) {
            // The repeated password must match the first.
            $chug_leaderPasswordErr2 = errorString("Chug Leader passwords do not match");
        }
        $chug_leaderPasswordHashed = password_hash($chug_leader_password, PASSWORD_DEFAULT);
        $db->addColumn("chug_leader_password", $chug_leaderPasswordHashed, 's');
    }
    // Same, for pref count.
    if ($pref_count) {
        $prefCount = intval($pref_count);
        if ($prefCount < 1 ||
            $prefCount > 6) {
            $prefCountError = errorString("Camper preference count must be between 1 and 6, inclusive");
        } else {
            $db->addColumn("pref_count", $prefCount, 'i');
        }
    }
    // Camp terminologies cannot be empty
    if (!$chug_term_singular || !$chug_term_plural || !$block_term_singular || !$block_term_plural || !$edah_term_singular || !$edah_term_plural) {
        $campTerminologyErr = errorString("Camp terminologies are required");
    }
    if (empty($staffEmailErr) &&
        empty($adminPasswordErr) &&
        empty($adminPasswordErr2) &&
        empty($roshPasswordErr) &&
        empty($roshPasswordErr2) &&
        empty($chug_leaderPasswordErr) &&
        empty($chug_leaderPasswordErr2) &&
        empty($adminEmailCcErr) &&
        empty($dbError) &&
        empty($campNameErr) &&
        empty($prefCountError) &&
        empty($campTerminologyErr)) {
        // No errors: insert the new/updated data, and then redirect
        // to the admin home page.
        $updateOk = $db->updateTable("admin_data", $dbError);
        if ($updateOk) {
            // New data entered OK: go to the home page.  If a password was
            // validated, log the user in.
            if ($admin_password) {
                $_SESSION['admin_logged_in'] = true;
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

$errText = genFatalErrorReport(array($dbError, $adminPasswordErr, $adminPasswordErr2, $roshPasswordErr, $roshPasswordErr2, $chug_leaderPasswordErr, $chug_leaderPasswordErr2, $staffEmailErr, $adminEmailCcErr, $campNameErr, $prefCountError, $campTerminologyErr));
if (!is_null($errText)) {
    echo $errText;
    exit();
}
?>


<div class="card card-body mt-3 mb-3 container">
<h1><a>Edit Admin Data</a></h1>
<form id="loginForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

<div class="page-header">
<h2>Edit Admin Data</h2>
<p>Please update the staff admin data as needed. For more information about a field, hover over that field.<br>
After updating successfully, you will be directed to the camp staff home page.<br>
Required values are marked with a <font color="red">*</font>.
</p>
</div>
<ul>

<?php
$counter = 0;
$adminEmailField = new FormItemSingleTextField("Admin Email Address", true, "admin_email", $counter++);
$adminEmailField->setInputValue($admin_email);
$adminEmailField->setInputType("email");
$adminEmailField->setInputClass("element text medium");
$adminEmailField->setInputMaxLength(50);
$adminEmailField->setPlaceHolder("leveling@campramahne.org");
$adminEmailField->setGuideText("Enter the address of a person who can answer leveling questions.");
$adminEmailField->setError($staffEmailErr);
echo $adminEmailField->renderHtml();

$adminEmailCcField = new FormItemSingleTextField("Admin Email CC Addresses", false, "admin_email_cc", $counter++);
$adminEmailCcField->setInputValue($admin_email_cc);
$adminEmailCcField->setInputType("email");
$adminEmailCcField->setInputClass("element text medium");
$adminEmailCcField->setInputMaxLength(255);
$adminEmailCcField->setPlaceHolder(" ");
$adminEmailCcField->setGuideText("Enter one or more emails to be CC'ed on camper correspondence.  Separate multiple addresses with commas.");
$adminEmailCcField->setError($adminEmailCcErr);
echo $adminEmailCcField->renderHtml();

$adminEmailFromNameField = new FormItemSingleTextField("Admin Email \"From\" Name", false, "admin_email_from_name", $counter++);
$adminEmailFromNameField->setInputValue($admin_email_from_name);
$adminEmailFromNameField->setInputType("text");
$adminEmailFromNameField->setInputClass("element text medium");
$adminEmailFromNameField->setInputMaxLength(255);
$adminEmailFromNameField->setPlaceHolder(ucfirst(chug_term_singular). " Organizer's Name");
$adminEmailFromNameField->setGuideText("If set, this name will appear as the \"From\" name when email is sent.  If not set, the camp name will be used.");
echo $adminEmailFromNameField->renderHtml();

$enableChugimImporterField = new FormItemCheckBox("Enable " . ucfirst(chug_term_plural) . " Importer", false, "enable_chugim_importer", $counter++);
$enableChugimImporterField->setInputValue($enable_chugim_importer);
$enableChugimImporterField->setGuideText("If this box is checked, an administrator can upload a CSV of chugim information.");
echo $enableChugimImporterField->renderHtml();

$enableCamperImporterField = new FormItemCheckBox("Enable Camper Importer", false, "enable_camper_importer", $counter++);
$enableCamperImporterField->setInputValue($enable_camper_importer);
$enableCamperImporterField->setGuideText("If this box is checked, an administrator can upload a CSV of camper data.");
echo $enableCamperImporterField->renderHtml();

$enableCamperCreationField = new FormItemCheckBox("Enable Camper Creation", false, "enable_camper_creation", $counter++);
$enableCamperCreationField->setInputValue($enable_camper_creation);
$enableCamperCreationField->setGuideText("If this box is checked, campers can signup themselves via the \"First time\" option.");
echo $enableCamperCreationField->renderHtml();

$enableSelectionProcessField = new FormItemCheckBox("Enable Selection Process", false, "enable_selection_process", $counter++);
$enableSelectionProcessField->setInputValue($enable_selection_process);
$enableSelectionProcessField->setGuideText("If this box is checked, campers will be able to submit preferences for " . chug_term_plural . ".");
echo $enableSelectionProcessField->renderHtml();

$sendConfirmEmailField = new FormItemCheckBox("Email Ranking Confirmation to Campers", false, "send_confirm_email", $counter++);
$sendConfirmEmailField->setInputValue($send_confirm_email);
$sendConfirmEmailField->setGuideText("If this box is checked, confirmation of " . chug_term_singular . " choices will be sent to campers.  If not checked, confirmation email will only be sent to the Admin Email CC address(es), if configured.");
echo $sendConfirmEmailField->renderHtml();

$regularUserTokenField = new FormItemSingleTextField("Camper Access Token", false, "regular_user_token", $counter++);
$regularUserTokenField->setInputValue($regular_user_token);
$regularUserTokenField->setInputType("text");
$regularUserTokenField->setInputClass("element text medium");
$regularUserTokenField->setInputMaxLength(50);
$regularUserTokenField->setPlaceHolder("e.g., RamahKayitz");
$regularUserTokenField->setGuideText("The camper access token is used by non-admin users to confirm their login.  It can be any easy-to-remember string.  This value is not a password, just a token, so it should be something simple, e.g., \"RamahKayitz\".");
echo $regularUserTokenField->renderHtml();

$hintField = new FormItemTextArea("Camper Access Token Hint Phrase", false, "regular_user_token_hint", $counter++);
$hintField->setInputValue($regular_user_token_hint);
$hintField->setInputType("text");
$hintField->setInputClass("element textarea medium");
$hintField->setInputMaxLength(512);
$hintField->setPlaceHolder(" ");
$hintField->setGuideText("Optional hint for campers who forget the access token.  Can be anything.");
echo $hintField->renderHtml();

$prefInstructions = new FormItemTextArea("Camper Instructions for Ranking", false, "pref_page_instructions", $counter++);
$prefInstructions->setInputValue($pref_page_instructions);
$prefInstructions->setInputType("text");
$prefInstructions->setInputClass("element textarea medium");
$prefInstructions->setInputMaxLength(2048);
$prefInstructions->setPlaceHolder(" ");
$prefInstructions->setGuideText("These are the instructions campers will see on the ranking page.  HTML tags are OK.");
echo $prefInstructions->renderHtml();

$prefCount = new FormItemSingleTextField("Number of " . ucfirst(chug_term_singular) . " Preferences", false, "pref_count", $counter++);
$prefCount->setInputValue($pref_count);
$prefCount->setInputType("number");
$prefCount->setInputClass("element textarea medium");
$prefCount->setInputClass("element text medium");
$prefCount->setInputMaxLength(2);
$prefCount->setGuideText("Select the number of " . chug_term_singular . " preferences a camper should select (values between 1 and 6, inclusive, are supported).");
echo $prefCount->renderHtml();

$deletableTablesField = new FormItemInstanceChooser("Allow Deletion", false, "deletable_tables", $counter++);
$deletableTablesField->setId2Name($deletableTableId2Name);
$deletableTablesField->setActiveIdHash($deletableTableActiveIdHash);
$deletableTablesField->setGuideText("For data protection, administrators may only delete items in the checked categories.  Check a category to permit deletions.");
echo $deletableTablesField->renderHtml();

$campNameField = new FormItemSingleTextField("Camp Name", true, "camp_name", $counter++);
$campNameField->setInputValue($camp_name);
$campNameField->setInputType("text");
$campNameField->setInputClass("element text medium");
$campNameField->setInputMaxLength(50);
$campNameField->setGuideText("Enter the standard name for this camp, e.g., \"Camp Ramah New England\"");
$campNameField->setPlaceHolder("Camp Ramah New England");
echo $campNameField->renderHtml();

$campWebField = new FormItemSingleTextField("Camp Website", false, "camp_web", $counter++);
$campWebField->setInputValue($camp_web);
$campWebField->setInputType("text");
$campWebField->setInputClass("element text medium");
$campWebField->setInputMaxLength(50);
$campWebField->setGuideText("Enter your camp website, if you have one, e.g., \"www.campramahne.org\"");
$campWebField->setPlaceHolder(" ");
echo $campWebField->renderHtml();
?>

<li>
<label class="description mt-2" for="accordionPassword">
    Update Password(s)
</label>
Update any number of passwords in the applicable sections of the accordion below. Passwords must be between 5-255 characters.

<?php
// 1. create password fields

// 1a. admin
$adminPasswordField = new FormItemSingleTextField("New Admin Password (leave this field blank to keep admin password the same.)",
                    false, "admin_password", $counter++);
$adminPasswordField->setInputType("password");
$adminPasswordField->setInputClass("element text medium");
$adminPasswordField->setInputMaxLength(50);
$adminPasswordField->setPlaceHolder(" ");
$adminPasswordField->setGuideText("Leave this field and the next one blank if you do not wish to change the admin password.");
$adminPasswordStr = $adminPasswordField->renderHtml();

// change from list element to just regular div with same id
$adminPasswordStr = str_replace("<li", "<div class=\"mb-3\"", $adminPasswordStr);
$adminPasswordStr = str_replace("/li>", "/div>", $adminPasswordStr);

$adminPasswordField2 = new FormItemSingleTextField("Retype New Admin Password", false, "admin_password2", $counter++);
$adminPasswordField2->setInputType("password");
$adminPasswordField2->setInputClass("element text medium");
$adminPasswordField2->setInputMaxLength(50);
$adminPasswordField2->setPlaceHolder(" ");
$adminPassword2Str = $adminPasswordField2->renderHtml();

// change from list element to just regular div with same id
$adminPassword2Str = str_replace("<li", "<div", $adminPassword2Str);
$adminPassword2Str = str_replace("/li>", "/div>", $adminPassword2Str);


// 1b. rosh/yoetzet
$roshPasswordField = new FormItemSingleTextField("New Rosh/Yoetzet Password (leave this field blank to keep rosh/yoetzet password the same.)",
                    false, "rosh_password", $counter++);
$roshPasswordField->setInputType("password");
$roshPasswordField->setInputClass("element text medium");
$roshPasswordField->setInputMaxLength(50);
$roshPasswordField->setPlaceHolder(" ");
$roshPasswordField->setGuideText("Leave this field and the next one blank if you do not wish to change the rosh/yoetzet password.");

$roshPasswordStr = $roshPasswordField->renderHtml();

// change from list element to just regular div with same id
$roshPasswordStr = str_replace("<li", "<div class=\"mb-3\"", $roshPasswordStr);
$roshPasswordStr = str_replace("/li>", "/div>", $roshPasswordStr);

$roshPasswordField2 = new FormItemSingleTextField("Retype New Rosh/Yoetzet Password", false, "rosh_password2", $counter++);
$roshPasswordField2->setInputType("password");
$roshPasswordField2->setInputClass("element text medium");
$roshPasswordField2->setInputMaxLength(50);
$roshPasswordField2->setPlaceHolder(" ");

$roshPassword2Str = $roshPasswordField2->renderHtml();

// change from list element to just regular div with same id
$roshPassword2Str = str_replace("<li", "<div", $roshPassword2Str);
$roshPassword2Str = str_replace("/li>", "/div>", $roshPassword2Str);

// 1c. chug leader
$chugLeaderPasswordField = new FormItemSingleTextField("New " . ucfirst($chug_term_singular) . " Leader Password (leave this field blank to keep $chug_term_singular leader password the same.)",
                    false, "chug_leader_password", $counter++);
$chugLeaderPasswordField->setInputType("password");
$chugLeaderPasswordField->setInputClass("element text medium");
$chugLeaderPasswordField->setInputMaxLength(50);
$chugLeaderPasswordField->setPlaceHolder(" ");
$chugLeaderPasswordField->setGuideText("Leave this field and the next one blank if you do not wish to change the $chug_term_singular leader password.");

$chugLeaderPasswordStr = $chugLeaderPasswordField->renderHtml();

// change from list element to just regular div with same id
$chugLeaderPasswordStr = str_replace("<li", "<div class=\"mb-3\"", $chugLeaderPasswordStr);
$chugLeaderPasswordStr = str_replace("/li>", "/div>", $chugLeaderPasswordStr);

$chugLeaderPasswordField2 = new FormItemSingleTextField("Retype New " . ucfirst($chug_term_singular) . " Leader Password", false, "chug_leader_password2", $counter++);
$chugLeaderPasswordField2->setInputType("password");
$chugLeaderPasswordField2->setInputClass("element text medium");
$chugLeaderPasswordField2->setInputMaxLength(50);
$chugLeaderPasswordField2->setPlaceHolder(" ");

$chugLeaderPassword2Str = $chugLeaderPasswordField2->renderHtml();

// change from list element to just regular div with same id
$chugLeaderPassword2Str = str_replace("<li", "<div", $chugLeaderPassword2Str);
$chugLeaderPassword2Str = str_replace("/li>", "/div>", $chugLeaderPassword2Str);

// 2. assemble/output accordion
$passwordAccordion = new bootstrapAccordion($name="Password", $flush=false, $alwaysOpen=false);
$passwordAccordion->addAccordionElement($id="Admin", $title="Admin Password", $body=$adminPasswordStr . $adminPassword2Str, $open=true);
$passwordAccordion->addAccordionElement($id="RoshYoetzet", $title="Rosh/Yoetzet Password", $body=$roshPasswordStr . $roshPassword2Str, $open=false);
$passwordAccordion->addAccordionElement($id="ChugLeader", $title=chug_term_singular . " Leader Password", $body=$chugLeaderPasswordStr . $chugLeaderPassword2Str, $open=false);
echo $passwordAccordion->renderHtml();

?>
</li>

<br>
<li>
<label class="description mt-2" for="accordionTermEdit">
    Update Camp-Specific Terms
</label>
Modify the terms used for certain things in your camp. Please provide both a singular and plural version for each term.
<?php
// 1. create term fields

// 1a. chug
    $chugTermStart = "A " . chug_term_singular . " is a certain activity or elective. Campers rank their preferences for these activities and are assigned to certain ones. For example, a camper may have “soccer” or “painting” as a " . chug_term_singular . " - this refers to the specific activity.";
    $chugTermSingularField = new FormItemSingleTextField("Chug Term (singular)", false, "chug_term_singular", $counter++);
    $chugTermSingularField->setInputType("text");
    $chugTermSingularField->setInputClass("element text medium");
    $chugTermSingularField->setInputMaxLength(50);
    $chugTermSingularField->setPlaceHolder(" ");
    $chugTermSingularField->setInputValue(chug_term_singular);

    $chugTermSingularField2Str = $chugTermSingularField->renderHtml();

    $chugTermSingularField2Str = str_replace("<li", "<div", $chugTermSingularField2Str);
    $chugTermSingularField2Str = str_replace("/li>", "/div>", $chugTermSingularField2Str);

    $chugTermPluralField = new FormItemSingleTextField("Chug Term (plural)", false, "chug_term_plural", $counter++);
    $chugTermPluralField->setInputType("text");
    $chugTermPluralField->setInputClass("element text medium");
    $chugTermPluralField->setInputMaxLength(50);
    $chugTermPluralField->setPlaceHolder(" ");
    $chugTermPluralField->setInputValue(chug_term_plural);
    
    $chugTermPluralField2Str = $chugTermPluralField->renderHtml();

    $chugTermPluralField2Str = str_replace("<li", "<div", $chugTermPluralField2Str);
    $chugTermPluralField2Str = str_replace("/li>", "/div>", $chugTermPluralField2Str);


// 1b. block
    $blockTermStart = "A " .  ucfirst(block_term_singular) . " is a time period. For example, camp may be made up of multiple sessions (e.g. 2 sessions, each lasting 4 weeks) where campers have multiple elective cycles. If the sessions are “1st session” and “2nd session,” the " . block_term_plural . " may be “Weeks 1+2,” “Weeks 3+4,” “Weeks 5+6,” and “Weeks 7+8.” It is unlikely you will need to change this term, mostly just including it in case you have a different preference.";
    $blockTermSingularField = new FormItemSingleTextField("Block Term (singular)", false, "block_term_singular", $counter++);
    $blockTermSingularField->setInputType("text");
    $blockTermSingularField->setInputClass("element text medium");
    $blockTermSingularField->setInputMaxLength(50);
    $blockTermSingularField->setPlaceHolder(" ");
    $blockTermSingularField->setInputValue(block_term_singular);
    
    $blockTermSingularField2Str = $blockTermSingularField->renderHtml();

    $blockTermSingularField2Str = str_replace("<li", "<div", $blockTermSingularField2Str);
    $blockTermSingularField2Str = str_replace("/li>", "/div>", $blockTermSingularField2Str);
    
    $blockTermPluralField = new FormItemSingleTextField("Block Term (plural)", false, "block_term_plural", $counter++);
    $blockTermPluralField->setInputType("text");
    $blockTermPluralField->setInputClass("element text medium");
    $blockTermPluralField->setInputMaxLength(50);
    $blockTermPluralField->setPlaceHolder(" ");
    $blockTermPluralField->setInputValue(block_term_plural);

    $blockTermPluralField2Str = $blockTermPluralField->renderHtml();

    $blockTermPluralField2Str = str_replace("<li", "<div", $blockTermPluralField2Str);
    $blockTermPluralField2Str = str_replace("/li>", "/div>", $blockTermPluralField2Str);

// 1c. edah
    $edahTermStart = "An " . edah_term_singular . " is an age group at camp. Different camps utilize different spellings of the term; specify your version here.";
    $edahTermSingularField = new FormItemSingleTextField("Edah Term (singular)", false, "edah_term_singular", $counter++);
    $edahTermSingularField->setInputType("text");
    $edahTermSingularField->setInputClass("element text medium");
    $edahTermSingularField->setInputMaxLength(50);
    $edahTermSingularField->setPlaceHolder(" ");
    $edahTermSingularField->setInputValue(edah_term_singular);
    
    $edahTermSingularField2Str = $edahTermSingularField->renderHtml();

    $edahTermSingularField2Str = str_replace("<li", "<div", $edahTermSingularField2Str);
    $edahTermSingularField2Str = str_replace("/li>", "/div>", $edahTermSingularField2Str);
    
    $edahTermPluralField = new FormItemSingleTextField("Edah Term (plural)", false, "edah_term_plural", $counter++);
    $edahTermPluralField->setInputType("text");
    $edahTermPluralField->setInputClass("element text medium");
    $edahTermPluralField->setInputMaxLength(50);
    $edahTermPluralField->setPlaceHolder(" ");
    $edahTermPluralField->setInputValue(edah_term_plural);

    $edahTermPluralField2Str = $edahTermPluralField->renderHtml();

    $edahTermPluralField2Str = str_replace("<li", "<div", $edahTermPluralField2Str);
    $edahTermPluralField2Str = str_replace("/li>", "/div>", $edahTermPluralField2Str);

// 2. assemble/output accordion
$termEditAccordion = new bootstrapAccordion($name="TermEdit", $flush=false, $alwaysOpen=false);
$termEditAccordion->addAccordionElement($id="Chug", $title=ucfirst(chug_term_singular) . " Term", $body=$chugTermStart . $chugTermSingularField2Str . $chugTermPluralField2Str, $open=true);
$termEditAccordion->addAccordionElement($id="Block", $title=ucfirst(block_term_singular) . " Term", $body=$blockTermStart . $blockTermSingularField2Str . $blockTermPluralField2Str, $open=false);
$termEditAccordion->addAccordionElement($id="Edah", $title=ucfirst(edah_term_singular) . " Term", $body=$edahTermStart . $edahTermSingularField2Str . $edahTermPluralField2Str, $open=false);
echo $termEditAccordion->renderHtml();

?>
</li>

<li class="buttons">
<input id="saveForm" class="btn btn-primary" type="submit" name="submit" value="Submit" />
<?php
$cancelUrl = urlIfy("staffHome.php");
echo "<a class=\"btn btn-link\" href=\"$cancelUrl\">Cancel</a>";
?>
</li>
</ul>
</form>
</div>

<?php
echo footerText();
?>

</body>
</html>
