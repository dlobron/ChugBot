<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
camperBounceToLogin();
checkLogout();
setup_camp_specific_terminology_constants();

$db = new DbConn();
$sql = "SELECT enable_selection_process FROM admin_data";
$err = "";
$result = $db->runQueryDirectly($sql, $err);
$enableSelectionProcess = true;
if ($result) {
    $row = $result->fetch_assoc();
    if ($row) {
        $enableSelectionProcess = (bool)$row["enable_selection_process"];
    }
}

$editCamperPage = new EditPage("Review Camper Information",
    "Please review your information below, and make any necessary edits.",
    "campers", "camper_id");
$editCamperPage->addSecondParagraph("Then click <b>Update " . ucfirst(chug_term_plural) . "</b> to update your " . chug_term_singular . " preferences, or click <b>Save and Exit</b> to save your changes without updating " . chug_term_singular . " preferences.");
$editCamperPage->setAlternateResultString("Please review your information below, make any edits needed, and then click <b>Choose " . chug_term_plural . "</b> to make your " . chug_term_singular . " rankings.");
$editCamperPage->addColumn("first");
$editCamperPage->addColumn("last");
$editCamperPage->addColumn("email");
$editCamperPage->addColumn("email2", false, false);
$editCamperPage->addColumn("session_id", true, true);
$editCamperPage->addColumn("edah_id", true, true);
$editCamperPage->addColumn("bunk_id", false, true);
$editCamperPage->addColumn("needs_first_choice", false, true, 0);
$editCamperPage->addColumn("inactive", false, true, 0);
if ($enableSelectionProcess) {
    $editCamperPage->setSubmitAndContinueTarget("rankCamperChoices.html", "Update " . ucfirst(chug_term_plural));
    $editCamperPage->setSaveAndReturnLabel("Save and Exit");
}

$editCamperPage->handleSubmit();

$firstNameField = new FormItemSingleTextField("First Name", true, "first", 0);
$firstNameField->setInputType("text");
$firstNameField->setInputClass("element text medium");
$firstNameField->setInputMaxLength(255);
$firstNameField->setInputValue($editCamperPage->columnValue("first"));
$firstNameField->setPlaceHolder("First Name");
$firstNameField->setError($editCamperPage->errForColName("first"));
$editCamperPage->addFormItem($firstNameField);

$lastNameField = new FormItemSingleTextField("Last Name", true, "last", 1);
$lastNameField->setInputType("text");
$lastNameField->setInputClass("element text medium");
$lastNameField->setInputMaxLength(255);
$lastNameField->setInputValue($editCamperPage->columnValue("last"));
$lastNameField->setPlaceHolder("Last Name");
$lastNameField->setError($editCamperPage->errForColName("last"));
$editCamperPage->addFormItem($lastNameField);

$emailField = new FormItemSingleTextField("Email address", true, "email", 2);
$emailField->setInputType("email");
$emailField->setInputClass("element text medium");
$emailField->setInputMaxLength(255);
$emailField->setInputValue($editCamperPage->columnValue("email"));
$emailField->setPlaceHolder("Email address");
$emailField->setError($editCamperPage->errForColName("email"));
$emailField->setGuideText("Please include an email address (you can use the same email for more than one camper)");
$editCamperPage->addFormItem($emailField);

$email2Field = new FormItemSingleTextField("Secondary email address", false, "email2", 3); // May be NULL.
$email2Field->setInputType("email");
$email2Field->setInputClass("element text medium");
$email2Field->setInputMaxLength(255);
$email2Field->setInputValue($editCamperPage->columnValue("email2"));
$email2Field->setPlaceHolder("Secondary email address");
$email2Field->setGuideText("If you have an alternative/second email address that should receive alerts, please include it here");
$editCamperPage->addFormItem($email2Field);

$sessionIdVal = $editCamperPage->columnValue("session_id"); // May be NULL.
$sessionDropDown = new FormItemDropDown("Session", true, "session_id", 4);
$sessionDropDown->setGuideText("Choose your camp session.");
$sessionDropDown->setInputClass("element medium");
$sessionDropDown->setError($editCamperPage->errForColName("session_id"));
$sessionDropDown->setInputSingular("session");
$sessionDropDown->setColVal($sessionIdVal);
$sessionDropDown->fillDropDownId2Name($editCamperPage->dbErr,
    "session_id", "sessions");
$editCamperPage->addFormItem($sessionDropDown);

$edahIdVal = $editCamperPage->columnValue("edah_id"); // May be NULL.
$edahDropDown = new FormItemDropDown(ucfirst(edah_term_singular), true, "edah_id", 5);
$edahDropDown->setGuideText("Choose your " . ucfirst(edah_term_singular) . "!");
$edahDropDown->setError($editCamperPage->errForColName("edah_id"));
$edahDropDown->setInputSingular("edah");
$edahDropDown->setInputClass("element medium");
$edahDropDown->setColVal($edahIdVal);
$edahDropDown->fillDropDownId2Name($editCamperPage->dbErr,
    "edah_id", "edot");
$editCamperPage->addFormItem($edahDropDown);

$bunkIdVal = $editCamperPage->columnValue("bunk_id"); // May be NULL.
$bunkDropDown = new FormItemConstrainedDropDown("Bunk/Tzrif", false, "bunk_id", 6,
    "SELECT b.bunk_id id_val, b.name name_val FROM bunks b, " .
    "bunk_instances i WHERE b.bunk_id = i.bunk_id AND i.edah_id = ? ORDER BY name+0>0 DESC, name+0,LENGTH(name), name");
$bunkDropDown->setGuideText("Choose your bunk (you can leave this blank if you do not know it yet!).  You must choose your Edah first.");
$bunkDropDown->setInputClass("element medium");
$bunkDropDown->setParentIdAndName("edah_id", "Edah");
$bunkDropDown->setColVal($bunkIdVal);
$editCamperPage->addFormItem($bunkDropDown);

// Add two fields that are only visible to staff.  These apply only to the edit page, not the add
// page.
$needsFirstChoiceVal = $editCamperPage->columnValue("needs_first_choice");
$needsFirstChoiceBox = new FormItemCheckBox("Needs first choice", false, "needs_first_choice", 7);
$needsFirstChoiceBox->setGuideText("Check this box if this camper should always get their first choice " . chug_term_singular . ".");
$needsFirstChoiceBox->setStaffOnly(true);
$needsFirstChoiceBox->setInputValue($needsFirstChoiceVal);
$editCamperPage->addFormItem($needsFirstChoiceBox);

$inactiveVal = $editCamperPage->columnValue("inactive");
$inactiveBox = new FormItemCheckBox("Inactive", false, "inactive", 8);
$inactiveBox->setGuideText("If you check this box, this camper will not be assigned.");
$inactiveBox->setStaffOnly(true);
$inactiveBox->setInputValue($inactiveVal);
$editCamperPage->addFormItem($inactiveBox);

$editCamperPage->renderForm();
