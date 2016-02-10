<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    
    $editCamperPage = new EditPage("Edit Camper",
                                   "Please edit your camper information below, and click Submit to update.",
                                   "campers", "camper_id");
    $editCamperPage->addSecondParagraph("When you are finished editing, click Continue to go to the preference ranking page");
    $editCamperPage->addColumn("first");
    $editCamperPage->addColumn("last");
    $editCamperPage->addColumn("email");
    $editCamperPage->addColumn("session_id");
    $editCamperPage->addColumn("edah_id");
    $editCamperPage->addColumn("needs_first_choice", FALSE, 0, TRUE);
    $editCamperPage->addColumn("inactive", FALSE, 0, TRUE);
    $editCamperPage->setSubmitAndContinueTarget("rankCamperChoices.html");

    $editCamperPage->handlePost();

    $firstNameField = new FormItemSingleTextField("First Name", TRUE, "first", 0);
    $firstNameField->setInputType("text");
    $firstNameField->setInputClass("element text medium");
    $firstNameField->setInputMaxLength(255);
    $firstNameField->setInputValue($editCamperPage->columnValue("first"));
    $firstNameField->setPlaceHolder("First Name");
    $firstNameField->setError($editCamperPage->errForColName("first"));
    $editCamperPage->addFormItem($firstNameField);

    $lastNameField = new FormItemSingleTextField("Last Name", TRUE, "last", 1);
    $lastNameField->setInputType("text");
    $lastNameField->setInputClass("element text medium");
    $lastNameField->setInputMaxLength(255);
    $lastNameField->setInputValue($editCamperPage->columnValue("last"));
    $lastNameField->setPlaceHolder("Last Name");
    $lastNameField->setError($editCamperPage->errForColName("last"));
    $editCamperPage->addFormItem($lastNameField);

    $emailField = new FormItemSingleTextField("Email address", TRUE, "email", 2);
    $emailField->setInputType("text");
    $emailField->setInputClass("element text medium");
    $emailField->setInputMaxLength(255);
    $emailField->setInputValue($editCamperPage->columnValue("email"));
    $emailField->setPlaceHolder("Email address");
    $emailField->setError($editCamperPage->errForColName("email"));
    $emailField->setGuideText("Please include an email address (you can use the same email for more than one camper)");
    $editCamperPage->addFormItem($emailField);

    $sessionIdVal = $editCamperPage->columnValue("session_id"); // May be NULL.
    $sessionDropDown = new FormItemDropDown("Session", TRUE, "session_id", 3);
    $sessionDropDown->setGuideText("Choose your camp session.");
    $sessionDropDown->setInputClass("element select medium");
    $sessionDropDown->setError($editCamperPage->errForColName("session_id"));
    $sessionDropDown->setInputSingular("session");
    $sessionDropDown->setColVal($sessionIdVal);
    $sessionDropDown->fillDropDownId2Name($editCamperPage->mysqli, $editCamperPage->dbErr,
                                        "session_id", "sessions");
    $editCamperPage->addFormItem($sessionDropDown);
    
    $edahIdVal = $editCamperPage->columnValue("edah_id"); // May be NULL.
    $edahDropDown = new FormItemDropDown("Edah", TRUE, "edah_id", 4);
    $edahDropDown->setGuideText("Choose your Edah!");
    $edahDropDown->setError($editCamperPage->errForColName("edah_id"));
    $edahDropDown->setInputSingular("edah");
    $edahDropDown->setInputClass("element select medium");
    $edahDropDown->setColVal($edahIdVal);
    $edahDropDown->fillDropDownId2Name($editCamperPage->mysqli, $editCamperPage->dbErr,
                                       "edah_id", "edot");
    $editCamperPage->addFormItem($edahDropDown);
    
    // Add two fields that are only visible to staff.  These only apply to the edit page, not the add
    // page.
    $needsFirstChoiceVal = $editCamperPage->columnValue("needs_first_choice");
    $needsFirstChoiceBox = new FormItemCheckBox("Needs first choice", FALSE, "needs_first_choice", 5);
    $needsFirstChoiceBox->setGuideText("Check this box if this camper should always get their first choice chug.");
    $needsFirstChoiceBox->setStaffOnly(TRUE);
    $needsFirstChoiceBox->setInputValue($needsFirstChoiceVal);
    $editCamperPage->addFormItem($needsFirstChoiceBox);
    
    $inactiveVal = $editCamperPage->columnValue("inactive");
    $inactiveBox = new FormItemCheckBox("Inactive", FALSE, "inactive", 6);
    $inactiveBox->setGuideText("If you check this box, this camper will not be assigned.");
    $inactiveBox->setStaffOnly(TRUE);
    $inactiveBox->setInputValue($inactiveVal);
    $editCamperPage->addFormItem($inactiveBox);
    
    $editCamperPage->renderForm();
    
?>

    