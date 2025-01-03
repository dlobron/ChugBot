<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
bounceToLogin();
checkLogout();

$editBunkPage = new EditPage("Edit Bunk",
    "Please edit information for this bunk",
    "bunks", "bunk_id");
$editBunkPage->addColumn("name");
$editBunkPage->addInstanceTable("bunk_instances");
$secondParagraph = "A bunk has a name or number (required), and can optionally be associated with one or more " . ucfirst(edah_term_plural) . ".";
$editBunkPage->addSecondParagraph($secondParagraph);
$editBunkPage->fillInstanceId2Name("edah_id", "edot");

$editBunkPage->handleSubmit();

$nameField = new FormItemSingleTextField("Bunk Name", true, "name", 0);
$nameField->setInputMaxLength(255);
$nameField->setInputValue($editBunkPage->columnValue("name"));
$nameField->setError($editBunkPage->errForColName("name"));
$nameField->setGuideText("Choose a name or number for this bunk (\"1\", \"Tikvah Village\", etc.)");
$editBunkPage->addFormItem($nameField);

$edahChooserField = new FormItemInstanceChooser(ucfirst(edah_term_plural), false, "edah_ids", 1);
$edahChooserField->setId2Name($editBunkPage->instanceId2Name);
$edahChooserField->setActiveIdHash($editBunkPage->instanceActiveIdHash);
$edahChooserField->setGuideText("Associate this bunk with one or more " . edah_term_plural . " (optional).");
$editBunkPage->addFormItem($edahChooserField);

$editBunkPage->renderForm();
