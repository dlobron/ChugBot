<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
bounceToLogin();
checkLogout();

$editEdahPage = new EditPage("Edit Edah",
    "Please update edah information as needed",
    "edot", "edah_id");
$editEdahPage->addColumn("name");
$editEdahPage->addColumn("rosh_name", false);
$editEdahPage->addColumn("rosh_phone", false);
$editEdahPage->addColumn("comments", false);
$editEdahPage->addColumn("sort_order", false, true);
$editEdahPage->handleSubmit();

$nameField = new FormItemSingleTextField("Edah Name", true, "name", 0);
$nameField->setInputClass("element text medium");
$nameField->setInputType("text");
$nameField->setInputMaxLength(255);
$nameField->setInputValue($editEdahPage->columnValue("name"));
$nameField->setError($editEdahPage->errForColName("name"));
$nameField->setGuideText("Choose your edah name (Kochavim, Ilanot 1, etc.)");
$editEdahPage->addFormItem($nameField);

$roshField = new FormItemSingleTextField("Rosh Edah (head counselor) Name", false, "rosh_name", 1);
$roshField->setInputClass("element text medium");
$roshField->setInputType("text");
$roshField->setInputMaxLength(255);
$roshField->setInputValue($editEdahPage->columnValue("rosh_name"));
$roshField->setGuideText("Enter the head counselor name (optional)");
$editEdahPage->addFormItem($roshField);

$roshPhoneField = new FormItemSingleTextField("Rosh Edah Phone", false, "rosh_phone", 2);
$roshPhoneField->setInputType("text");
$roshPhoneField->setInputClass("element text medium");
$roshPhoneField->setInputMaxLength(255);
$roshPhoneField->setInputValue($editEdahPage->columnValue("rosh_phone"));
$roshPhoneField->setGuideText("Phone number for the head counselor (optional)");
$editEdahPage->addFormItem($roshPhoneField);

$commentsField = new FormItemTextArea("Comments", false, "comments", 3);
$commentsField->setInputClass("element textarea medium");
$commentsField->setInputValue($editEdahPage->columnValue("comments"));
$commentsField->setGuideText("Comments about this Edah (optional)");
$editEdahPage->addFormItem($commentsField);

$sortOrderField = new FormItemSingleTextField("Sort Order", false, "sort_order", 4);
$sortOrderField->setInputType("number");
$sortOrderField->setInputMaxLength(3);
$sortOrderField->setInputValue($editEdahPage->columnValue("sort_order"));
$sortOrderField->setGuideText("Indicate where this edah should appear when all edot are sorted, with lower appearing earlier.  For example, if this is the youngest and that group should be listed first, enter 1.  If this group should appear third, enter 3.  If no choices are made for this box, edot will be listed alphabetically.");
$editEdahPage->addFormItem($sortOrderField);

$editEdahPage->renderForm();
