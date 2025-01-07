<?php
include_once 'constants.php';
include_once 'functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

function getArchiveYears(&$dbErr)
{
    $retVal = array();
    $db = new DbConn();
    $result = $db->runQueryDirectly("SHOW DATABASES", $dbErr);
    if ($result === null) {
        return retVal;
    }
    $years = array();
    $matched = array();
    $archiveDbPattern = "/^" . MYSQL_DB . "(?<dbyear>\d+)$/";
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        // Expected format: MYSQL_DB . YEAR
        if (preg_match($archiveDbPattern, $row[0], $matched)) {
            array_push($years, $matched["dbyear"]);
        }
    }
    // Next, for each configured year, check to see if the archive contains
    // campers.
    foreach ($years as $year) {
        $db = new DbConn($year);
        $result = $db->runQueryDirectly("SHOW TABLES", $dbErr);
        $rc = 0;
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $rc++;
        }
        if ($rc === 0) {
            continue;
        }
        $db = new DbConn($year);
        $db->addSelectColumn("count(*)");
        $result = $db->simpleSelectFromTable("campers", $dbErr);
        if ($result === null) {
            return $retVal;
        }
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {

            if (intval($row[0]) > 0) {
                array_push($retVal, $year);
            }
        }
    }

    return $retVal;
}

