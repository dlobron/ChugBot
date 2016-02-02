<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    $editGroupPage = new EditPage("Edit Group", "Please update group information as needed",
                                  "groups", "group_id");
    $editGroupPage->addColumn("name");
    $editGroupPage->handlePost();
    
    $nameField = new FormItemSingleTextField("Group Name", TRUE, "name",
                                             "element text medium", "text", 0);
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($editGroupPage->columnValue("name"));
    $nameField->setError($editGroupPage->nameErr);
    $nameField->setGuideText("Choose a group name (e.g., aleph or bet)");    
    $editGroupPage->addFormItem($nameField);
    
    $editGroupPage->renderForm(TRUE);
    
    ?>
    