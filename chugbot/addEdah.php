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
    $nameField->setInputType("text");
    $nameField->setInputClass("element text medium");
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($addEdahPage->columnValue("name"));
    $nameField->setError($addEdahPage->errForColName("name"));
    $nameField->setGuideText("Choose your edah name (Kochavim, Ilanot 1, etc.)");
    $addEdahPage->addFormItem($nameField);
   
    $roshField = new FormItemSingleTextField("Rosh Edah (head counselor) Name", FALSE, "rosh_name", 1);
    $roshField->setInputType("text");
    $roshField->setInputClass("element text medium");
    $roshField->setInputMaxLength(255);
    $roshField->setInputValue($addEdahPage->columnValue("rosh_name"));
    $roshField->setGuideText("Enter the head counselor name (optional)");
    $addEdahPage->addFormItem($roshField);
    
    $roshPhoneField = new FormItemSingleTextField("Rosh Edah Phone", FALSE, "rosh_phone", 2);
    $roshPhoneField->setInputType("text");
    $roshPhoneField->setInputClass("element text medium");
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