function startsWith($haystack, $needle)
{
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle)
{
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function forwardNoHistory($url)
{
    $retVal = '<script type="text/javascript">';
    $retVal .= "window.location.replace(\"$url\")";
    $retVal .= '</script>';
    $retVal .= '<noscript>';
    $retVal .= '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
    $retVal .= '</noscript>';

    return $retVal;
}

function sendMail($address,
    $subject,
    $body,
    $admin_data_row,
    &$error,
    $confirmationMessage = false) {
    if ($admin_data_row === null) {
        return false;
    }
    // An example of the possible parameters for PHPMailer can be found here:
    // https://github.com/Synchro/PHPMailer/blob/master/examples/gmail.phps
    // The settings below are the ones needed by CRNE's ISP, A Small Orange, as
    // of 2016.
    $mail = new PHPMailer();
    // JQuery is unable to parse our JSON if an email error
    // occurs when SMTPDebug is enabled, so I'm not using it for now.
    // $mail->SMTPDebug = 1; // DBG: 1 = errors and messages, 2 = messages only
    $toAddress = null;
    $sendToCamper = $admin_data_row["send_confirm_email"];
    if ($confirmationMessage == false ||
        $sendToCamper == true) {
        // If this is not a confirmation message, or if we're configured to
        // send camper confirmations, use the main address as the TO.
        $mail->addAddress($address);
        $toAddress = $address;
    }
    if ($admin_data_row["admin_email_cc"] != null &&
        (!empty($admin_data_row["admin_email_cc"]))) {
        $ccs = preg_split("/[,:; ]/", $admin_data_row["admin_email_cc"]);
        foreach ($ccs as $cc) {
            if ($toAddress === null) {
                // If we did not use the main address as the TO, use the first
                // CC as the TO.
                $mail->addAddress($cc);
                $toAddress = $cc;
            } else {
                $mail->AddCC($cc);
            }
        }
    }
    if ($toAddress === null) {
        return false; // We need at least a TO address.
    }
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isSMTP();
    // Uncomment the next line to enable debug messages.
    // $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
    $mail->isHTML(true);
    $mail->SMTPAuth = true;
    $mail->Host = EMAIL_HOST;
    $mail->Port = EMAIL_PORT;
    $mail->Username = ADMIN_EMAIL_USERNAME;
    $mail->Password = ADMIN_EMAIL_PASSWORD;
    // GMail's filter rejects our messages when the source is something like foo@gmail.com,
    // so we set the from to the admin email username (normally, the address of our email
    // account on this ISP), and we set the reply-to to whatever the administrator's
    // actual email is.
    $fromName = $admin_data_row["camp_name"];
    if (array_key_exists("admin_email_from_name", $admin_data_row)) {
        $fromName = $admin_data_row["admin_email_from_name"];
    }
    $mail->setFrom("noreply@campramahchug.org", $fromName);
    $mail->addReplyTo($admin_data_row["admin_email"], $admin_data_row["camp_name"]);
    $sentOk = $mail->send();
    if (!$sentOk) {
        error_log("Failed to send email to $toAddress");
        error_log("Mailer error: " . $mail->ErrorInfo);
        $error = $mail->ErrorInfo;
    } else {
        error_log("Mail sent to $toAddress OK");
    }

    return $sentOk;
}

function debugLog($message)
{
    if (DEBUG) {
        error_log("DBG: $message");
    }
}

function populateActiveIds(&$idHash, $key)
{
    // If we have active instance IDs, grab them.
    if (empty($key) ||
        (array_key_exists($key, $_POST) == false
            &&
            array_key_exists($key, $_GET) == false)) {
        return; // No instances.
    }
    $arr = array();
    if (array_key_exists($key, $_POST)) {
        $arr = $_POST[$key];
    } else if (array_key_exists($key, $_GET)) {
        $arr = $_GET[$key];
    }
    foreach ($arr as $instance_id) {
        $instanceId = test_input($instance_id);
        if ($instanceId == null) {
            continue;
        }
        $idHash[$instanceId] = 1;
    }
}

function genFatalErrorReport($errorList, $fixOnSamePage = false,
    $backUrl = null, $closePage = true) {
    $errorHtml = "";
    $ec = 0;
    foreach ($errorList as $errorText) {
        if (empty($errorText)) {
            continue;
        }
        if ($ec > 0) {
            $errorHtml .= $errorText;
        } else {
            $errorHtml = $errorText;
        }
        $ec++;
    }
    $desc = "Errors";
    if ($ec == 0) {
        return null;
    } else if ($ec == 1) {
        $desc = "An error";
    }
    $retVal = <<<EOM
    
<div class="row justify-content-center">
<div class="col-6 mt-4">
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
<div class="panel-heading">
<h3>Oops! $desc occurred:</h3>
</div>
<div class="panel-body">
EOM;
    $backText = "<a href=\"javascript:history.back()\">here</a>";
    if ($backUrl) {
        $backText = "<a href=\"$backUrl\">here</a>";
    }
    $retVal = $retVal . $errorHtml;
    if ($fixOnSamePage) {
        $retVal = $retVal . "<p>Please fix the errors and try again.</p></div></div></div></div>";
    } else {
        $retVal = $retVal . "<p>Please click $backText to try again, or report the error to an administrator if it persists.</p></div></div></div></div>";
    }
    if ($closePage) {
        $retVal .= "</div>";
        $retVal = $retVal . footerText();
        $retVal = $retVal . "</body></html>";
    }

    return $retVal;
}

function genPickListForm($id2Name, $name, $tableName, $method = "POST")
{
    // Check to see if items in this table may be deleted.
    $err = "";
    $deleteAllowed = true;
    $db = new DbConn();
    $db->addSelectColumn('delete_ok');
    $db->addWhereColumn('name', $tableName, 's');
    $result = $db->simpleSelectFromTable('category_tables', $err);
    if ($result) {
        $row = $result->fetch_assoc();
        $deleteAllowed = intval($row["delete_ok"]);
    }
    $ucName = ucfirst($name);
    $ucPlural = ucfirst($tableName);

    if ($tableName == 'chugim') {
        $ucPlural = ucfirst(chug_term_plural);
        $ucName = ucfirst(chug_term_singular);
    } else if ($tableName == 'blocks') {
        $ucPlural = ucfirst(block_term_plural);
        $ucName = ucfirst(block_term_singular);
    } else if ($tableName == 'chug_groups') {
        $ucPlural = ucfirst(chug_term_singular) . ' Groups';
        $ucName = ucfirst(chug_term_singular);
    } else if ($tableName == 'edot') {
        $ucPlural = ucfirst(edah_term_plural);
        $ucName = ucfirst(edah_term_singular);
    }

    $formName = "form_" . $name;
    $idCol = $name . "_id";
    $editUrl = urlIfy("edit" . ucfirst($name) . ".php");
    $addUrl = urlIfy("add" . ucfirst($name) . ".php");
    $deleteUrl = urlIfy("delete.php?tableName=$tableName&idCol=$idCol");
    $article = "a";
    if (preg_match('/^[aeiou]/i', $name)) {
        $article = "an";
    }
    $edahExtraText = "";
    if ($name == "edah") {
        $edahExtraText = " To view the campers in an " . edah_term_singular . ", select an " . edah_term_singular . " and click <font color=\"red\">\"Show Campers\"</font>.";
    }
    $guideText = "";
    if ($deleteAllowed) {
        $guideText = "To add, edit or delete $article $ucName, choose $article $ucName from the drop-down list and click Add New $ucName, Edit or Delete. $edahExtraText";
    } else {
        $guideText = "To add or edit $article $ucName, choose $article $ucName from the drop-down list and click Add New $ucName or Edit. Deletion of " . lcfirst($ucPlural) .
            " is currently disallowed: to allow deletion, click \"Edit Admin Settings\" at the top of this page and adjust the check boxes. $edahExtraText";
    }
    $retVal = <<<EOM
<h2 class="accordion-header" id="heading-$name">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-$name" aria-expanded="false" aria-controls="collapse-$name">
    Manage $ucPlural
    </button>
</h2>
<div id="collapse-$name" class="accordion-collapse collapse" aria-labelledby="heading-$name" data-bs-parent="#accordionExample">
    <div class="accordion-body">

    <form method="$method">
    <div class="page-header">
    <h3>$ucPlural</h3></div>
    <ul><li>
    <div>
    <select class="form-select mb-3" id="$idCol" name="$idCol">
    <option value="" disabled=disabled selected>---</option>
EOM;
       foreach ($id2Name as $itemId => $itemName) {
           $retVal = $retVal . "<option value=\"$itemId\">$itemName</option>";
       }
       $formEnd = <<<EOM
    </select>
    <p class="guidelines"><small>$guideText</small></p>
    <input type="hidden" name="fromStaffHomePage" id="fromStaffHomePage" value="1" />
    <input class="btn btn-secondary btn-sm" type="submit" name="submit" value="Edit" formaction="$editUrl"/>
   
EOM;
       $retVal = $retVal . $formEnd;
       if ($deleteAllowed) {
           $delText = "<input class=\"btn btn-secondary btn-sm\" type=\"submit\" name=\"submit\" value=\"Delete\" " .
               "onclick=\"return confirm('Are you sure you want to delete this $ucName?')\" formaction=\"$deleteUrl\" />";
           $retVal = $retVal . $delText;
       }
       if ($name == "edah") {
           $camperUrl = urlIfy("viewCampersByEdah.php");
           $retVal =
               $retVal . "<input class=\"btn btn-success ms-1 btn-sm\" type=\"submit\" name=\"submit\" value=\"Show Campers\" formaction=\"$camperUrl\"/>";
       }
       $formEnd = <<<EOM
    </li>
    <li>
    </form>
    <form>
    <input type=button class='btn btn-primary' onClick="location.href='$addUrl'" value='Add New $ucName'>
    </li></ul>
    </form>
    
    </div>
</div>
EOM;
    $retVal = $retVal . $formEnd;

    return $retVal;
}

function genPickList($id2Name, $selectedMap, $name, $defaultMessage = null)
{

    $ucName = ucfirst($name);

    if ($name == 'chug') {
        $ucName = ucfirst(chug_term_singular);
    } else if ($name == 'block') {
        $ucName = ucfirst(block_term_singular);
    } else if ($name == 'edah') {
        $ucName = ucfirst(edah_term_singular);
    }

    $ddMsg = "Choose $ucName";
    if ($defaultMessage !== null) {
        $ddMsg = $defaultMessage;
    }
    $retVal = "<option value=\"\" >-- $ddMsg --</option>";
    if($name != "edah" && !strstr($name, "edot_for") & $name != edah_term_singular) {
        asort($id2Name);
    }
    foreach ($id2Name as $id => $name) {
        $selStr = "";
        if (array_key_exists($id, $selectedMap)) {
            $selStr = "selected";
        }
        $retVal = $retVal . "<option value=\"$id\" $selStr>$name</option>";
    }
    return $retVal;
}

function genCheckBox($id2Name, $activeIds, $arrayName)
{
    $retVal = "";
    if($arrayName != "edah_ids" && !strstr($arrayName, "edot_for")) {
        asort($id2Name);
    }
    foreach ($id2Name as $id => $name) {
        $selStr = "";
        $idStr = strval($id); // Use strings in forms, for consistency.
        if ($idStr !== null &&
            $activeIds !== null &&
            array_key_exists($idStr, $activeIds)) {
            $selStr = "checked=checked";
        }
        $retVal .= "<label class=\"form-check-label\"><input class=\"form-check-input me-1\" type=\"checkbox\" name=\"${arrayName}[]\" value=\"$idStr\" id=\"${arrayName}_$idStr\" $selStr>$name</label>";
    }
    return $retVal;
}

// Similar to genCheckBox, except we emit JS that limits the available checkboxes
// based on selected items in a parent.
function genConstrainedCheckBoxScript($id2Name, $arrayName,
    $ourId, $parentId, $descId) {
    asort($id2Name);
    $javascript = <<<JS
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha384-Dziy8F2VlJQLMShA6FHWNul/veM9bCkRUaLqr199K94ntO5QUrLJBEbYegdSkkqX" crossorigin="anonymous"></script>
<script>
function fillConstraintsCheckBox() {
    var parent = $("#${parentId}");
    var ourCheckBox = $("#${ourId}");
    var ourDesc = $("#${descId}");
    var values = {};
    values["get_legal_id_to_name"] = 1;
    var curSelectedEdahIds = [];
    $("#${parentId} input:checked").each(function() {
       curSelectedEdahIds.push($(this).attr('value'));
    });
    var sql = "SELECT e.group_id group_id, g.name group_name FROM edot_for_group e, chug_groups g WHERE e.edah_id IN (";
    var ct = 0;
    for (var i = 0; i < curSelectedEdahIds.length; i++) {
        if (ct++ > 0) {
            sql += ",";
        }
        sql += "?";
    }
    sql += ") AND e.group_id = g.group_id GROUP BY e.group_id HAVING COUNT(e.edah_id) = " + ct;
    if(ourCheckBox === "edah") {
        sql += " SORT BY e.sort_order";
    }
    values["sql"] = sql;
    values["instance_ids"] = curSelectedEdahIds;
    $.ajax({
        url: '../ajax.php',
        type: 'post',
        data: values,
        success: function(data) {
           ourCheckBox.empty();
           var html = "";
           if (data == "none") {
               $(ourCheckBox).hide();
               $(ourDesc).hide();
               return;
           } else if (data == "no-intersection") {
               html = "<b>Error</b>: The selected edot cannot be leveled together, because they have no common groups. Please choose a different edot combination, or edit the groups to allow these edot to be leveled together.";
               $(ourCheckBox).html(html);
               $(ourCheckBox).show();
               $(ourDesc).hide();
               return;
           }
           $.each(data, function(itemId, itemName) {
                html = "<label class=\"form-check-label\"><input class=\"form-check-input me-1\" type=\"checkbox\" name=\"" + "${arrayName}" +
                  "[]\" value=\"" + itemId + "\" checked=checked/>" + itemName + "</label>";
                $(ourCheckBox).append(html);
            });
           $(ourCheckBox).show();
           $(ourDesc).show();
        },
        error: function(xhr, desc, err) {
           console.log(xhr);
           console.log("Details: " + desc + " Error:" + err);
        }
    });
}
$(function() {
  $("#${parentId}").load(fillConstraintsCheckBox());
  $("#${parentId}").bind('change',fillConstraintsCheckBox);
});
</script>
JS;

    return $javascript;
}

// Modified from genConstrainedCheckbox to work for drop down menus, and
// to be applicable to more types
// NOTE: fillConstraintsPickList() must be called as an `onchange` method
//     from the element which it relies upon
function genConstrainedPickListScript($ourId, $parentId, $descId, $type, $choicesJS = false) {
    // Ensure $choicesJS is converted to a JavaScript boolean value
    $choicesJS = $choicesJS ? 'true' : 'false';
    $javascript = <<<JS
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha384-Dziy8F2VlJQLMShA6FHWNul/veM9bCkRUaLqr199K94ntO5QUrLJBEbYegdSkkqX" crossorigin="anonymous"></script>
<script>
function fillConstraintsPickList() {
    var parent = $("#${parentId}");
    var ourPickList = $("#${ourId}");
    var ourDesc = $("#${descId}");
    var values = {};
    values["get_legal_id_to_name"] = 1;
    $(ourDesc).hide();
    if(!${choicesJS}) {
        var parentField = document.getElementsByName("${parentId}")[0];
    }
    else {
        var parentField = document.getElementsByName("${parentId}[]")[0];
    }
    var curSelectedEdahIds = [];
    for (var option of parentField.selectedOptions) {
        curSelectedEdahIds.push(option.value);
    }
    if("${type}" == "group" || "${type}" == "block" || "${type}" == "schedule") {
        var sql = "SELECT e.${type}_id ${type}_id, g.name ${type}_name FROM edot_for_${type} e, "
        // Determine right table to search from
        if ("${type}" == "group") {
            sql += "chug_groups g WHERE e.edah_id IN (";
        }
        else if ("${type}" == "block") {
            sql += "blocks g WHERE e.edah_id IN (";
        }
        else if ("${type}" == "schedule") {
            sql += "schedules g WHERE e.edah_id IN (";
        }
        var ct = 0;
        for (var i = 0; i < curSelectedEdahIds.length; i++) {
            if (ct++ > 0) {
                sql += ",";
            }
            sql += "?";
        }
        sql += ") AND e.${type}_id = g.${type}_id ";
        if("${type}" == "group" && $("#${choicesJS}")) {
            sql += "AND active_block_id IS NOT NULL ";
        }
        sql += "GROUP BY e.${type}_id HAVING COUNT(e.edah_id) = " + ct;
    }
    if(ourPickList === "edah") {
        sql += " SORT BY e.sort_order";
    }
    values["sql"] = sql;
    values["instance_ids"] = curSelectedEdahIds;
    $.ajax({
        url: '../ajax.php',
        type: 'post',
        data: values,
        success: function(data) {
        ourPickList.empty();
        var html = "";
        if (data == "none") {
            $(ourPickList).hide();
            $(ourDesc).hide();
            return;
        } else if (data == "no-intersection") {
            $(ourPickList).html(html);
            $(ourPickList).show();
            $(ourDesc).hide();
            return;
        }
        if(!$("#${choicesJS}")) {
            html = "<select class=\"form-select\" id=\"${ourId}\" name=\"${type}\" required";
        }
        else {
            html = "<select class=\"form-select choices-js\" id=\"${ourId}\" name=\"${type}\"";
        }

        if ("${type}" == "schedule") {
            html += " onchange=\"loadSchedule()\"> <option value=\"\"> -- New Schedule -- </option>";
        }
        else if ("${type}" == "group") {
            if(typeof fillChugimConstraintsPickList === "function") {
                html += " onchange=\"fillChugimConstraintsPickList()\"> <option value=\"\"> -- Choose Perek -- </option>";
            }
            else {
                html += " onchange=\"\"> <option value=\"\"> -- Choose Perek -- </option>";
            }
        }
        else {
            html += ">";
        }
        // add individual options
        $.each(data, function(itemId, itemName) {
                html += "<option value=\""+itemId+"\">"+itemName+"</option>";
            });
        $(ourPickList).append(html);
        $(ourPickList).show();
        $(ourDesc).show();
        if(${choicesJS}) {
            const choices = new Choices($(ourPickList).find('select')[0], {shouldSort: false, allowHTML: true, searchEnabled: false});
        }
        },
        error: function(xhr, desc, err) {
        console.log(xhr);
        console.log("Details: " + desc + " Error:" + err);
        }
    });
}
$(function() {
  $("#${parentId}").load(fillConstraintsPickList());
  $("#${parentId}").bind('change',fillConstraintsPickList);
});
</script>
JS;

    return $javascript;
}

// Based off of genConstrainedPickListScript (above) to find chugim based on edah, b
function genChugimPickListScript($ourId, $parent1, $parent2, $descId, $type, $choicesJS = false) {
    $javascript = <<<JS
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha384-Dziy8F2VlJQLMShA6FHWNul/veM9bCkRUaLqr199K94ntO5QUrLJBEbYegdSkkqX" crossorigin="anonymous"></script>
<script>
function fillChugimConstraintsPickList() {
    var parent1 = $("#${parent1}");
    var parent2 = $("#${parent2}");
    var ourPickList = $("#${ourId}");
    var ourDesc = $("#${descId}");
    var values = {};
    values["get_legal_id_to_name"] = 1;
    $(ourDesc).hide();
    $(ourPickList).hide();

    // perform basic sanity checks to ensure the two parent fields (edah, group) have valid values
    var parent1Field = document.getElementsByName("${parent1}[]")[0];
    var parent2Field = document.getElementsByName("${parent2}");
    if(parent2Field.length < 1) { return; } // verifies there is a value present for group; if not, end
    parent2Field = parent2Field[0];
    var instanceIds = [];
    instanceIds.push(parent2Field.selectedOptions[0].value); // the group_id is used twice in the SQL statement, so added twice to instance id array
    instanceIds.push(parent2Field.selectedOptions[0].value);
    for (var option of parent1Field.selectedOptions) { // adds selected edot to instance id array
        instanceIds.push(option.value);
    }
    if(parent1Field.selectedOptions.length < 1) { return; } // ensures 1+ edot are selected before continuing

    if("${type}" == "chug") {
        // sql statement looks for all chugim which apply to any number of the selected edot for given chug group and active block
        var sql = "SELECT e.chug_id, c.name FROM edot_for_chug e JOIN (SELECT c.chug_id, c.name FROM chug_instances i ";
        sql += "JOIN chugim c ON i.chug_id = c.chug_id WHERE i.block_id = (SELECT active_block_id FROM chug_groups WHERE group_id = ?) AND c.group_id = ?) c "
        sql += "ON e.chug_id = c.chug_id WHERE e.edah_id IN(";
        
        var ct = 0;
        for (var i = 2; i < instanceIds.length; i++) {
            if (ct++ > 0) {
                sql += ",";
            }
            sql += "?";
        }
        sql += ") GROUP BY e.chug_id";
    }
    values["sql"] = sql;
    values["instance_ids"] = instanceIds;
    $.ajax({
        url: '../ajax.php',
        type: 'post',
        data: values,
        success: function(data) {
        ourPickList.empty();
        var html = "";
        if (data == "none") {
            $(ourPickList).hide();
            $(ourDesc).hide();
            return;
        } else if (data == "no-intersection") {
            $(ourPickList).html(html);
            $(ourPickList).show();
            $(ourDesc).hide();
            return;
        }
        if(!$("#${choicesJS}")) {
            html = "<select class=\"form-select\" id=\"${ourId}\" name=\"${type} required\"";
        }
        else {
            html = "<select class=\"form-select choices-js\" id=\"${ourId}\" name=\"${type}\"";
        }

        if ("${type}" == "schedule") {
            html += " onchange=\"loadSchedule()\"> <option value=\"\"> -- New Schedule -- </option>";
        }
        else if ("${type}" == "chug") {
            html += " onchange=\"\"> <option value=\"\"> -- Choose Chug -- </option>";
        }
        else {
            html += ">";
        }
        // add individual options
        $.each(data, function(itemId, itemName) {
                html += "<option value=\""+itemId+"\">"+itemName+"</option>";
            });
        $(ourPickList).append(html);
        $(ourPickList).show();
        $(ourDesc).show();
        if($("#${choicesJS}")) {
            const choices = new Choices($(ourPickList).find('select')[0], {shouldSort: true, allowHTML: true});
        }
        },
        error: function(xhr, desc, err) {
        console.log(xhr);
        console.log("Details: " + desc + " Error:" + err);
        }
    });
}
$(function() {
  $("#${parent1}").load(fillChugimConstraintsPickList());
  $("#${parent1}").bind('change',fillChugimConstraintsPickList);
  $("#${parent2}").load(fillChugimConstraintsPickList());
  $("#${parent2}").bind('change',fillChugimConstraintsPickList);
});
</script>
JS;

    return $javascript;
}

function test_input($data)
{
    if (empty($data)) {
        return null;
    }
    if(!is_array($data)) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
    }
    return $data;
}

