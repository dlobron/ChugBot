<?php
    include_once 'constants.php';
    require_once 'PHPMailer/PHPMailerAutoload.php';
    
    function forwardNoHistory($url) {
        $retVal = '<script type="text/javascript">';
        $retVal .= "window.location.replace(\"$url\")";
        $retVal .= '</script>';
        $retVal .= '<noscript>';
        $retVal .= '<meta http-equiv="refresh" content="0;url='.$url.'" />';
        $retVal .= '</noscript>';
        
        return $retVal;
    }
    
    function sendMail($address,
                      $subject,
                      $body,
                      $admin_data_row,
                      &$error) {
        // An example of the possible parameters for PHPMailer can be found here:
        // https://github.com/Synchro/PHPMailer/blob/master/examples/gmail.phps
        // The settings below are the ones needed by CRNE's ISP, A Small Orange, as
        // of 2016.
        $mail = new PHPMailer;
        // JQuery is unable to parse our JSON if an email error
        // occurs when SMTPDebug is enabled, so I'm not using it for now.  
        //$mail->SMTPDebug = 1; // DBG: 1 = errors and messages, 2 = messages only
        $mail->addAddress($address);
        if ($admin_data_row["admin_email_cc"] != NULL &&
            (! empty($admin_data_row["admin_email_cc"]))) {
            $ccs = preg_split("/[,:; ]/", $admin_data_row["admin_email_cc"]);
            foreach ($ccs as $cc) {
                $mail->AddCC($cc);
            }
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
        $mail->setFrom(ADMIN_EMAIL_USERNAME, $admin_data_row["camp_name"]);
        $mail->addReplyTo($admin_data_row["admin_email"], $admin_data_row["camp_name"]);
        $sentOk = $mail->send();
        if (! $sentOk) {
            error_log("Failed to send email to $address");
            error_log("Mailer error: " . $mail->ErrorInfo);
            $error = $mail->ErrorInfo;
        } else {
            error_log("Mail sent to $address OK");
        }
        
        return $sentOk;
    }
    
    function debugLog($message) {
        if (DEBUG) {
            error_log("DBG: $message");
        }
    }
    
    function populateActiveIds(&$idHash, $key) {
        // If we have active instance IDs, grab them.
        if (empty($key) ||
            empty($_POST[$key])) {
            return; // No instances.
        }
        foreach ($_POST[$key] as $instance_id) {
            $instanceId = test_input($instance_id);
            if ($instanceId == NULL) {
                continue;
            }
            $idHash[$instanceId] = 1;
        }
    }
    
    function overUnder($chugim, &$underMin, &$overMax) {
        foreach ($chugim as $chug) {
            if ($chug->assigned_count < $chug->min_size) {
                $amtUnder = $chug->min_size - $chug->assigned_count;
                if (empty($underMin)) {
                    $underMin = $chug->name;
                } else {
                    $underMin .= ", $chug->name";
                }
                $underMin .= " (-" . strval($amtUnder) . ")";
            }
            if ($chug->assigned_count > $chug->max_size) {
                $amtOver = $chug->assigned_count - $chug->max_size;
                if (empty($overMax)) {
                    $overMax = $chug->name;
                } else {
                    $overMax .= ", $chug->name";
                }
                $overMax .= " (+" . strval($amtOver) . ")";
            }
        }
    }
    
    function genFatalErrorReport($errorList, $fixOnSamePage = FALSE,
                                 $backUrl = NULL) {
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
            return NULL;
        } else if ($ec == 1) {
            $desc = "An error";
        }
        $retVal = <<<EOM
<div class="error_box">
<h3>Oops!  $desc occurred:</h3>
EOM;
        $backText = "Back";
        if ($backUrl) {
            $backText = "<a href=\"$backUrl\">here</a>";
        }
        $retVal = $retVal . $errorHtml;
        if ($fixOnSamePage) {
            $retVal = $retVal . "<p>Please fix the errors and try again.</p></div>";
        } else {
            $retVal = $retVal . "<p>Please click $backText to try again, or report the error to an administrator if it persists.</p></div>";
        }
        $retVal = $retVal . footerText();
        $retVal = $retVal . "<img id=\"bottom\" src=\"images/bottom.png\" alt=\"\"></body></html>";
        
        return $retVal;
    }
    
    function genPickListForm($id2Name, $name, $tableName) {
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
        $retVal = <<<EOM
<form id="$formName" class="appnitro" method="post">
<div class="form_description">
<h3>$ucPlural</h3></div>
<ul><li>
<div>
<select class="element select medium" id="$idCol" name="$idCol">
<option value="" disabled=disabled selected>---</option>
EOM;
        foreach ($id2Name as $itemId => $itemName) {
            $retVal  = $retVal . "<option value=\"$itemId\">$itemName</option>";
        }
        $formEnd = <<<EOM
</select>
<p class="guidelines"><small>To add or delete $article $ucName, choose from the drop-down list and click Edit or Delete.  Click Add to add a new $ucName. $edahExtraText</small></p>
<input type="hidden" name="fromStaffHomePage" id="fromStaffHomePage" value="1" />
<input class="button_text" type="submit" name="submit" value="Edit" formaction="$editUrl"/>
<input class="button_text" type="submit" name="submit" value="Delete" onclick="return confirm('Are you sure you want to delete this $ucName?')" formaction="$deleteUrl"/>
EOM;
        $retVal = $retVal . $formEnd;
        if ($name == "edah") {
            $camperUrl = urlIfy("viewCampersByEdah.php");
            $retVal =
                $retVal . "<input class=\"button_text\" style=\"color:red\" type=\"submit\" name=\"submit\" value=\"Show Campers\" formaction=\"$camperUrl\"/>";
        }
        $formEnd = <<<EOM
</li>
<li>
</form>
<form>
<input type=button onClick="location.href='$addUrl'" value='Add New $ucName'>
</form>
</li></ul>
</div>
EOM;
        $retVal = $retVal . $formEnd;
        
        return $retVal;
    }
    
    function genPickList($id2Name, $selected_id, $name) {
        $ucName = ucfirst($name);
        $retVal = "<option value=\"\" >-- Choose $ucName --</option>";
        asort($id2Name);
        foreach ($id2Name as $id => $name) {
            $selStr = "";
            if ($id == $selected_id) {
                $selStr = "selected";
            }
            $retVal  = $retVal . "<option value=\"$id\" $selStr>$name</option>";
        }
        return $retVal;
    }
    
    function genCheckBox($id2Name, $activeIds, $arrayName) {
        $retVal = "";
        asort($id2Name);
        foreach ($id2Name as $id => $name) {
            $selStr = "";
            $idStr = strval($id); // Use strings in forms, for consistency.
            if (array_key_exists($idStr, $activeIds)) {
                $selStr = "checked=checked";
            }
            $retVal = $retVal . "<input type=\"checkbox\" name=\"${arrayName}[]\" value=\"$idStr\" $selStr />$name<br>";
        }
        return $retVal;
    }
    
    function test_input($data) {
        if (empty($data)) {
            return NULL;
        }        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    function urlBaseText() {
        $scheme = "http";
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
            $scheme = "https";
        }
        $localUrl = "/";
        $lastSlashPos = strrpos($_SERVER["PHP_SELF"], "/"); // Note that we're reverse-searching.
        if ($lastSlashPos != FALSE) {
            // Remove everything after the last slash (keep the slash).
            $localUrl = substr($_SERVER["PHP_SELF"], 0, $lastSlashPos + 1);
        }
        // Return the local URL minus everything after the last slash.
        return $scheme . "://" . $_SERVER['HTTP_HOST'] . $localUrl;
    }
    
    function urlIfy($localLink) {
        return urlBaseText() . $localLink;
    }
    
    function adminLoggedIn() {
        return isset($_SESSION['admin_logged_in']);
    }
    
    function camperLoggedIn() {
        return isset($_SESSION['camper_logged_in']);
    }
    
    function baseUrl() {
        return urlIfy("index.php");
    }

    function homeUrl() {
        if (isset($_SESSION['admin_logged_in'])) {
            return urlIfy("staffHome.php");
        } else {
            return urlIfy("camperHome.php");
        }
    }
    
    function homeAnchor($text = "home") {
        $homeUrl = homeUrl();
        return "<a href=\"$homeUrl\">$text</a>";
    }
    
    function staffHomeAnchor($text = "home") {
        $homeUrl = urlIfy("staffHome.php");
        return "<a href=\"$homeUrl\">$text</a>";
    }
    
    function loginRequiredMessage() {
        $retVal = "";
        if (! isset($_SESSION['admin_logged_in'])) {
            $retVal = "<font color=\"red\"><b>Login required!</b></font><br>The page you are " .
            "accessing requires that you log in with the admin password.<br>";
        }
        return $retVal;
    }
    
    function bounceToLogin() {
        if (! isset($_SESSION['admin_logged_in'])) {
            $fromUrl = $_SERVER["PHP_SELF"];
            $redirUrl = urlIfy("staffLogin.php?from=$fromUrl");
            header("Location: $redirUrl");
            exit();
        }
    }
    
    function camperBounceToLogin() {
        if ((! isset($_SESSION['camper_logged_in'])) &&
            (! isset($_SESSION['admin_logged_in']))) {
            $fromUrl = $_SERVER["PHP_SELF"];
            $redirUrl = urlIfy("index.php?retry=1");
            header("Location: $redirUrl");
            exit();
        }
    }
    
    function fromBounce() {
        $qs = htmlspecialchars($_SERVER['QUERY_STRING']);
        $parts = explode("=/", $qs);
        if (count($parts) == 2 &&
            $parts[0] == "from") {
            return TRUE;
        }
        return FALSE;
    }
    
    function bouncePastIfLoggedIn($localLink) {
        if (isset($_SESSION['admin_logged_in'])) {
            $redirUrl = urlIfy($localLink);
            header("Location: $redirUrl");
            exit();
        }
    }
    
    function staffBounceBackUrl() {
        $url = urlBaseText() . "staffHome.php"; // Default staff redirect
        $parts = array();
        $qs = htmlspecialchars($_SERVER['QUERY_STRING']);
        $qs_from_post = test_input($_POST['query_string']);
        $qs_from_post = preg_replace("/&#?[a-z0-9]+;/i","", $qs_from_post);
        if (! empty($qs)) {
            $parts = explode("/", $qs);
        } else if (! empty($qs_from_post)) {
            $parts = explode("/", $qs_from_post);
        }
	if (count($parts) > 0) {
	   $len = count($parts);
           $url = urlBaseText() . $parts[$len - 1];
        }
        
        return $url;
    }
    
    function navText($bottom = FALSE) {
        $retVal = "";
        $baseUrl = baseUrl();
        $aclass = "nav_anchor";
        if ($bottom) {
            $aclass = "hnav_anchor";
        }
        $retVal .= "<a class=\"$aclass\" href=\"$baseUrl\">Site Home</a>";
        $homeUrl = homeUrl();
        if (adminLoggedIn()) {
            $retVal .= "<a class=\"$aclass\" href=\"$homeUrl\">Staff Home</a>";
            $camperUrl = urlIfy("camperHome.php");
            $retVal .= "<a class=\"$aclass\" href=\"$camperUrl\">Camper Home</a>";
        } else {
            $retVal .= "<a class=\"$aclass\" href=\"$homeUrl\">Camper Home</a>";
        }        
        $mysqli = connect_db();
        $sql = "SELECT camp_name, camp_web FROM admin_data";
        $result = $mysqli->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $campUrl = $row["camp_web"];
            $campName = $row["camp_name"];
            if ((! empty($campUrl)) &&
                (! empty($campName))) {
                $retVal .= "<a class=\"$aclass\" href=\"http://$campUrl/\">$campName Home</a>";
            }
        }
        $mysqli->close();
        
        return $retVal;
    }
    
    function footerText() {
            //$retVal = "<div class=\"hnav_container\">";
            //$retVal .= navText(TRUE);
            //$retVal .= "</div>";
        
        return "";
    }
    
    function headerText($title) {
        $navText = navText();
        $retVal = <<<EOM
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>$title</title>
<script type="text/javascript" src="meta/view.js"></script>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
</head>

<body id="main_body">
        
<div class="nav_container">
$navText
</div>
EOM;
        return $retVal;
    }
    
    function genErrorPage($err) {
        $retVal = headerText("Error Page");
        $retVal .= genFatalErrorReport(array($err));
        return $retVal;
    }
    
    function errorString($data) {
        return "<font color=\"red\">* $data</font><br>";
    }
    
    function dbErrorString($sql, $data) {
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
    
    function connect_db() {
        $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
        if (mysqli_connect_error()) {
            die('Connect Error: ('.mysqli_connect_errno().') '.mysqli_connect_error());
        }
        return $mysqli;
    }
    
    function yearOfUpcomingSummer() {
        $month = date('n'); // Month, 1-12
        if ($month >= 9) {
            // September or later: return next year
            return strval((intval(date('Y')) + 1));
        } else {
            // Jan-Aug: return current year.
            return date('Y');
        }
    }
    
    function genPassToEditPageForm($action, $paramHash) {
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
    
?>
