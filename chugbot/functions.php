<?php
include_once 'constants.php';
include_once 'functions.php';
require_once 'PHPMailer/PHPMailer.php';

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
    $mail = new PHPMailer;
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
    $mail->isHTML(true);
    $mail->SMTPAuth = true;
    $mail->Host = 'localhost';
    $mail->Port = 25;
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
    $mail->setFrom(ADMIN_EMAIL_USERNAME, $fromName);
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
            $errorHtml .= ", " . $errorText;
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
<div class="row">
<div class="panel panel-danger col-lg-6 col-lg-offset-3">
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
        $retVal = $retVal . "<p>Please fix the errors and try again.</p></div></div></div>";
    } else {
        $retVal = $retVal . "<p>Please click $backText to try again, or report the error to an administrator if it persists.</p></div></div></div>";
    }
    if ($closePage) {
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
    $formName = "form_" . $name;
    $idCol = $name . "_id";
    $editUrl = urlIfy("edit" . $ucName . ".php");
    $addUrl = urlIfy("add" . $ucName . ".php");
    $deleteUrl = urlIfy("delete.php?tableName=$tableName&idCol=$idCol");
    $article = "a";
    if (preg_match('/^[aeiou]/i', $name)) {
        $article = "an";
    }
    $edahExtraText = "";
    if ($name == "edah") {
        $edahExtraText = " To view the campers in an edah, select an edah and click <font color=\"red\">\"Show Campers\"</font>.";
    }
    $guideText = "";
    if ($deleteAllowed) {
        $guideText = "To add, edit or delete $article $ucName, choose $article $ucName from the drop-down list and click Add New $ucName, Edit or Delete. $edahExtraText";
    } else {
        $guideText = "To add or edit $article $ucName, choose $article $ucName from the drop-down list and click Add New $ucName or Edit. Deletion of $tableName " .
            "is currently disallowed: to allow deletion, click \"Edit Admin Settings\" at the top of this page and adjust the check boxes. $edahExtraText";
    }
    $retVal = <<<EOM
<div class="panel panel-default">
 <div class="panel-heading">
  <h4 class="panel-title">
   <a data-toggle="collapse" data-parent="#accordion" href="#$formName">Manage $ucPlural</a>
  </h4>
 </div>
<div id="$formName" class="panel-collapse collapse panel-body">
 <form method="$method">
 <div class="page-header">
 <h3>$ucPlural</h3></div>
 <ul><li>
 <div>
 <select class="form-control" id="$idCol" name="$idCol">
 <option value="" disabled=disabled selected>---</option>
EOM;
    foreach ($id2Name as $itemId => $itemName) {
        $retVal = $retVal . "<option value=\"$itemId\">$itemName</option>";
    }
    $formEnd = <<<EOM
 </select>
 <p class="guidelines"><small>$guideText</small></p>
 <input type="hidden" name="fromStaffHomePage" id="fromStaffHomePage" value="1" />
 <input class="btn btn-default btn-sm" type="submit" name="submit" value="Edit" formaction="$editUrl"/>

EOM;
    $retVal = $retVal . $formEnd;
    if ($deleteAllowed) {
        $delText = "<input class=\"btn btn-default btn-sm\" type=\"submit\" name=\"submit\" value=\"Delete\" " .
            "onclick=\"return confirm('Are you sure you want to delete this $ucName?')\" formaction=\"$deleteUrl\" />";
        $retVal = $retVal . $delText;
    }
    if ($name == "edah") {
        $camperUrl = urlIfy("viewCampersByEdah.php");
        $retVal =
            $retVal . "<input class=\"btn btn-danger btn-sm\" type=\"submit\" name=\"submit\" value=\"Show Campers\" formaction=\"$camperUrl\"/>";
    }
    $formEnd = <<<EOM
 </li>
 <li>
 </form>
 <form>
 <input type=button class='btn btn-primary' onClick="location.href='$addUrl'" value='Add New $ucName'>
 </form>
 </li></ul>
 </div>
</div>
EOM;
    $retVal = $retVal . $formEnd;

    return $retVal;
}

