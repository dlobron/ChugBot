
/* ************************************************************************************
* ******************* JavaScript Functions for Schedule Design ************************
* ************************************************************************************/


// Updates chug-group related options as edah is changed - does 3 things:
// 1. SQL query for which groups and blocks are allowed for the edah
// 2. Set the optional dropdowns to override default perek assignments
// 3. Create shortcut buttons to include parameters in schedule
function setAdvanced(block_term_singular) {
    // 1: SQL queries -- get blocks, chug group
    var values = {};
    values["get_legal_id_to_name"] = 1;
    var parentField = document.getElementById("edah_list");
    var curSelectedEdahIds = [];
    curSelectedEdahIds.push(parentField.value);
    if (curSelectedEdahIds[0] == '') {
        document.getElementById("advanced").style.display = 'none';
        document.getElementById("optional").style.display = 'none';
        document.getElementById("shortcut-buttons").style.display = 'none';
        return;
    }
    document.getElementById("advanced").style.display = '';
    document.getElementById("optional").style.display = '';
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
        console.log(block_term_singular);
        var blockBase = "<option value=\"\">-- Override " + block_term_singular + " Assignments --</option>";
        for (let i = 0; i < blockIds.length; i++) {
            blockBase += "<option value=\""+blockIds[i]+"\">"+blockNames[i]+"</option>";
        }
        // step 2: create each individual entry in the list
        var html = "<ul>";
        for (let i = 0; i < groupIds.length; i++) {  
            html += "<li><label class=\"description\" for=\"group"+groupIds[i]+"\" id=\"group"+groupIds[i]+"_desc\">"+groupNames[i]+"</label>";
            html += "<select class=\"form-select\" id=\"group"+groupIds[i]+"\" name="+i+">";
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
            html += "<button type=\"button\" class=\"btn btn-outline-secondary\" style=\"white-space: normal;\" "
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

// load a saved schedule from the menu
function loadSchedule() {
    // the picklist and surrounding div have the same id so that they can be hidden together when
    // no edah is scheduled, below line gets both of them and just returns the object of the picklist
    var schedule_picker = document.querySelectorAll("[id='schedule_picklist']")[1];
    // ensure it's an actual option
    var sched_id = schedule_picker.value;
    if (sched_id == "") { 
        document.getElementById('delete-sched-btn').hidden = true;
        return;
    }
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
        document.getElementById('delete-sched-btn').hidden = false;
    });
}

// Verify an edah is selected when saving a schedule
document.getElementById("saveForm").onsubmit = function() {
    var checkboxes = document.getElementsByName("edah_ids[]");
    var isChecked = false;
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            isChecked = true;
            break;
        }
    }
    if (!isChecked) {
        // Display Bootstrap alert
        var alertDiv = document.createElement("div");
        alertDiv.classList.add("alert", "alert-danger");
        alertDiv.innerHTML = "Please select at least one edah.";
        document.getElementById("saveForm").querySelector(".modal-body").prepend(alertDiv);
        return false; // Prevent form submission
    }
}



function saveSchedulePopup() {
    // insert schedule into popup
    var editor = tinymce.get('schedule-textarea');
    if(editor) {
        document.getElementById('schedule-preview').innerHTML = editor.getContent(); // make it look pretty
        document.getElementById('save-schedule-value').innerHTML = editor.getContent(); // this is actually submitted
    }
    // insert "add/update" buttons if necessary
    // also updates field with current schedule id, if applicable (for saving)
    var prevTemplate = document.getElementsByName("schedule");
    if(prevTemplate.length > 0) {
        if(prevTemplate[0].value != '') {
            document.getElementById("hidable-save-schedule").style.display = '';
            document.getElementById("sched-id-save").setAttribute('value', prevTemplate[0].value);
        }
        else {
            document.getElementById("hidable-save-schedule").style.display = 'none';
            document.getElementById("sched-id-save").setAttribute('value', '');
        }
    }
    else {
        document.getElementById("hidable-save-schedule").style.display = 'none';
        document.getElementById("sched-id-save").setAttribute('value', '');
    }
}

function deleteSchedulePopup() {
    // insert schedule into popup
    var editor = tinymce.get('schedule-textarea');
    if(editor) {
        document.getElementById('delete-schedule-preview').innerHTML = editor.getContent(); // make it look pretty
    }
    var e = document.querySelectorAll("[id='schedule_picklist']")[1];;
    var text = e.options[e.selectedIndex].text;
    document.getElementById('delete-schedule-name').value = text;
    var prevTemplate = document.getElementsByName("schedule");
    document.getElementById("sched-id-del").setAttribute('value', prevTemplate[0].value);
}

// submit form by setting "save new" to 1 when enter button is pressed on 'save schedule' popup
var input = document.getElementById("save-schedule-name");
input.addEventListener("keypress", function(event) {
if (event.key === "Enter") {
    event.preventDefault();
    document.getElementById("new-save-btn").click();
}
});