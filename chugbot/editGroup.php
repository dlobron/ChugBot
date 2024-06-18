<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
bounceToLogin();
checkLogout();

$editGroupPage = new EditPage("Edit Group", "Please update group information as needed",
    "chug_groups", "group_id");
$editGroupPage->addColumn("name");
$editGroupPage->setActiveEdotFilterBy("group");
$secondParagraph = <<<EOM
A group is a set of activities that take place at a certain time.  Each group is associated with one
or more Edot, which you can update here at any time.
EOM;
$editGroupPage->addSecondParagraph($secondParagraph);
$editGroupPage->setActiveEdotFilterBy("group");

$editGroupPage->handleSubmit();

$nameField = new FormItemSingleTextField("Group Name", true, "name", 0);
$nameField->setInputType("text");
$nameField->setInputClass("element text medium");
$nameField->setInputMaxLength(255);
$nameField->setInputValue($editGroupPage->columnValue("name"));
$nameField->setError($editGroupPage->errForColName("name"));
$nameField->setGuideText("Choose a group name (e.g., aleph or bet)");
$editGroupPage->addFormItem($nameField);

$edahChooser = new FormItemInstanceChooser("Edot", false, "edot_for_group", 1);
$edahChooser->setId2Name($editGroupPage->activeEdotFilterId2Name);
$edahChooser->setActiveIdHash($editGroupPage->activeEdotHash);
$edahChooser->setGuideText("Choose the edot for this group (you can do this later if you are not sure now)");
$editGroupPage->addFormItem($edahChooser);

$editGroupPage->renderForm();
