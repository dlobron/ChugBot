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
?>


<script src="/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<script>
      tinymce.init({
        selector: '#schedule-textarea'
      });
</script>

<div class="well well-white container">
    <h1>Schedule Builder</h1>
    <div class="page-header"><h2>Generate Printable Schedules</h2>
    <p>In the form below, select an edah and time <?php echo block_term_singular?> to begin designing printable schedules for each camper.
    Completing all of the below steps will allow each camper to have a custom printout with all of their <?php echo chug_term_singular ?>
    assignments for a certain time <?php echo block_term_singular?>. Tzevet only need to design one schedule for an edah, and adding placeholders
    will automatically populate a camper's assignments for every perek.</p>
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
    <form id="schedule_designer_form" class="well" method="POST" action="printSchedules.php" target="_blank"><ul>
        <li>
            <label class="description" for="edah"><span style="color:red;">*</span>Edah</label>
            <div id="edah_checkbox">
                <select class="form-control" id="edah_list" name="edah" required onchange="setAdvanced(); fillConstraintsPickList()">
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
                <select class="form-control" id="block" name="block" required>
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
        <li>
            <button class="btn btn-primary" type="submit">Generate Schedules!</button>
            <!-- This button is intentionally commented out. It will later be used to save schedules to be reused
            <button class="btn btn-info" type="submit">Generate Schedules!</button>-->
            <br><br>
            <button class="btn btn-info" onClick="previewSchedule()">Preview</button>
        </li>
        <br><br>
        <li>
            <div id="optional">
                <fieldset><legend>OPTIONAL: Advanced Block Override (by <?php echo chug_term_singular; ?>)</legend>
                <div id="advanced"></div>
            </div>
            </fieldset>
        </li>
    </ul></form>

</div>

<?php
    echo footerText();
?>