function test_post_input($data)
{
    if (!isset($_POST[$data])) {
        return null;
    }
    return test_input($_POST[$data]);
}

function test_get_input($data)
{
    if (!isset($_GET[$data])) {
        return null;
    }
    return test_input($_GET[$data]);
}

function urlBaseText()
{
    $scheme = "http";
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
        $scheme = "https";
    }
    $localUrl = "/";
    $lastSlashPos = strrpos($_SERVER["PHP_SELF"], "/"); // Note that we're reverse-searching.
    if ($lastSlashPos != false) {
        // Remove everything after the last slash (keep the slash).
        $localUrl = substr($_SERVER["PHP_SELF"], 0, $lastSlashPos + 1);
    }
    // Return the local URL minus everything after the last slash.
    return $scheme . "://" . $_SERVER['HTTP_HOST'] . $localUrl;
}

function urlIfy($localLink)
{
    return urlBaseText() . $localLink;
}

function adminLoggedIn()
{
    return isset($_SESSION['admin_logged_in']);
}

function roshLoggedIn()
{
    return isset($_SESSION['rosh_logged_in']);
}

function chugLeaderLoggedIn()
{
    return isset($_SESSION['chug_leader_logged_in']);
}

function camperLoggedIn()
{
    return isset($_SESSION['camper_logged_in']);
}

