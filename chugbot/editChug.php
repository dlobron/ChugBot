<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
bounceToLogin();

$editChugPage = new EditPage("Edit Chug",
    "Please edit chug below as needed",
    "chugim", "chug_id");
$editChugPage->addColumn("name");
$editChugPage->addColumn("group_id", true, true);
$editChugPage->addColumn("min_size", false, true, MIN_SIZE_NUM);
$editChugPage->addColumn("max_size", false, true, MAX_SIZE_NUM);
$editChugPage->addColumn("description", false);
$editChugPage->addInstanceTable("chug_instances");
$editChugPage->fillInstanceId2Name("block_id", "blocks");
$editChugPage->setActiveEdotFilterBy("chug");
$editChugPage->setAddEditChugPage();

$editChugPage->handleSubmit();

$nameField = new FormItemSingleTextField("Chug Name", true, "name", 0);
$nameField->setInputType("text");
$nameField->setInputClass("element text medium");
$nameField->setInputMaxLength(255);
$nameField->setInputValue($editChugPage->columnValue("name"));
$nameField->setPlaceHolder("Chug name");
$nameField->setError($editChugPage->errForColName("name"));
$nameField->setGuideText("Choose a name for this chug");
$editChugPage->addFormItem($nameField);

$groupIdVal = $editChugPage->columnValue("group_id"); // May be NULL.
$groupDropDown = new FormItemDropDown("Group", true, "group_id", 1);
$groupDropDown->setGuideText("Please assign this chug to a group");
$groupDropDown->setError($editChugPage->errForColName("group_id"));
$groupDropDown->setInputClass("element select medium");
$groupDropDown->setInputSingular("group");
$groupDropDown->setColVal($groupIdVal);
$groupDropDown->fillDropDownId2Name($editChugPage->dbErr,
    "group_id", "chug_groups");
$editChugPage->addFormItem($groupDropDown);

$sessionChooserField = new FormItemInstanceChooser("Active Blocks", false, "block_ids", 2);
$sessionChooserField->setId2Name($editChugPage->instanceId2Name);
$sessionChooserField->setActiveIdHash($editChugPage->instanceActiveIdHash);
$sessionChooserField->setGuideText("Check each time block in which this chug is active (you can do this later if you are not sure).");
$editChugPage->addFormItem($sessionChooserField);

$edahChooser = new FormItemInstanceChooser("Edot", false, "edot_for_chug", 3);
$edahChooser->setId2Name($editChugPage->activeEdotFilterId2Name);
$edahChooser->setActiveIdHash($editChugPage->activeEdotHash);
$edahChooser->setGuideText("Choose the edot who may participate in this chug (you can do this later if you are not sure now)");
$editChugPage->addFormItem($edahChooser);

$minField = new FormItemSingleTextField("Minimum participants", false, "min_size", 4);
$minField->setInputClass("element text medium");
$minField->setInputType("text");
$minField->setInputMaxLength(4);
$minField->setPlaceHolder("Min participants");
$minField->setInputValue($editChugPage->columnValue("min_size"));
$minField->setGuideText("Enter the minimum number of campers needed for this chug to take place (default = no minimum)");
$editChugPage->addFormItem($minField);

$maxField = new FormItemSingleTextField("Maximum participants", false, "max_size", 5);
$maxField->setInputClass("element text medium");
$maxField->setInputType("text");
$maxField->setInputMaxLength(4);
$maxField->setPlaceHolder("Max participants");
$maxField->setInputValue($editChugPage->columnValue("max_size"));
$maxField->setGuideText("Enter the maximum number of campers allowed in this chug (default = no limit)");
$editChugPage->addFormItem($maxField);

$commentsField = new FormItemTextArea("Description", false, "description", 6);
$commentsField->setInputClass("element textarea medium");
$commentsField->setInputValue($editChugPage->columnValue("description"));
$commentsField->setPlaceHolder("Chug description");
$commentsField->setGuideText("Enter an optional description of this activity.");
$editChugPage->addFormItem($commentsField);

$dedupDropDown = new FormItemDropDown("De-duplication list", false, "dedup", 7);
$dedupDropDown->setGuideText("Select chugim that should not be assigned to the same camper together with this one. As you select, each de-duplicated chug will appear in a list above the drop-down. Click the red X next to a chug in the list to remove it.");
$dedupDropDown->setInputSingular("chug");
$dedupDropDown->setDefaultMsg("Choose Chug(im)");
$dedupDropDown->setInputClass("element select medium");
$db = new DbConn();
$err = "";
$result = $db->runQueryDirectly("SELECT c.name, c.chug_id, g.name FROM chugim c, chug_groups g WHERE c.group_id = g.group_id", $err);
$chugId2Name = array();
while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
    $chugId2Name[$row[1]] = $row[0] . " (" . $row[2] . ")";
}
$dedupDropDown->setId2Name($chugId2Name);
$dedupDropDown->setDisplayListName("dedup_chugim");
$dedupDropDown->setDisplayListSelectedIds($editChugPage->displayListSelectedIds());
$editChugPage->addFormItem($dedupDropDown);

$editChugPage->renderForm();
