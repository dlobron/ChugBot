<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();

    $addGroupPage = new AddSingletonPage("Add Session",
                                         "Please enter session information",
                                         "sessions", "session_id");
    $addGroupPage->handlePost();
    
    $nameField = new FormItemSingleTextField("Session Name", TRUE, "name",
                                             "element text medium", "text", 0);
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($addGroupPage->name);
    $nameField->setError($addGroupPage->nameErr);
    $nameField->setGuideText("Choose a session name (e.g., (e.g., \"July\", \"August\", \"Full Summer\")");
                                             
    $addGroupPage->addFormItem($nameField);

    $addGroupPage->renderForm();
?>