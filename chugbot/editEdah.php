<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();

    $editEdahPage = new EditPage("Edit Edah",
                                 "Please update edah information as needed",
                                 "edot", "edah_id");
    $editEdahPage->addColumn("name");
    $editEdahPage->addColumn("rosh_name", FALSE);
    $editEdahPage->addColumn("rosh_phone", FALSE);
    $editEdahPage->addColumn("comments", FALSE);
    $editEdahPage->handlePost();
    
    $nameField = new FormItemSingleTextField("Edah Name", TRUE, "name", 0);
    $nameField->setInputClass("element text medium");
    $nameField->setInputType("text");
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($editEdahPage->columnValue("name"));
    $nameField->setError($editEdahPage->nameErr);
    $nameField->setGuideText("Choose your edah name (Kochavim, Ilanot 1, etc.)");
    $editEdahPage->addFormItem($nameField);
   
    $roshField = new FormItemSingleTextField("Rosh Edah (head counselor) Name", FALSE, "rosh_name", 1);
    $roshField->setInputClass("element text medium");
    $roshField->setInputType("text");
    $roshField->setInputMaxLength(255);
    $roshField->setInputValue($editEdahPage->columnValue("rosh_name"));
    $roshField->setGuideText("Enter the head counselor name (optional)");
    $editEdahPage->addFormItem($roshField);
    
    $roshPhoneField = new FormItemSingleTextField("Rosh Edah Phone", FALSE, "rosh_phone", 2);
    $roshPhoneField->setInputType("element text medium");
    $roshPhoneField->setInputClass("text");
    $roshPhoneField->setInputMaxLength(255);
    $roshPhoneField->setInputValue($editEdahPage->columnValue("rosh_phone"));
    $roshPhoneField->setGuideText("Phone number for the head counselor (optional)");
    $editEdahPage->addFormItem($roshPhoneField);
    
    $commentsField = new FormItemTextArea("Comments", FALSE, "comments", 3);
    $commentsField->setInputClass("element textarea medium");
    $commentsField->setInputValue($editEdahPage->columnValue("comments"));
    $commentsField->setGuideText("Comments about this Edah (optional)");
    $editEdahPage->addFormItem($commentsField);

    $editEdahPage->renderForm();
?>