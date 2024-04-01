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

    echo headerTextRTE("Staff Home");
?>

<div class="well well-white container">
    <h1>Schedule Builder</h1>
    <div class="page-header"><h2>Generate Printable Schedules</h2></div>
    <!--<p>To view the leveling page, choose a time <?php echo block_term_singular ?> and <b>1-8</b> edot, and click "Go."</p>
    <p>If there is an existing saved assignment for the selected edah/edot and <?php echo block_term_singular ?>, it will be displayed.  Nothing will be
    changed until you click the Save or Reassign buttons on the leveling page.  If there is no existing assignment, one
    will be created and then displayed.</p>
    <p>If you choose two edot, make sure they share at least some <?php echo chug_term_plural ?>.</p>
    <p>To generate a printable <?php echo chug_term_singular ?> assigment report, click "Report".-->
    <form id="leveling_choice_form" class="well" method="GET" action="printSchedules.php"><ul>
        <li>
            <label class="description" for="edah"><span style="color:red;">*</span>Edah</label>
            <div id="edah_checkbox">
                <select class="form-control" id="edah_list" name="edah" required onchange="setAdvanced(); fillConstraintsPickList()">
                    <?php echo genPickList($edahId2Name, array(), "edah"); ?>
                </select>
            </div>
        </li>
        <li> <!-- Group Selector: update to be saved schedules -->
            <label class="description" for="group" id="group_desc">Group</label>
            <div id="group_picklist">
                <?php echo genConstrainedPickListScript($groupId2Name, "group_ids", "group_picklist", "edah", "group_desc", "group"); ?>
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
        <li style="max-width: 90%;">
            <label class="description" for="schedule_build"><span style="color:red;">*</span>Schedule</label>
            <div id="schedule_build" style="max-width:80%; width:80%; display: inline-block;">
                <textarea id="mytextarea" name="schedule-template">Hello, World!</textarea>
            </div>
            <div style="max-width:20%; display: inline-block;"></div> <!-- Insert shortcut buttons here -->
        </li>
        <li>
            <button class="btn btn-primary" type="submit">Generate Schedules!</button>
            <button class="btn btn-info" type="submit">Generate Schedules!</button>
<!--<input type="submit" name="Submit" id="Submit" value="Submit"
       class="SubmitPrefsButton btn btn-success btn-lg" />-->
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
    // Set the optional dropdowns to override default perek assignments
    function setAdvanced() {
        var values = {};
        values["get_legal_id_to_name"] = 1;
        var parentField = document.getElementById("edah_list");
        console.log(parentField);
        var curSelectedEdahIds = [];
        curSelectedEdahIds.push(parentField.value);
        if (curSelectedEdahIds[0] == '') {
            document.getElementById("advanced").style.display = 'none';
            return;
        }
        document.getElementById("advanced").style.display = '';
        console.log(curSelectedEdahIds);
        // 2 SQL queries - one to get chug group names, one to get blocks
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
        console.log(groupNames);
        console.log(groupIds);
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
            console.log(groupNames);
            console.log(groupIds);
            console.log(blockNames);
            console.log(blockIds);
            // finally, build a picklist for each group with the blocks as options
            // step 1: create generic block options
            console.log(blockIds[0]);
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
            console.log(html);
            var ourPickList = $("#advanced");
            $(ourPickList).html(html);
        });
    }

</script>
</body>
</html>


<?php
// Functions for Above Methods

function blockForChugim() {
    

}
?>