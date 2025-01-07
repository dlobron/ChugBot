<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
bounceToLogin();
checkLogout();

$addGroupPage = new AddPage("Add Group",
    "Please enter your group information",
    "chug_groups", "group_id");
$addGroupPage->addColumn("name");
$addGroupPage->setActiveEdotFilterBy("group");
$secondParagraph = "A group is a set of activities that take place at a certain time.  Each group is associated with one " . 
    "or more " . ucfirst(edah_term_plural) . ", which you can update here at any time.";
$addGroupPage->addSecondParagraph($secondParagraph);
$addGroupPage->handleSubmit();

$nameField = new FormItemSingleTextField("Group Name", true, "name", 0);
$nameField->setInputType("text");
$nameField->setInputClass("element text medium");
$nameField->setInputMaxLength(255);
$nameField->setInputValue($addGroupPage->columnValue("name"));
$nameField->setError($addGroupPage->errForColName("name"));
$nameField->setGuideText("Choose a group name (e.g., aleph or bet)");
$addGroupPage->addFormItem($nameField);

$edahChooser = new FormItemInstanceChooser(ucfirst(edah_term_plural), false, "edot_for_group", 1);
$edahChooser->setId2Name($addGroupPage->activeEdotFilterId2Name);
$edahChooser->setActiveIdHash($addGroupPage->activeEdotHash);
$edahChooser->setGuideText("Choose the " . (edah_term_plural) . " for this group (you can do this later if you are not sure now)");
$addGroupPage->addFormItem($edahChooser);

$addGroupPage->renderForm();
