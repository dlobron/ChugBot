<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    include_once 'dbConn.php';
    bounceToLogin();
    
    $addChugPage = new AddPage("Add Chug",
                               "Please enter chug information here",
                               "chugim", "chug_id");
    $addChugPage->setAddEditChugPage();
    $addChugPage->addColumn("name");
    $addChugPage->addColumn("group_id");
    $addChugPage->addColumn("min_size", FALSE, TRUE, MIN_SIZE_NUM);
    $addChugPage->addColumn("max_size", FALSE, TRUE, MAX_SIZE_NUM);
    $addChugPage->addColumn("description", FALSE);
    $addChugPage->addInstanceTable("chug_instances");
    $addChugPage->fillInstanceId2Name("block_id", "blocks");
    $addChugPage->setActiveEdotFilterBy("chug");
    
    $addChugPage->handleSubmit();
    
    $nameField = new FormItemSingleTextField("Chug Name", TRUE, "name", 0);
    $nameField->setInputType("text");
    $nameField->setInputClass("element text medium");
    $nameField->setInputMaxLength(255);
    $nameField->setInputValue($addChugPage->columnValue("name"));
    $nameField->setPlaceHolder("Chug name");
    $nameField->setError($addChugPage->errForColName("name"));
    $nameField->setGuideText("Choose a name for this chug");
    $addChugPage->addFormItem($nameField);
    
    $groupIdVal = $addChugPage->columnValue("group_id"); // May be NULL.
    $groupDropDown = new FormItemDropDown("Group", TRUE, "group_id", 1);
    $groupDropDown->setGuideText("Please assign this chug to a group");
    $groupDropDown->setError($addChugPage->errForColName("group_id"));
    $groupDropDown->setInputClass("element select medium");
    $groupDropDown->setInputSingular("group");
    $groupDropDown->setColVal($groupIdVal);
    $groupDropDown->fillDropDownId2Name($addChugPage->dbErr,
                                        "group_id", "groups");
    $addChugPage->addFormItem($groupDropDown);
    
    $sessionChooserField = new FormItemInstanceChooser("Active Blocks", FALSE, "block_ids", 2);
    $sessionChooserField->setId2Name($addChugPage->instanceId2Name);
    $sessionChooserField->setActiveIdHash($addChugPage->instanceActiveIdHash);
    $sessionChooserField->setGuideText("Check each time block in which this chug is active (you can do this later if you are not sure).");
    $addChugPage->addFormItem($sessionChooserField);
    
    $edahChooser = new FormItemInstanceChooser("Edot", FALSE, "edot_for_chug", 3);
    $edahChooser->setId2Name($addChugPage->activeEdotFilterId2Name);
    $edahChooser->setActiveIdHash($addChugPage->activeEdotHash);
    $edahChooser->setGuideText("Choose the edot who may participate in this chug (you can do this later if you are not sure now)");
    $addChugPage->addFormItem($edahChooser);
    
    $minField = new FormItemSingleTextField("Minimum participants", FALSE, "min_size", 4);
    $minField->setInputClass("element text medium");
    $minField->setInputType("text");
    $minField->setInputMaxLength(4);
    $minField->setPlaceHolder("Min participants");
    $minField->setInputValue($addChugPage->columnValue("min_size"));
    $minField->setGuideText("Enter the minimum number of campers needed for this chug to take place (default is no minimum)");
    $addChugPage->addFormItem($minField);

    $maxField = new FormItemSingleTextField("Maximum participants", FALSE, "max_size", 5);
    $maxField->setInputClass("element text medium");
    $maxField->setInputType("text");
    $maxField->setInputMaxLength(4);
    $maxField->setPlaceHolder("Max participants");
    $maxField->setInputValue($addChugPage->columnValue("max_size"));
    $maxField->setGuideText("Enter the maximum number of campers allowed in this chug (default = no limit)");
    $addChugPage->addFormItem($maxField);
    
    $commentsField = new FormItemTextArea("Description", FALSE, "description", 6);
    $commentsField->setInputClass("element textarea medium");
    $commentsField->setInputValue($addChugPage->columnValue("description"));
    $commentsField->setPlaceHolder("Chug description");
    $commentsField->setGuideText("Enter an optional description of this activity.");
    $addChugPage->addFormItem($commentsField);
    
    // Let the user choose chugim to dedup.
    $dedupDropDown = new FormItemDropDown("De-duplication list", FALSE, "dedup", 7);
    $dedupDropDown->setGuideText("Select chugim that should not be assigned to the same camper together with this one. As you select, each de-duplicated chug will appear in a list above the drop-down.");
    $dedupDropDown->setInputSingular("chug");
    $dedupDropDown->setDefaultMsg("Choose Chug(im)");
    $dedupDropDown->setInputClass("element select medium");
    $db = new DbConn();
    $err = "";
    $result = $db->runQueryDirectly("SELECT DISTINCT name FROM chugim", $err);
    $chugName2Name = array(); // Mimic id-to-name, but use name as ID in this case.
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $chugName2Name[$row[0]] = $row[0];
    }
    $dedupDropDown->setId2Name($chugName2Name);
    $dedupDropDown->setDisplayListName("dedup_chugim");
    $addChugPage->addFormItem($dedupDropDown);
    
    $addChugPage->renderForm();

?>
    
    
    

    