function baseUrl()
{
    return urlIfy("../index.php");
}

function homeUrl()
{
    if (isset($_SESSION['admin_logged_in'])) {
        return urlIfy("../staffHome.php");
    } else {
        return urlIfy("../camperHome.php");
    }
}

function homeAnchor($text = "home")
{
    $homeUrl = homeUrl();
    return "<a href=\"$homeUrl\">$text</a>";
}

function staffHomeAnchor($text = "home")
{
    $homeUrl = urlIfy("../staffHome.php");
    return "<a href=\"$homeUrl\">$text</a>";
}

function loginRequiredMessage()
{
    $retVal = "";
    if (!isset($_SESSION['admin_logged_in'])) {
        $retVal = "<font color=\"red\"><b>Login required!</b></font><br>The page you are " .
            "accessing requires that you log in with a staff password.<br>";
    }
    return $retVal;
}

function bounceToLogin($role = "admin")
{
    if($role == "admin") {
        if (!isset($_SESSION['admin_logged_in'])) {
            $fromUrl = $_SERVER["PHP_SELF"];
            $redirUrl = urlIfy("../staffLogin.php?from=$fromUrl");
            header("Location: $redirUrl");
            exit();
        }
    }
    else if ($role == "chugLeader") {
        if (!isset($_SESSION['chug_leader_logged_in'])) {
            $fromUrl = $_SERVER["PHP_SELF"];
            $redirUrl = urlIfy("../staffLogin.php?from=$fromUrl");
            header("Location: $redirUrl");
            exit();
        }
    }
    else if ($role == "rosh") {
        if (!isset($_SESSION['rosh_logged_in'])) {
            $fromUrl = $_SERVER["PHP_SELF"];
            $redirUrl = urlIfy("../staffLogin.php?from=$fromUrl");
            header("Location: $redirUrl");
            exit();
        }
    }
}

