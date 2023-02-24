<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
bounceToLogin();
setup_camp_specific_terminology_constants();

$block_term_singular = block_term_singular;
$addBlockPage = new AddPage("Add " . ucfirst(block_term_singular),
    "Please enter information for this " . block_term_singular,
    "blocks", "block_id");
$addBlockPage->addColumn("name");
$addBlockPage->addColumn("visible_to_campers", true, true);
$addBlockPage->addInstanceTable("block_instances");
$secondParagraph = <<<EOM
A $block_term_singular is a time period for an activity: for example, weeks 1-2 of July.  Each $block_term_singular is associated with one or more sessions: for example, "July 1" might be associated with July and July+August sessions (a session is the unit of time that a camper signs up for).  You can add or edit sessions for this $block_term_singular later if you are not sure right now which sessions to assign.
EOM;
$addBlockPage->addSecondParagraph($secondParagraph);
$addBlockPage->fillInstanceId2Name("session_id", "sessions");
$addBlockPage->setActiveEdotFilterBy("block");

$addBlockPage->handleSubmit();

$nameField = new FormItemSingleTextField(ucfirst(block_term_singular) . " Name", true, "name", 0);
$nameField->setInputMaxLength(255);
$nameField->setInputValue($addBlockPage->columnValue("name"));
$nameField->setError($addBlockPage->errForColName("name"));
$nameField->setGuideText("Choose a name for this " . block_term_singular . " (e.g., \"July Week 1\", \"Mini Session Aleph\", etc.)");
$addBlockPage->addFormItem($nameField);

$visibleToCampersChoiceBox = new FormItemCheckBox("Visible to Campers", false, "visible_to_campers", 1);
$visibleToCampersChoiceBox->setGuideText("Check this box to make this " . block_term_singular . " visible and active when campers choose their next round of " . chug_term_plural . ".");
$visibleToCampersChoiceBox->setInputValue(true);
$addBlockPage->addFormItem($visibleToCampersChoiceBox);

$sessionChooserField = new FormItemInstanceChooser("Sessions", false, "session_ids", 2);
$sessionChooserField->setId2Name($addBlockPage->instanceId2Name);
$sessionChooserField->setActiveIdHash($addBlockPage->instanceActiveIdHash);
$sessionChooserField->setGuideText("Choose each session that contains this time " . block_term_singular . " (you can do this later if you are not sure now).");
$addBlockPage->addFormItem($sessionChooserField);

$edahChooser = new FormItemInstanceChooser("Edot", false, "edot_for_block", 3);
$edahChooser->setId2Name($addBlockPage->activeEdotFilterId2Name);
$edahChooser->setActiveIdHash($addBlockPage->activeEdotHash);
$edahChooser->setGuideText("Choose the edot who will participate in this time " . block_term_singular . " (you can do this later if you are not sure now)");
$addBlockPage->addFormItem($edahChooser);

$addBlockPage->renderForm();
