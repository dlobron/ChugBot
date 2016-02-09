<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    
    $addCamperPage = new AddPage("Add a Camper", "Please enter camper information here",
                                 "campers", "camper_id");
    $addCamperPage->addColumn("first");
    $addCamperPage->addColumn("last");
    $addCamperPage->addColumn("email");
    $addCamperPage->addColumn("session_id");
    $addCamperPage->addColumn("edah_id");

    $addCamperPage->handlePost();

    $firstNameField = new FormItemSingleTextField("First Name", TRUE, "first", 0);
    $firstNameField->setInputType("text");
    $firstNameField->setInputClass("element text medium");
    $firstNameField->setInputMaxLength(255);
    $firstNameField->setInputValue($addCamperPage->columnValue("first"));
    $firstNameField->setPlaceHolder("First Name");
    $firstNameField->setError($addCamperPage->errForColName("first"));
    $addCamperPage->addFormItem($firstNameField);

    $lastNameField = new FormItemSingleTextField("Last Name", TRUE, "last", 1);
    $lastNameField->setInputType("text");
    $lastNameField->setInputClass("element text medium");
    $lastNameField->setInputMaxLength(255);
    $lastNameField->setInputValue($addCamperPage->columnValue("last"));
    $lastNameField->setPlaceHolder("Last Name");
    $lastNameField->setError($addCamperPage->errForColName("last"));
    $addCamperPage->addFormItem($lastNameField);

    $emailField = new FormItemSingleTextField("Email address", TRUE, "email", 2);
    $emailField->setInputType("text");
    $emailField->setInputClass("element text medium");
    $emailField->setInputMaxLength(255);
    $emailField->setInputValue($addCamperPage->columnValue("email"));
    $emailField->setPlaceHolder("Email address");
    $emailField->setError($addCamperPage->errForColName("email"));
    $emailField->setGuideText("Please include an email address (you can use the same email for more than one camper)");
    $addCamperPage->addFormItem($emailField);

    $sessionIdVal = $addCamperPage->columnValue("session_id"); // May be NULL.
    $sessionDropDown = new FormItemDropDown("Session", TRUE, "session_id", 3);
    $sessionDropDown->setGuideText("Choose your camp session.");
    $sessionDropDown->setError($addCamperPage->errForColName("session_id"));
    $sessionDropDown->setInputClass("element select medium");
    $sessionDropDown->setInputSingular("session");
    $sessionDropDown->setColVal($sessionIdVal);
    $sessionDropDown->fillDropDownId2Name($addCamperPage->mysqli, $addCamperPage->dbErr,
                                        "session_id", "sessions");
    $addCamperPage->addFormItem($sessionDropDown);
    
    $edahIdVal = $addCamperPage->columnValue("edah_id"); // May be NULL.
    $edahDropDown = new FormItemDropDown("Edah", TRUE, "edah_id", 4);
    $edahDropDown->setGuideText("Choose your Edah!");
    $edahDropDown->setError($addCamperPage->errForColName("edah_id"));
    $edahDropDown->setInputSingular("edah");
    $edahDropDown->setInputClass("element select medium");
    $edahDropDown->setColVal($sessionIdVal);
    $edahDropDown->fillDropDownId2Name($addCamperPage->mysqli, $addCamperPage->dbErr,
                                       "edah_id", "edot");
    $addCamperPage->addFormItem($edahDropDown);
    
    $addCamperPage->renderForm();
    
?>

    