function camperBounceToLogin()
{
    if ((!isset($_SESSION['camper_logged_in'])) &&
        (!isset($_SESSION['admin_logged_in']))) {
        $fromUrl = $_SERVER["PHP_SELF"];
        $redirUrl = urlIfy("../index.php?retry=1");
        header("Location: $redirUrl");
        exit();
    }
}

function fromBounce()
{
    $qs = htmlspecialchars($_SERVER['QUERY_STRING']);
    $parts = explode("=/", $qs);
    if (count($parts) == 2 &&
        $parts[0] == "from") {
        return true;
    }
    return false;
}

function bouncePastIfLoggedIn($localLink, $role)
{
    if($role == "admin") {
        if (isset($_SESSION['admin_logged_in'])) {
            $redirUrl = urlIfy($localLink);
            header("Location: $redirUrl");
            exit();
        }
    }
    else if($role == "chugLeader") {
        if (isset($_SESSION['chug_leader_logged_in'])) {
            $redirUrl = urlIfy($localLink);
            header("Location: $redirUrl");
            exit();
        }
    }
    else if($role == "rosh") {
        if (isset($_SESSION['rosh_logged_in'])) {
            $redirUrl = urlIfy($localLink);
            header("Location: $redirUrl");
            exit();
        }
    }
}

