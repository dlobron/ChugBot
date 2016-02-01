<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();

    $addGroupPage = new AddSingletonPage("Add Group",
                                         "Please enter your group information",
                                         "groups", "group_id");
    $addGroupPage->handlePost();
    
    $nameField = new FormItemSingleTextField("Group Name", TRUE, "name",
                                             "element text medium", "text", 0);
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($addGroupPage->name);
    $nameField->setError($addGroupPage->nameErr);
    $nameField->setGuideText("Choose a group name (e.g., aleph or bet)");
                                             
    $addGroupPage->addFormItem($nameField);

    $addGroupPage->renderForm();

?>