function genPickList($id2Name, $selectedMap, $name, $defaultMessage = null)
{
    $ucName = ucfirst($name);
    $ddMsg = "Choose $ucName";
    if ($defaultMessage !== null) {
        $ddMsg = $defaultMessage;
    }
    $retVal = "<option value=\"\" >-- $ddMsg --</option>";
    asort($id2Name);
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
    asort($id2Name);
    foreach ($id2Name as $id => $name) {
        $selStr = "";
        $idStr = strval($id); // Use strings in forms, for consistency.
        if ($idStr !== null &&
            $activeIds !== null &&
            array_key_exists($idStr, $activeIds)) {
            $selStr = "checked=checked";
        }
        $retVal = $retVal . "<input type=\"checkbox\" name=\"${arrayName}[]\" value=\"$idStr\" $selStr />$name<br>";
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
    values["sql"] = sql;
    values["instance_ids"] = curSelectedEdahIds;
    $.ajax({
        url: 'ajax.php',
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
                html = "<input type=\"checkbox\" name=\"" + "${arrayName}" +
                  "[]\" value=\"" + itemId + "\" checked=checked/>" + itemName + "<br>";
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

function test_input($data)
{
    if (empty($data)) {
        return null;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
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

function camperLoggedIn()
{
    return isset($_SESSION['camper_logged_in']);
}

function baseUrl()
{
    return urlIfy("index.php");
}

function homeUrl()
{
    if (isset($_SESSION['admin_logged_in'])) {
        return urlIfy("staffHome.php");
    } else {
        return urlIfy("camperHome.php");
    }
}

function homeAnchor($text = "home")
{
    $homeUrl = homeUrl();
    return "<a href=\"$homeUrl\">$text</a>";
}

function staffHomeAnchor($text = "home")
{
    $homeUrl = urlIfy("staffHome.php");
    return "<a href=\"$homeUrl\">$text</a>";
}

function loginRequiredMessage()
{
    $retVal = "";
    if (!isset($_SESSION['admin_logged_in'])) {
        $retVal = "<font color=\"red\"><b>Login required!</b></font><br>The page you are " .
            "accessing requires that you log in with the admin password.<br>";
    }
    return $retVal;
}

function bounceToLogin()
{
    if (!isset($_SESSION['admin_logged_in'])) {
        $fromUrl = $_SERVER["PHP_SELF"];
        $redirUrl = urlIfy("staffLogin.php?from=$fromUrl");
        header("Location: $redirUrl");
        exit();
    }
}

function camperBounceToLogin()
{
    if ((!isset($_SESSION['camper_logged_in'])) &&
        (!isset($_SESSION['admin_logged_in']))) {
        $fromUrl = $_SERVER["PHP_SELF"];
        $redirUrl = urlIfy("index.php?retry=1");
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

function bouncePastIfLoggedIn($localLink)
{
    if (isset($_SESSION['admin_logged_in'])) {
        $redirUrl = urlIfy($localLink);
        header("Location: $redirUrl");
        exit();
    }
}

function staffBounceBackUrl()
{
    $url = urlBaseText() . "staffHome.php"; // Default staff redirect
    $parts = array();
    $qs = htmlspecialchars($_SERVER['QUERY_STRING']);
    $qs_from_post = test_input($_POST['query_string']);
    $qs_from_post = preg_replace("/&#?[a-z0-9]+;/i", "", $qs_from_post);
    if (!empty($qs)) {
        $parts = explode("/", $qs);
    } else if (!empty($qs_from_post)) {
        $parts = explode("/", $qs_from_post);
    }
    if (count($parts) > 0) {
        $len = count($parts);
        $url = urlBaseText() . $parts[$len - 1];
    }

    return $url;
}

function navText()
{
    $homeUrl = homeUrl();
    $retVal = "<nav class=\"navbar navbar-default\">";
    $baseUrl = baseUrl();
    $retVal .= "<div class=\"container-fluid\">";
    $retVal .= "<div class=\"navbar-header\">";
    $retVal .= "<button type=\"button\" class=\"navbar-toggle\" data-toggle=\"collapse\" data-target=\"#myNavbar\">";
    $retVal .= "<span class=\"icon-bar\"></span><span class=\"icon-bar\"></span><span class=\"icon-bar\"></span></button>";
    if (adminLoggedIn()) {
        $retVal .= "<a class=\"navbar-brand\" href=\"$homeUrl\">Staff Home</a></div>";
        $retVal .= "<div class=\"collapse navbar-collapse\" id=\"myNavbar\"><ul class=\"nav navbar-nav\">";
        $camperUrl = urlIfy("camperHome.php");
        $retVal .= "<li><a href=\"$camperUrl\">Camper Home</a></li>";
    } else {
        $retVal .= "<a class=\"navbar-brand\" href=\"$homeUrl\">Camper Home</a></div>";
        $retVal .= "<div class=\"collapse navbar-collapse\" id=\"myNavbar\"><ul class=\"nav navbar-nav\">";
    }
    $retVal .= "<li><a href=\"$baseUrl\">Site Home</a></li>";

    $db = new DbConn();
    $db->addSelectColumn('camp_name');
    $db->addSelectColumn('camp_web');
    $result = $db->simpleSelectFromTable('admin_data', $err);
    if ($result) {
        $row = $result->fetch_assoc();
        $campUrl = $row["camp_web"];
        $campName = $row["camp_name"];
        if ((!empty($campUrl)) &&
            (!empty($campName))) {
            $retVal .= "<li><a href=\"http://$campUrl/\">$campName Home</a></li>";
        }
    }
    $retVal .= "</ul></div></div></nav>";

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
<script type="text/javascript" src="meta/view.js"></script>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha384-Dziy8F2VlJQLMShA6FHWNul/veM9bCkRUaLqr199K94ntO5QUrLJBEbYegdSkkqX" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js" integrity="sha384-VI5+XuguQ/l3kUhh4knz7Hxptx47wpQbVRDnp8v7Vvuhzwn1PEYb/uvtH6KLxv6d" crossorigin="anonymous"></script>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Ej0hUpn6wbrOTJtRExp8jvboBagaz+Or6E9zzWT+gHCQuuZQQVZUcbmhXQzSG17s" crossorigin="anonymous">
</head>
<body id="main_body">
$navText
EOM;
    return $retVal;
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
