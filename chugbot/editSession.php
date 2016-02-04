<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    $editSessionPage = new EditPage("Edit Session", "Please update session information as needed",
                                    "sessions", "session_id");
    $editSessionPage->addColumn("name");
    
    $editSessionPage->handlePost();
    
    $nameField = new FormItemSingleTextField("Session Name", TRUE, "name", 0);
    $nameField->setInputType("element text medium");
    $nameField->setInputClass("text");
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($editSessionPage->columnValue("name"));
    $nameField->setError($editSessionPage->nameErr);
    $nameField->setGuideText("Choose a session name (e.g., (e.g., \"July\", \"August\", \"Full Summer\")");    
    $editSessionPage->addFormItem($nameField);
    
    $editSessionPage->renderForm();
    
    ?>
    
