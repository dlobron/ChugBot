<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    $editBlockPage = new EditPage("Edit Block",
                                  "Please edit information for this block",
                                  "blocks", "block_id");
    $editBlockPage->addColumn("name");
    $editBlockPage->addInstanceTable("block_instances");
    $secondParagraph = <<<EOM
A block is a time period for an activity: for example, weeks 1-2 of July.  Each block is associated with one or more sessions: for example, "July 1" might be associated with July and July+August sessions (a session is the unit of time that a camper signs up for).  You can add or edit sessions for this block later if you are not sure right now which sessions to assign.
EOM;
    $editBlockPage->addSecondParagraph($secondParagraph);
    $editBlockPage->fillInstanceId2Name("session_id", "sessions");
    
    $editBlockPage->handlePost();

    $nameField = new FormItemSingleTextField("Block Name", TRUE, "name", 0);
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($editBlockPage->columnValue("name"));
    $nameField->setError($editBlockPage->errForColName("name"));
    $nameField->setGuideText("Choose a name for this block (e.g., \"July Week 1\", \"Mini Session Aleph\", etc.)");
    $editBlockPage->addFormItem($nameField);

    $sessionChooserField = new FormItemInstanceChooser("Sessions", FALSE, "session_ids", 1);
    $sessionChooserField->setId2Name($editBlockPage->instanceId2Name);
    $sessionChooserField->setActiveIdHash($editBlockPage->instanceActiveIdHash);
    $sessionChooserField->setGuideText("Choose each session that contains this time block (you can do this later if you are not sure now).");
    $editBlockPage->addFormItem($sessionChooserField);

    $editBlockPage->renderForm();
?>