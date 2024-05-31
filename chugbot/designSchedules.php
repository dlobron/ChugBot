<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';
    bounceToLogin();
    setup_camp_specific_terminology_constants();

    $dbErr = "";
    $sessionId2Name = array();
    $blockId2Name = array();
    $groupId2Name = array();
    $edahId2Name = array();
    $chugId2Name = array();
    $bunkId2Name = array();

    fillId2Name(null, $chugId2Name, $dbErr, "chug_id", "chugim", "group_id", "chug_groups");
    fillId2Name(null, $sessionId2Name, $dbErr, "session_id", "sessions");
    fillId2Name(null, $blockId2Name, $dbErr, "block_id", "blocks");
    fillId2Name(null, $groupId2Name, $dbErr, "group_id", "chug_groups");
    fillId2Name(null, $edahId2Name, $dbErr, "edah_id", "edot");
    fillId2Name(null, $bunkId2Name, $dbErr, "bunk_id", "bunks");

    echo headerText("Design Schedules");
    $inserted = false;
    $deleted = false;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // determine if deleting or saving
        $delete = test_post_input("delete");
        if(!is_null($delete)) { // delete
            $deleted = true;
            $sched_id = test_post_input("schedule-id");
            $db = new dbConn();
            $db->addWhereColumn("schedule_id", $sched_id, "i");
            $queryOk = $db->deleteFromTable("schedules", $dbErr,);
            if (!$queryOk) {
                $deleted = false;
                error_log("Insert failed: $dbErr");
                return;
            }
        }
        else { // save
            $inserted = true;
            // get values from request
            $edah_ids = test_post_input("edah_ids");
            $sched_name = test_post_input("save-schedule-name");
            $schedule = html_entity_decode(test_post_input("schedule-save"));
            $sched_id = test_post_input("schedule-id");
            $save_new_int = test_post_input("save-new");
            $replace = false;
            if($save_new_int == 0) {$replace = true;}

            // insert schedule into table
            $db = new dbConn();
            if($replace){ 
                // add current schedule id, if applicable
                $db->addColumn("schedule_id", $sched_id, "i");
            }
            $db->addColumn("name", $sched_name, "s");
            $db->addColumn("schedule", $schedule, "s");
            $queryOk = $db->insertIntoTable("schedules", $dbErr, $replace);
            if (!$queryOk) {
                $inserted = false;
                error_log("Insert failed: $dbErr");
                return;
            }
            $sched_id = $db->insertId();


            // set available for designated edot
            foreach($edah_ids as $edah) {
                $db = new dbConn();
                $db->addColumn("schedule_id", $sched_id, "s");
                $db->addColumn("edah_id", intval(test_input($edah)), "s");
                $queryOk = $db->insertIntoTable("edot_for_schedule", $dbErr);
                if (!$queryOk) {
                    $inserted = false;
                    error_log("Insert failed: $dbErr");
                    return;
                }
            }
            unset($edah);
        }
    }
?>


<script src="/tinymce/tinymce.min.js" referrerpolicy="origin"></script>

<script>
      tinymce.init({
        selector: '#schedule-textarea',
        plugins: 'preview importcss searchreplace directionality visualblocks visualchars fullscreen link table charmap advlist lists help emoticons wordcount',
        menubar: 'file edit view insert format tools table help',
        toolbar: 'undo redo | blocks fontsizeinput | bold italic underline forecolor backcolor | table align | numlist bullist ltr rtl | removeformat | wordcount',
        paste_merge_formats: true,
      });
</script>

<?php if($inserted):?> 
<div class="row justify-content-center">
    <div class="col-6 mt-4">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <h5>Schedule successfully saved!</h5>
            You can now access it after choosing the edah/edot you saved it for.
        </div>
    </div>