function staffBounceBackUrl()
{
    $url = urlBaseText() . "staffHome.php"; // Default staff redirect
    $parts = array();
    $qs = htmlspecialchars($_SERVER['QUERY_STRING']);
    $qs_from_post = test_post_input('query_string');
    $qs_from_post = preg_replace("/&#?[a-z0-9]+;/i", "", $qs_from_post);
    if (!empty($qs)) {
        $parts = explode("/", $qs);
    } else if (!empty($qs_from_post)) {
        $parts = explode("/", $qs_from_post);
    }
    if (count($parts) > 0) {
        $len = count($parts);
        $from = array_search("from=",$parts);
        $str = "";
        for($i = $from + 1; $i < $len - 1; $i++) {
            $str .= $parts[$i] . "/";
        }
        $url = urlBaseText() . $str . $parts[$len - 1];
    }

    return $url;
}

function checkLogout() 
{
    // sign out if logout button was pressed
    $logout = test_get_input('logout');
    if (!empty($logout)) {
        unset($_SESSION['rosh_logged_in']);
        unset($_SESSION['chug_leader_logged_in']);
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['camper_logged_in']);
        bounceToLogin();
    }
}

function navText()
{
    setup_camp_specific_terminology_constants();
    $homeUrl = homeUrl();
    $retVal = "<nav class=\"navbar navbar-expand-lg navbar-light bg-light\">";
    $baseUrl = baseUrl();
    $retVal .= "<div class=\"container-fluid\">";
    $retVal .= "<a class=\"navbar-brand\" href=\"$baseUrl\">Chugbot</a>";
    $retVal .= "<button class=\"navbar-toggler\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#navbarSupportedContent\" aria-controls=\"navbarSupportedContent\" aria-expanded=\"false\" aria-label=\"Toggle navigation\">";
    $retVal .= "<span class=\"navbar-toggler-icon\"></span></button>";
    $retVal .= "<div class=\"collapse navbar-collapse\" id=\"navbarSupportedContent\">";
    $retVal .= "<ul class=\"navbar-nav me-auto mb-2 mb-lg-0\">";
    if (adminLoggedIn()) {
        $adminAttendanceUrl = urlIfy("../attendance/");
        $retVal .= "<li class=\"nav-item dropdown\"><a class=\"nav-link dropdown-toggle\" href=\"#\" id=\"adminNavbarDropdown\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">" . 
            "Admin</a><ul class=\"dropdown-menu\" aria-labelledby=\"adminNavbarDropdown\">" .
            "<li><a class=\"dropdown-item\" href=\"$homeUrl\">Main Admin Home</a></li>" . 
            "<li><a class=\"dropdown-item\" href=\"$adminAttendanceUrl\">Admin Attendance Settings</a></li>" . 
            "</ul></li>";
    }
    if (roshLoggedIn() & check_enabled("rosh_yoetzet_password")) {
        $roshUrl = urlIfy("../attendance/roshHome.php");
        $retVal .= "<li class=\"nav-item dropdown\"><a class=\"nav-link dropdown-toggle\" href=\"#\" id=\"roshNavbarDropdown\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">" . 
            "Rosh/Yoetzet</a><ul class=\"dropdown-menu\" aria-labelledby=\"roshNavbarDropdown\">" .
            "<li><a class=\"dropdown-item\" href=\"$roshUrl\">Rosh/Yoetzet Home (Attendance Module)</a></li>" . 
            "<li><a class=\"dropdown-item\" href=\"../designSchedules.php\">Schedule Builder</a></li>" . 
            "</ul></li>";
    }
    if (chugLeaderLoggedIn() & check_enabled("chug_leader_password")) {
        $chugLeaderUrl = urlIfy("../attendance/chugLeaderHome.php");
        $retVal .= "<li class=\"nav-item\"><a class=\"nav-link\" href=\"$chugLeaderUrl\">" . ucfirst(chug_term_singular) . " Leader Home</a></li>";
    }
    if (camperLoggedIn()) {
        $camperUrl = urlIfy("../camperHome.php");
        $retVal .= "<li class=\"nav-item\"><a class=\"nav-link\" href=\"$camperUrl\">Camper Home</a></li>";
    } else {
        $retVal .= "<li class=\"nav-item\"><a class=\"nav-link\" href=\"$homeUrl\">Camper Home</a></li>";
    }

    $db = new DbConn();
    $db->addSelectColumn('camp_name');
    $db->addSelectColumn('camp_web');
    $result = $db->simpleSelectFromTable('admin_data', $err);
    if ($result) {
        $row = $result->fetch_assoc();
	if ($row) {
           $campUrl = $row["camp_web"];
           $campName = $row["camp_name"];
           if ((!empty($campUrl)) &&
              (!empty($campName))) {
              $retVal .= "<li class=\"nav-item\"><a class=\"nav-link\" href=\"http://$campUrl/\">$campName Home</a></li>";
           }
	}
    }
    $retVal .= "</ul>";
    
    if (camperLoggedIn()) {
        $retVal .= "<form class=\"d-flex ms-auto\" method=\"GET\">" . 
        "<button class=\"nav-link btn btn-link\" name=\"logout\" value=\"1\" type=\"submit\">Logout</button>" . 
      "</form>";
    }
    
    $retVal .= "</div></div></nav>";

    return $retVal;
}

