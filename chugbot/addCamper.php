<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
camperBounceToLogin();
checkLogout();

$addCamperPage = new AddCamperPage("Add a Camper", "Step 1: Enter camper information",
    "campers", "camper_id");
$addCamperPage->setAlternateResultString("Please enter your camper info below.");
$addCamperPage->addColumn("first");
$addCamperPage->addColumn("last");
$addCamperPage->addColumn("email");
$addCamperPage->addColumn("email2", false, false);
$addCamperPage->addColumn("session_id", true, true);
$addCamperPage->addColumn("edah_id", true, true);
$addCamperPage->addColumn("bunk_id", false, true);

$addCamperPage->handleSubmit();

$firstNameField = new FormItemSingleTextField("First Name", true, "first", 0);
$firstNameField->setInputType("text");
$firstNameField->setInputClass("element text medium");
$firstNameField->setInputMaxLength(255);
$firstNameField->setInputValue($addCamperPage->columnValue("first"));
$firstNameField->setPlaceHolder("First Name");
$firstNameField->setError($addCamperPage->errForColName("first"));
$addCamperPage->addFormItem($firstNameField);

$lastNameField = new FormItemSingleTextField("Last Name", true, "last", 1);
$lastNameField->setInputType("text");
$lastNameField->setInputClass("element text medium");
$lastNameField->setInputMaxLength(255);
$lastNameField->setInputValue($addCamperPage->columnValue("last"));
$lastNameField->setPlaceHolder("Last Name");
$lastNameField->setError($addCamperPage->errForColName("last"));
$addCamperPage->addFormItem($lastNameField);

$emailField = new FormItemSingleTextField("Email address", true, "email", 2);
$emailField->setInputType("email");
$emailField->setInputClass("element text medium");
$emailField->setInputMaxLength(255);
$emailField->setInputValue($addCamperPage->columnValue("email"));
$emailField->setPlaceHolder("Email address");
$emailField->setError($addCamperPage->errForColName("email"));
$emailField->setGuideText("Please include an email address (you can use the same email for more than one camper)");
$addCamperPage->addFormItem($emailField);

$email2Field = new FormItemSingleTextField("Secondary email address", false, "email2", 3); // May be NULL.
$email2Field->setInputType("email");
$email2Field->setInputClass("element text medium");
$email2Field->setInputMaxLength(255);
$email2Field->setInputValue($addCamperPage->columnValue("email2"));
$email2Field->setPlaceHolder("Secondary email address");
$email2Field->setGuideText("If you have an alternative/second email address that should receive alerts, please include it here");
$addCamperPage->addFormItem($email2Field);

$sessionIdVal = $addCamperPage->columnValue("session_id"); // May be NULL.
$sessionDropDown = new FormItemDropDown("Session", true, "session_id", 4);
$sessionDropDown->setGuideText("Choose your camp session.");
$sessionDropDown->setError($addCamperPage->errForColName("session_id"));
$sessionDropDown->setInputClass("element medium");
$sessionDropDown->setInputSingular("session");
$sessionDropDown->setColVal($sessionIdVal);
$sessionDropDown->fillDropDownId2Name($addCamperPage->dbErr,
    "session_id", "sessions");
$addCamperPage->addFormItem($sessionDropDown);

$edahIdVal = $addCamperPage->columnValue("edah_id"); // May be NULL.
$edahDropDown = new FormItemDropDown("Edah", true, "edah_id", 5);
$edahDropDown->setGuideText("Choose your Edah!");
$edahDropDown->setError($addCamperPage->errForColName("edah_id"));
$edahDropDown->setInputSingular("edah");
$edahDropDown->setInputClass("element medium");
$edahDropDown->setColVal($edahIdVal);
$edahDropDown->fillDropDownId2Name($addCamperPage->dbErr,
    "edah_id", "edot");
$addCamperPage->addFormItem($edahDropDown);

$bunkIdVal = $addCamperPage->columnValue("bunk_id"); // May be NULL.
$bunkDropDown = new FormItemConstrainedDropDown("Bunk/Tzrif", false, "bunk_id", 6,
    "SELECT b.bunk_id id_val, b.name name_val FROM bunks b, " .
    "bunk_instances i WHERE b.bunk_id = i.bunk_id AND i.edah_id = ?");
$bunkDropDown->setGuideText("Choose your bunk (you can leave this blank if you do not know it yet!).  You must choose your Edah first.");
$bunkDropDown->setInputClass("element medium");
$bunkDropDown->setParentIdAndName("edah_id", "Edah");
$bunkDropDown->setColVal($bunkIdVal);
$addCamperPage->addFormItem($bunkDropDown);

$addCamperPage->renderForm();