<script>
    /* ************************************************************************************
     * **************************** JavaScript Functions **********************************
     * ************************************************************************************/


    // Updates chug-group related options as edah is changed - does 3 things:
    // 1. SQL query for which groups and blocks are allowed for the edah
    // 2. Set the optional dropdowns to override default perek assignments
    // 3. Create shortcut buttons to include parameters in schedule
    function setAdvanced() {
        // 1: SQL queries -- get blocks, chug group
        var values = {};
        values["get_legal_id_to_name"] = 1;
        var parentField = document.getElementById("edah_list");
        var curSelectedEdahIds = [];
        curSelectedEdahIds.push(parentField.value);
        if (curSelectedEdahIds[0] == '') {
            document.getElementById("advanced").style.display = 'none';
            document.getElementById("shortcut-buttons").style.display = 'none';
            return;
        }
        document.getElementById("advanced").style.display = '';
        // Two SQL queries - one to get chug group names, one to get blocks
        // first, get a list of all applicable chug groups and ids:
        var sql = "SELECT e.group_id group_id, g.name group_name FROM edot_for_group e, chug_groups g WHERE e.edah_id IN (";
        var ct = 0;
        for (var i = 0; i < curSelectedEdahIds.length; i++) {
            if (ct++ > 0) {
                sql += ",";
            }
            sql += "?";
        }
        sql += ") AND e.group_id = g.group_id GROUP BY e.group_id HAVING COUNT(e.edah_id) = " + ct;
        values["sql"] = sql;
        values["instance_ids"] = curSelectedEdahIds;
        var groupNames = [];
        var groupIds = [];
        var ajax1 = $.ajax({
            url: 'ajax.php',
            type: 'post',
            data: values,
            success: function(data) {
                $.each(data, function(itemId, itemName) {
                    groupNames.push(itemName);
                    groupIds.push(itemId)
                });
            },
            error: function(xhr, desc, err) {
            console.log(xhr);
            console.log("Details: " + desc + " Error:" + err);
            }
        });
        // second, get list of all block names and ids
        sql = "SELECT e.block_id block_id, g.name block_name FROM edot_for_block e, blocks g WHERE e.edah_id IN (";
        var ct = 0;
        for (var i = 0; i < curSelectedEdahIds.length; i++) {
            if (ct++ > 0) {
                sql += ",";
            }
            sql += "?";
        }
        sql += ") AND e.block_id = g.block_id GROUP BY e.block_id HAVING COUNT(e.edah_id) = " + ct;
        values["sql"] = sql;
        values["instance_ids"] = curSelectedEdahIds;
        const blockNames = [];
        const blockIds = [];
        var ajax2 = $.ajax({
            url: 'ajax.php',
            type: 'post',
            data: values,
            success: function(data) {
                $.each(data, function(itemId, itemName) {
                    blockNames.push(itemName);
                    blockIds.push(itemId)
                });
            },
            error: function(xhr, desc, err) {
            console.log(xhr);
            console.log("Details: " + desc + " Error:" + err);
            }
        });

        // Wait for both Ajax calls to finish
        $.when(ajax1, ajax2).done(function() {
            // 2: Block override buttons

            // finally, build a picklist for each group with the blocks as options
            // step 1: create generic block options
            var blockBase = "<option value=\"\">-- Override Block Assignments --</option>";
            for (let i = 0; i < blockIds.length; i++) {
                blockBase += "<option value=\""+blockIds[i]+"\">"+blockNames[i]+"</option>";
            }
            // step 2: create each individual entry in the list
            var html = "<ul>";
            for (let i = 0; i < groupIds.length; i++) {  
                html += "<li><label class=\"description\" for=\"group"+groupIds[i]+"\" id=\"group"+groupIds[i]+"_desc\">"+groupNames[i]+"</label>";
                html += "<select class=\"form-control\" id=\"group"+groupIds[i]+"\" name="+i+">";
                html += blockBase;
                html += "</select></li>";
            }
            html += "</ul>"
            var ourPickList = $("#advanced");
            $(ourPickList).html(html);


            // 3: Shortcut buttons
            html = "<div class=\"btn-group-vertical\" role=\"group\">";
            // make array with all options:
            var shortcutsRequired = ["Name", "Bunk", "Edah", "Rosh", "Rosh Phone Number"];
            shortcutsRequired = shortcutsRequired.concat(groupNames);
            // write html for each button:
            for (let i = 0; i < shortcutsRequired.length; i++) {
                html += "<button type=\"button\" class=\"btn btn-default\" style=\"white-space: normal;\" "
                html += "onClick='insertTextOnClick(\""+ shortcutsRequired[i] + "\")'>" + shortcutsRequired[i] + "</button>"
            }
            html += "</div>";
            var shortcutButtons = $("#shortcut-buttons");
            $(shortcutButtons).html(html);
        });
    }

    function insertTextOnClick(toInsert) {
        var editor = tinymce.get('schedule-textarea');
        if (editor) {
            // Insert text at the current cursor position
            editor.insertContent('{{' + toInsert + '}}');
        }
    }

    // Opens a new tab with the contents of the schedule builder, then automatically prepares the print dialog so a
    // user can see a print preview of the schedule. Once closing that print dialog, the tab automatically closes, too
    function previewSchedule() {
        var html = "<head>" + document.getElementsByTagName('head')[0].innerHTML + "</head>";
        html += "<body onload=\"PrintAndClose()\"><div class=\"container schedule\">";
        html += tinymce.get('schedule-textarea').getContent();
        html += "</div></body>";
        // Script so it prints on load:
        html += "<script> function PrintAndClose() { window.focus(); window.print(); window.onfocus=function(){ window.close();} }<\/script>";
        // Open new tab:
        var newWindow = window.open("", "_blank", "popup=yes");
        newWindow.document.write(html);
        newWindow.document.close();
    }


    function loadSchedule() {
        // the picklist and surrounding div have the same id so that they can be hidden together when
        // no edah is scheduled, below line gets both of them and just returns the object of the picklist
        var schedule_picker = document.querySelectorAll("[id='schedule_picklist']")[1];
        // ensure it's an actual option
        var sched_id = schedule_picker.value;
        if (sched_id == "") { return; }
        // create schedule query
        var sql = "SELECT schedule_id, schedule FROM schedules WHERE schedule_id IN (?)";

        var values = {};
        values["get_legal_id_to_name"] = 1;
        values["sql"] = sql;
        values["instance_ids"] = [sched_id]
        var result = "";

        var ajax = $.ajax({
            url: 'ajax.php',
            type: 'post',
            data: values,
            success: function(data) {
                result = data[sched_id];
            },
            error: function(xhr, desc, err) {
            console.log(xhr);
            console.log("Details: " + desc + " Error:" + err);
            }
        });

        // Wait for Ajax call to finish
        $.when(ajax).done(function() {
            var editor = tinymce.get('schedule-textarea');
            editor.setContent(result);
        });
    }

</script>
</body>
</html>