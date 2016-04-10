<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();

    $addGroupPage = new AddPage("Add Group",
                                "Please enter your group information",
                                "groups", "group_id");
    $addGroupPage->addColumn("name");
    $addGroupPage->handleSubmit();
    
    $nameField = new FormItemSingleTextField("Group Name", TRUE, "name", 0);
    $nameField->setInputType("text");
    $nameField->setInputClass("element text medium");
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($addGroupPage->columnValue("name"));
    $nameField->setError($addGroupPage->errForColName("name"));
    $nameField->setGuideText("Choose a group name (e.g., aleph or bet)");                                             
    $addGroupPage->addFormItem($nameField);

    $addGroupPage->renderForm();

?>