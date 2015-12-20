<?php
    include 'constants.php';
    
    function genSuccessMessage ($message) {
        if (empty($message)) {
            return "";
        }
        $retVal = "<div id=\"centered_container\">";
        $retVal = $retVal . $message;
        $retVal = $retVal . "</div>";
        return $retVal;
    }
    
    function getCamperRowForId(&$mysqli, $camper_id, &$dbErr, &$idErr) {
        $camperIdNum = intval($camper_id);
        $sql = "select * from campers where camper_id = $camperIdNum";
        $retVal = array();
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $dbErr = dbErrorString($sql, $mysqli->error);
        } else if ($result->num_rows != 1) {
            $camperIdErr = errorString("camper ID $camper_id not found");
        } else {
            $retVal =  $result->fetch_array(MYSQLI_NUM);
        }
        mysqli_free_result($result);
        
        return $retVal;
    }
    
    function genFatalErrorReport($errorList) {
        $errorHtml = "";
        $ec = 0;
        foreach ($errorList as $errorText) {
            if (empty($errorText)) {
                continue;
            }
            $errorHtml = $errorHtml . $errorText;
            $ec = $ec + 1;
        }
        $desc = "Errors";
        if ($ec == 0) {
            return NULL;
        } else if ($ec == 1) {
            $desc = "An error";
        }
        $retVal = <<<EOM
<div id="error_box">
<h3>Oops!  $desc occurred:</h3>
EOM;
        $retVal = $retVal . $errorHtml . "</div>";
        $retVal = $retVal . "<p>Please hit \"Back\" and try again, or report the error to an administrator if it persists.</p>";
        $retVal = $retVal . "<div id=\"footer\">";
        $retVal = $retVal . footerText();
        $retVal = $retVal . "</div><img id=\"bottom\" src=\"images/bottom.png\" alt=\"\"></body></html>";
        
        return $retVal;
    }
    
    function populateActiveInstances(&$mysqli,
                                     &$activeIdMap,
                                     &$dbErr,
                                     $keyCol,
                                     $keyVal,
                                     $valCol,
                                     $table) {
        $sql = "SELECT $valCol from $table where $keyCol = $keyVal";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $dbErr = dbErrorString($sql, $mysqli->error);
        } else {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $activeIdMap[$row[0]] = 1;
            }
            mysqli_free_result($result);
        }
    }
    
    function updateBlockInstances(&$mysqli,
                                  $sessionIdsForBlock,
                                  &$submitOk,
                                  &$dbErr,
                                  $blockIdNum) {
        $sql = "DELETE FROM block_instances WHERE block_id = $blockIdNum";
        $submitOk = $mysqli->query($sql);
        if ($submitOk == FALSE) {
            $dbErr = dbErrorString($sql, $mysqli->error);
        }
        foreach ($sessionIdsForBlock as $sessionId => $active) {
            if (! empty($dbErr)) {
                break;
            }
            $sessionIdNum = intval($sessionId);
            $sql = "INSERT INTO block_instances (block_id, session_id) VALUES ($blockIdNum, $sessionIdNum)";
            $submitOk = $mysqli->query($sql);
            if ($submitOk == FALSE) {
                $dbErr = dbErrorString($sql, $mysqli->error);
            }
        }
    }
    
    function genPickListForm(&$id2Name, $name, $plural = "") {
        if (empty($plural)) {
            $plural = $name . "s";
        }
        $ucName = ucfirst($name);
        $ucPlural = ucfirst($plural);
        $formName = "form_" . $name;
        $editUrl = urlIfy("edit" . $ucName . ".php");
        $addUrl = urlIfy("add" . $ucName . ".php");
        $deleteUrl = urlIfy("delete.php");
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
<select class="element select medium" id="name" name="name">
<option value="" disabled=disabled selected>---</option>
EOM;
        foreach ($id2Name as $itemId => $itemName) {
            $csVal = "$itemName,$plural"; # Table name is plural.
            $retVal  = $retVal . "<option value=\"$csVal\">$itemName</option>";
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
    
    function fillId2Name(&$mysqli, &$id2Name, &$dbErr,
                         $idColumn, $table) {
        $sql = "SELECT $idColumn, name FROM $table";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $dbErr = dbErrorString($sql, $mysqli->error);
        } else {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $id2Name[$row[0]] = $row[1];
            }
        }
        mysqli_free_result($result);
    }
    
    function test_input($data, $nosplit = FALSE) {
        if (empty($data)) {
            return $data;
        }
        
        // If the data is comma-separated, use only the first value, unless $nosplit
        // is set.
        if (! $nosplit) {
            $arr = explode(',', $data);
            $data = $arr[0];
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    function csv_input($data) {
        $tested = test_input($data, TRUE); // Test, but don't split yet.
        if (empty($tested)) {
            return array();
        }
        
        return explode(',', $tested);
    }
    
    function urlBaseText() {
        $scheme = "http";
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
            $scheme = "https";
        }
        return $scheme . "://" . $_SERVER['HTTP_HOST'] . "/chugbot/";
    }
    
    function urlIfy($localLink) {
        return urlBaseText() . $localLink;
    }
    
    function homeUrl() {
        return urlIfy("index.php");
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
    
    function footerText() {
        $homeUrl = homeUrl();
        return
            "<a href=\"http://www.campramahne.org/\">CRNE home</a><br><a href=\"$homeUrl\">ChugBot home</a>";
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
        $retVal = $retVal . "Error: " . $sql . "<br>";
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
        $retVal = "<form action=\"$action\" method=\"post\" name=\"passToEditPageForm\">";
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
        
        // TODO: Add noscript link or switch to HTML5.

        return $retVal;
    }
    
?>