function footerText()
{
    return "";
}

function headerText($title)
{
    $navText = navText();
    $retVal = <<<EOM
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>$title</title>
<link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">
<script type="text/javascript" src="../meta/view.js"></script>
<link rel="stylesheet" type="text/css" href="../meta/view.css" media="all">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha384-Dziy8F2VlJQLMShA6FHWNul/veM9bCkRUaLqr199K94ntO5QUrLJBEbYegdSkkqX" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@4.5.2/dist/flatly/bootstrap.min.css" integrity="sha384-qF/QmIAj5ZaYFAeQcrQ6bfVMAh4zZlrGwTPY7T/M+iTTLJqJBJjwwnsE5Y0mV7QK" crossorigin="anonymous">-->
</head>
<body id="main_body">
$navText
EOM;
    return $retVal;
}

function check_enabled($adminColumn)
{
    $db = new DbConn();
    $dbErr = "";
    $db->isSelect = true;
    $db->addSelectColumn($adminColumn);
    $result = $db->simpleSelectFromTable("admin_data", $dbErr);
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row) {
            return (bool)$row[$adminColumn];
        }
    }
    return false;
}


function genErrorPage($err)
{
    $retVal = headerText("Error Page");
    $retVal .= genFatalErrorReport(array($err));
    return $retVal;
}

function errorString($data)
{
    return "<font color=\"red\">* $data</font><br>";
}

function dbErrorString($sql, $data)
{
    $msg = "";
    preg_match("/duplicate entry '([^']+)/i", $data,
        $array);
    if (count($array)) {
        $msg = "$array[1] already exists in the database";
    } else {
        $msg = "Database Error: $data";
    }
    $retVal = "<font color=\"red\">$msg</font><br>";
    if (DEBUG) {
        $retVal .= "Error: " . $sql . "<br>";
    }
    return $retVal;
}

