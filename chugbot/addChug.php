<?php
session_start();
include_once 'addEdit.php';
include_once 'formItem.php';
include_once 'dbConn.php';
bounceToLogin();
setup_camp_specific_terminology_constants();

$addChugPage = new AddPage("Add " . ucfirst(chug_term_singular),
    "Please enter " . chug_term_singular . " information here",
    "chugim", "chug_id");
$addChugPage->setAddEditChugPage();
$addChugPage->addColumn("name");
$addChugPage->addColumn("group_id");
$addChugPage->addColumn("min_size", false, true, MIN_SIZE_NUM);
$addChugPage->addColumn("max_size", false, true, MAX_SIZE_NUM);
$addChugPage->addColumn("description", false);
$addChugPage->addInstanceTable("chug_instances");
$addChugPage->fillInstanceId2Name("block_id", "blocks");
$addChugPage->setActiveEdotFilterBy("chug");

$addChugPage->handleSubmit();

$nameField = new FormItemSingleTextField(ucfirst(chug_term_singular). " Name", true, "name", 0);
$nameField->setInputType("text");
$nameField->setInputClass("element text medium");
$nameField->setInputMaxLength(255);
$nameField->setInputValue($addChugPage->columnValue("name"));
$nameField->setPlaceHolder(ucfirst(chug_term_singular). " name");
$nameField->setError($addChugPage->errForColName("name"));
$nameField->setGuideText("Choose a name for this " . chug_term_singular);
$addChugPage->addFormItem($nameField);

$groupIdVal = $addChugPage->columnValue("group_id"); // May be NULL.
$groupDropDown = new FormItemDropDown("Group", true, "group_id", 1);
$groupDropDown->setGuideText("Please assign this " . chug_term_singular . " to a group");
$groupDropDown->setError($addChugPage->errForColName("group_id"));
$groupDropDown->setInputClass("element select medium");
$groupDropDown->setInputSingular("group");
$groupDropDown->setColVal($groupIdVal);
$groupDropDown->fillDropDownId2Name($addChugPage->dbErr,
    "group_id", "chug_groups");
$addChugPage->addFormItem($groupDropDown);

$sessionChooserField = new FormItemInstanceChooser("Active " . ucfirst(block_term_plural), false, "block_ids", 2);
$sessionChooserField->setId2Name($addChugPage->instanceId2Name);
$sessionChooserField->setActiveIdHash($addChugPage->instanceActiveIdHash);
$sessionChooserField->setGuideText("Check each time " . block_term_singular . " in which this " . chug_term_singular . " is active (you can do this later if you are not sure).");
$addChugPage->addFormItem($sessionChooserField);

$edahChooser = new FormItemInstanceChooser("Edot", false, "edot_for_chug", 3);
$edahChooser->setId2Name($addChugPage->activeEdotFilterId2Name);
$edahChooser->setActiveIdHash($addChugPage->activeEdotHash);
$edahChooser->setGuideText("Choose the edot who may participate in this " . chug_term_singular . " (you can do this later if you are not sure now)");
$addChugPage->addFormItem($edahChooser);

$minField = new FormItemSingleTextField("Minimum participants", false, "min_size", 4);
$minField->setInputClass("element text medium");
$minField->setInputType("text");
$minField->setInputMaxLength(4);
$minField->setPlaceHolder("Min participants");
$minField->setInputValue($addChugPage->columnValue("min_size"));
$minField->setGuideText("Enter the minimum number of campers needed for this " . chug_term_singular . " to take place (default is no minimum)");
$addChugPage->addFormItem($minField);

$maxField = new FormItemSingleTextField("Maximum participants", false, "max_size", 5);
$maxField->setInputClass("element text medium");
$maxField->setInputType("text");
$maxField->setInputMaxLength(4);
$maxField->setPlaceHolder("Max participants");
$maxField->setInputValue($addChugPage->columnValue("max_size"));
$maxField->setGuideText("Enter the maximum number of campers allowed in this " . chug_term_singular . " (default = no limit)");
$addChugPage->addFormItem($maxField);

$commentsField = new FormItemTextArea("Description", false, "description", 6);
$commentsField->setInputClass("element textarea medium");
$commentsField->setInputValue($addChugPage->columnValue("description"));
$commentsField->setPlaceHolder(ucfirst(chug_term_singular). " description");
$commentsField->setGuideText("Enter an optional description of this activity.");
$addChugPage->addFormItem($commentsField);

// Let the user choose chugim to dedup.
$dedupDropDown = new FormItemDropDown("De-duplication list", false, "dedup", 7);
$dedupDropDown->setGuideText("Select " . chug_term_plural . " that should not be assigned to the same camper together with this one. As you select, each de-duplicated " . chug_term_singular . " will appear in a list above the drop-down.");
$dedupDropDown->setInputSingular("chug");
$dedupDropDown->setDefaultMsg("Choose " . ucfirst(chug_term_plural));
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
$addChugPage->addFormItem($dedupDropDown);

$addChugPage->renderForm();
