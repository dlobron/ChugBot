<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    $editSessionPage = new EditSingletonPage("Edit Session", "Please update session information as needed",
                                             "sessions", "session_id");
    
    $editSessionPage->handlePost();
    
    $nameField = new FormItemSingleTextField("Session Name", TRUE, "name",
                                             "element text medium", "text", 0);
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($editSessionPage->name);
    $nameField->setError($editSessionPage->nameErr);
    $nameField->setGuideText("Choose a session name (e.g., (e.g., \"July\", \"August\", \"Full Summer\")");
    
    $editSessionPage->addFormItem($nameField);
    
    $editSessionPage->renderForm(TRUE);
    
    ?>
    