function yearOfUpcomingSummer()
{
    $month = date('n'); // Month, 1-12
    if ($month >= 7) {
        // Jul or later: return next year
        return strval((intval(date('Y')) + 1));
    } else {
        // Jan-Jun: return current year.
        return date('Y');
    }
}

function yearOfCurrentSummer()
{
    $month = date('n'); // Month, 1-12
    if ($month >= 9) {
        // Sep or later: return next year
        return strval((intval(date('Y')) + 1));
    } else {
        // Jan-Jun: return current year.
        return date('Y');
    }
}

function genPassToEditPageForm($action, $paramHash)
{
    $retVal = "<form action=\"$action\" method=\"POST\" name=\"passToEditPageForm\">";
    foreach ($paramHash as $name => $value) {
        if (is_array($value)) {
            foreach ($value as $valueElement) {
                $retVal = $retVal . "<input type=\"hidden\" name=\"$name\" value=\"$valueElement\">";
            }
        } else {
            $retVal = $retVal . "<input type=\"hidden\" name=\"$name\" value=\"$value\">";
        }
    }
    $retVal = $retVal . '<input type="hidden" name="fromAddPage" value="1">';
    $retVal = $retVal . '</form>';
    $retVal = $retVal . '<script type="text/javascript">document.passToEditPageForm.submit();</script>';

    return $retVal;
}

function setup_camp_specific_terminology_constants() {
    $db = new DbConn();
    $sql = "SELECT chug_term_singular, chug_term_plural, block_term_singular, block_term_plural, edah_term_singular, edah_term_plural FROM admin_data";
    $result = $db->runQueryDirectly($sql, $dbErr);
    if ($dbErr) {
        fatalError($dbErr);
    }
    $row = $result->fetch_assoc();

    foreach ($row as $key => $value) {
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// class to easily create Bootstrap Accordions
// to use, just create a new instance of the accordion and add each entry
//      call renderHtml() to export
class bootstrapAccordion
{
    // the following three variables are options for the accordion. $name is a string, the others are booleans
    protected $name;
    protected $flush; // makes accordion edge-to-edge with parent
    protected $alwaysOpen; // if true, multiple entries can be open at once
    // ids are first param set - used in the accordion itself and for editing fields (if needed)
    protected $ids = array();
    // all other entries are added in according to ids
    protected $titles = array();
    protected $bodies = array();
    protected $open = array();



    // initialize
    public function __construct(string $name, bool $flush=false, bool $alwaysOpen=false)
    {
        $this->name = $name;
        $this->flush = $flush;
        $this->alwaysOpen = $alwaysOpen;
    }

    // add element
    public function addAccordionElement($id, string $title, string $body, bool $open)
    {
        // only one instance of id is allowed
        if (!in_array($id, $this->ids))
        {
            array_push($this->ids, $id);
            $this->titles[$id] = $title;
            $this->bodies[$id] = $body;
            // if $alwaysOpen is set, only one entry can start off open. Use first by default
            if (($this->alwaysOpen & !in_array(true, $this->open)) || !$this->alwaysOpen)
            {
                $this->open[$id] = $open;
            } else
            {
                $this->open[$id] = false;
            }
        }
    }

    // edit element
    // uses $id as key, requires key to exist
    public function editAccordionElement($id, string $title, string $body, bool $open)
    {
        // only one instance of id is allowed
        if (in_array($id, $this->ids))
        {
            $this->titles[$id] = $title;
            $this->bodies[$id] = $body;
            // if $alwaysOpen is set, only one entry can start off open. Use first by default
            // but here, start by clearing existing value for this key
            unset($this->open[$id]);
            if (($this->alwaysOpen & !in_array(true, $this->open)) || !$this->alwaysOpen)
            {
                $this->open[$id] = $open;
            } else
            {
                $this->open[$id] = false;
            }
        }
    }

    // echoes the accordion instance
    // elements are returned by the order they were added
    public function renderHtml()
    {
        $html = "<div class=\"accordion";
        // if flush, include it
        if ($this->flush)
        {
            $html .= " accordion-flush";
        }
        $html .= "\" id=\"accordion" . $this->name . "\">";

        // create each element
        foreach($this->ids as $id)
        {
            // add element
            $html .= "<div class=\"accordion-item\"><h2 class=\"accordion-header\" id=\"heading$id\">";
            
            // create header
            $html .= "<button class=\"accordion-button";
            if (!$this->open[$id])
            {
                $html .= " collapsed";
            }
            $html .= "\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#collapse$id\" aria-expanded=\"" . $this->open[$id] . "\" aria-controls=\"collapse$id\">";
            $html .= $this->titles[$id];
            $html .= "</button></h2>";
            
            // create body
            $html .= "<div id=\"collapse$id\" class=\"accordion-collapse collapse";
            if ($this->open[$id]) 
            {
                $html .= " show";
            }
            $html .= "\" aria-labelledby=\"heading$id\"";
            if (!$this->alwaysOpen)
            {
                $html .= " data-bs-parent=\"#accordion" . $this->name . "\"";
            }
            $html .= "><div class=\"accordion-body\">";
            $html .= $this->bodies[$id];
            $html .= "</div></div></div>";
        }

        // close accordion
        $html .= "</div>";

        // returns result (if necessary to edit, it is then theoretically possible (e.g. using str_replace to change classes))
        return $html;
    }
}