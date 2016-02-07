<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();

    $addEdahPage = new AddPage("Add Edah",
                               "Please enter your edah information",
                               "edot", "edah_id");
    $addEdahPage->addColumn("name");
    $addEdahPage->addColumn("rosh_name", FALSE);
    $addEdahPage->addColumn("rosh_phone", FALSE);
    $addEdahPage->addColumn("comments", FALSE);
    $addEdahPage->handlePost();
    
    $nameField = new FormItemSingleTextField("Edah Name", TRUE, "name", 0);
    $nameField->setInputClass("element text medium");
    $nameField->setInputType("text");
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($addEdahPage->columnValue("name"));
    $nameField->setError($addEdahPage->nameErr);
    $nameField->setGuideText("Choose your edah name (Kochavim, Ilanot 1, etc.)");
    $addEdahPage->addFormItem($nameField);
   
    $roshField = new FormItemSingleTextField("Rosh Edah (head counselor) Name", FALSE, "rosh_name", 1);
    $roshField->setInputClass("element text medium");
    $roshField->setInputType("text");
    $roshField->setInputMaxLength(255);
    $roshField->setInputValue($addEdahPage->columnValue("rosh_name"));
    $roshField->setGuideText("Enter the head counselor name (optional)");
    $addEdahPage->addFormItem($roshField);
    
    $roshPhoneField = new FormItemSingleTextField("Rosh Edah Phone", FALSE, "rosh_phone", 2);
    $roshPhoneField->setInputType("element text medium");
    $roshPhoneField->setInputClass("text");
    $roshPhoneField->setInputMaxLength(255);
    $roshPhoneField->setInputValue($addEdahPage->columnValue("rosh_phone"));
    $roshPhoneField->setGuideText("Phone number for the head counselor (optional)");
    $addEdahPage->addFormItem($roshPhoneField);
    
    $commentsField = new FormItemTextArea("Comments", FALSE, "comments", 3);
    $commentsField->setInputClass("element textarea medium");
    $commentsField->setInputValue($addEdahPage->columnValue("comments"));
    $commentsField->setGuideText("Comments about this Edah (optional)");
    $addEdahPage->addFormItem($commentsField);

    $addEdahPage->renderForm();
?>