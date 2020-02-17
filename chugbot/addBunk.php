<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
bounceToLogin();

$addBunkPage = new AddPage("Add Bunk",
    "Please enter information for this bunk",
    "bunks", "bunk_id");
$addBunkPage->addColumn("name");
$addBunkPage->addInstanceTable("bunk_instances");
$secondParagraph = "A bunk has a name or number (required), and can optionally be associated with one or more Edot.";
$addBunkPage->addSecondParagraph($secondParagraph);
$addBunkPage->fillInstanceId2Name("edah_id", "edot");

$addBunkPage->handleSubmit();

$nameField = new FormItemSingleTextField("Bunk Name", true, "name", 0);
$nameField->setInputMaxLength(255);
$nameField->setInputValue($addBunkPage->columnValue("name"));
$nameField->setError($addBunkPage->errForColName("name"));
$nameField->setGuideText("Choose a name or number for this bunk (\"1\", \"Tikvah Village\", etc.)");
$addBunkPage->addFormItem($nameField);

$edahChooserField = new FormItemInstanceChooser("Edot", false, "edah_ids", 1);
$edahChooserField->setId2Name($addBunkPage->instanceId2Name);
$edahChooserField->setActiveIdHash($addBunkPage->instanceActiveIdHash);
$edahChooserField->setGuideText("Associate this bunk with one or more Edot (optional).");
$addBunkPage->addFormItem($edahChooserField);

$addBunkPage->renderForm();
