<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    $editGroupPage = new EditPage("Edit Group", "Please update group information as needed",
                                  "groups", "group_id");
    $editGroupPage->addColumn("name");
    $editGroupPage->handleSubmit();
    
    $nameField = new FormItemSingleTextField("Group Name", TRUE, "name", 0);
    $nameField->setInputType("text");
    $nameField->setInputClass("element text medium");
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($editGroupPage->columnValue("name"));
    $nameField->setError($editGroupPage->errForColName("name"));
    $nameField->setGuideText("Choose a group name (e.g., aleph or bet)");    
    $editGroupPage->addFormItem($nameField);
    
    $editGroupPage->renderForm();
    
    ?>
    
