<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
bounceToLogin();
checkLogout();

$editSessionPage = new EditPage("Edit Session", "Please update session information as needed",
    "sessions", "session_id");
$editSessionPage->addColumn("name");

$editSessionPage->handleSubmit();

$nameField = new FormItemSingleTextField("Session Name", true, "name", 0);
$nameField->setInputType("text");
$nameField->setInputClass("element text medium");
$nameField->setInputMaxLength(255);
$nameField->setInputValue($editSessionPage->columnValue("name"));
$nameField->setError($editSessionPage->errForColName("name"));
$nameField->setGuideText("Choose a session name (e.g., (e.g., \"July\", \"August\", \"Full Summer\")");
$editSessionPage->addFormItem($nameField);

$editSessionPage->renderForm();