</div>
<?php elseif ($deleted):?> 
<div class="row justify-content-center">
    <div class="col-6 mt-4">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <h5>Schedule successfully deleted!</h5>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card card-body mt-3 p-3 mb-3 container">
    <h1>Schedule Builder</h1>
    <div class="page-header"><h2>Generate Printable Schedules</h2>
    <p>In the form below, select an edah and time <?php echo block_term_singular?> to begin designing printable schedules for each camper.
    Completing all of the below steps will allow each camper to have a custom printout with all of their <?php echo chug_term_singular ?>
    assignments for a certain time <?php echo block_term_singular?>. Tzevet only need to design one schedule for an edah, and adding placeholders
    will automatically populate a camper's assignments for every perek.</p>
    <div class="card card-body mb-3 bg-light">
    <h4>Instructions</h4>
    <ol>
        <li>
            <strong>Select</strong> an edah, time <?php echo block_term_singular?>, and (optionally) a pre-saved schedule template from the below form.
        </li>
        <li>
            <strong>Customization!</strong> Using the provided editor, design what you want the camper schedule to look like. However the 
            schedule looks in the editor is it will appear in the printouts (with camper assignments replacing designated placeholders).
            <br>Tips for designing schedules:
                <ul>
                    <li>Use a large font! Size 18 or larger will make it more readable.</li>
                    <li>Once an edah is selected in the dropdown menu, buttons will appear to the right of the editor. Click the button to add
                        a placeholder for the desired field. You can stylize the placeholders, too, just be sure the entire placeholder is stylized
                        the same (the placeholders are structured with 2 curly brackets around the word, like this: <code>{{Name}}</code>).
                    </li>
                    <li>
                        Designing a template in Microsoft Word and copy/pasting it into the textbox seems to generally preserve formatting
                        (text size, tables, colors, etc); it is not always reliable from Google Docs. If creating a design in Word first, it is 
                        recommended to keep it to less than 3/4th of a page in Word, as sizing is adjusted slightly when printing the schedule.
                    </li>
                </ul>
        </li>
        <li>
            <strong>Advanced:</strong> In some situations, campers may have a <?php echo chug_term_singular ?>/perek assignment which lasts multiple time
            <?php echo block_term_plural?>. In that case, you can override the block for a specific <?php echo chug_term_singular ?> assignment
            using the dropdowns beneath the editor. Any campers missing an assignment for a perek with a placeholder tag will have an empty
            spot on their schedule.
        </li>
        <li>
            <strong>Click</strong> the blue "Generate Schedules!" button to see all camper schedules (it automatically opens in a new tab) and print them!
        </li>
    </ol>
    </div>
    </div>
    <form id="schedule_designer_form" class="well" method="POST" action="printSchedules.php" target="_blank"><ul>
        <li>
            <label class="description" for="edah"><span style="color:red;">*</span>Edah</label>
            <div id="edah_checkbox">
                <select class="form-select" id="edah_list" name="edah" required onchange="setAdvanced(); fillConstraintsPickList()">
                    <?php echo genPickList($edahId2Name, array(), "edah"); ?>
                </select>
            </div>
        </li>
        <li> 
            <label class="description" for="schedule" id="schedule_desc">Schedule Template</label>
            <div id="schedule_picklist">
                <?php echo genConstrainedPickListScript("schedule_picklist", "edah", "schedule_desc", "schedule"); ?>
            </div>
        </li>
        <li>
            <label class="description" for="block"><span style="color:red;">*</span><?php echo ucfirst(block_term_singular) ?></label>
            <div>
                <select class="form-select" id="block" name="block" required>
                    <?php echo genPickList($blockId2Name, array(), "block"); ?>
                </select>
            </div>
        </li>
        <li style="max-width: 100%;">
            <label class="description" for="schedule_build"><span style="color:red;">*</span>Schedule</label>
            <div id="schedule_build" style="width:80%; display: inline-block; float:left;">
                <textarea id="schedule-textarea" name="schedule-template" style="">Design the general camper schedule here!</textarea>
            </div>
            <div id="shortcut-buttons" style="display: inline-block; max-width:15%; float:right;">
                    <!-- Originally empty, added with JS when edah is set -->
            </div> 
        </li>
        <li style="max-width:79.5%;">
            <div class="position-relative" style="height:100px">
                <div class="position-absolute top-0 start-0"><button class="btn btn-primary" type="submit">Generate Schedules!</button></div>
                <div class="position-absolute top-0 end-0"><button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#saveModal" onClick="saveSchedulePopup()">Save Template</button></div>
                <div class="position-absolute bottom-0 start-0"><button class="btn btn-info" onClick="previewSchedule()">Preview</button></div>
                <div class="position-absolute bottom-0 end-0"><button type="button" id="delete-sched-btn" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" onClick="deleteSchedulePopup()" hidden>Delete Template</button></div>
            </div>
        </li>
        <br><br>
        <li>
            <div id="optional" style="display:none;">
                <fieldset><legend>OPTIONAL: Advanced Block Override (by <?php echo chug_term_singular; ?>)</legend>
                <div id="advanced"></div>
            </div>
            </fieldset>
        </li>
    </ul></form>

</div>

<!-- Save template popup/modal: -->
<div class="modal fade" id="saveModal" tabindex="-1" aria-labelledby="saveModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="saveModalLabel">Save Schedule Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form id="saveForm" action="" method="POST">
        <div class="modal-body">
            Select the edah/edot this schedule should be saved for, enter a name for the schedule, and save this template to reuse it again!
            <div id="save-form-items" class="card card-body mt-2">
                <label class="description" for="edah"><span style="color:red;">*</span> Edah/Edot</label>
                <?php echo genCheckBox($edahId2Name, array(), "edah_ids"); ?>
                <label class="description mt-3" for="save-schedule-name"><span style="color:red;">*</span> Schedule Name</label>
                <input class="form-control" id="save-schedule-name" type="text" name="save-schedule-name" required>
                <label class="description mt-3" for="schedule-preview">Schedule Preview:</label>
                <div id="schedule-preview" class="card card-body bg-light border-secondary"></div>
                <textarea id="save-schedule-value" name="schedule-save" form="saveForm" hidden></textarea>
                <input type="number" name="schedule-id" id="sched-id-save" hidden></input>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button title="Save updated template" id="hidable-save-schedule" class="btn btn-primary" name="save-new" value="0" type="submit">Update Saved Schedule</button>
            <button title="Save new template" id="new-save-btn" class="btn btn-primary" name="save-new" value="1" type="submit">Save New Schedule</button>
        </div>
    </form>
</div>
</div>
</div>

<!-- Delete template popup/modal: -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Delete Schedule Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <strong>Caution:</strong> once you delete a template, it cannot be recovered (consider temporarily renaming a template to indicate it is inactive instead of deleting if there is a chance you may need it again)
        <div class="card card-body bg-light mt-3 mb-3">
            <label class="description" for="delete-schedule-name">Schedule Name</label>
            <input class="form-control" id="delete-schedule-name" type="text" name="save-schedule-name" disabled>
            <label class="description mt-3" for="delete-schedule-preview">Schedule Preview:</label>
            <div id="delete-schedule-preview" class="card card-body bg-light border-secondary"></div>
        </div>
        Are you sure you want to delete this template? Pressing the red button will immediately permanently delete the template.
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="saveForm" action="" method="POST">
            <input type="number" name="schedule-id" id="sched-id-del" hidden></input>
            <button title="Delete schedule" class="btn btn-danger" name="delete" value="1" type="submit">Yes, delete this schedule</button>
        </form>
    </div>
</div>
</div>
</div>

<?php
    echo footerText();
?>
<script src="/meta/design.js"></script>
</body>
</html>