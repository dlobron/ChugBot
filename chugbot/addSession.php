<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();

    $addSessionPage = new AddPage("Add Session",
                                  "Please enter session information",
                                  "sessions", "session_id");
    $addSessionPage->addColumn("name");
    $addSessionPage->handlePost();
    
    $nameField = new FormItemSingleTextField("Session Name", TRUE, "name", 0);
    $nameField->setInputType("element text medium");
    $nameField->setInputClass("text");
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($addSessionPage->columnValue("name"));
    $nameField->setError($addSessionPage->nameErr);
    $nameField->setGuideText("Choose a session name (e.g., (e.g., \"July\", \"August\", \"Full Summer\")");                                             
    $addSessionPage->addFormItem($nameField);        

    $addSessionPage->renderForm();
?>