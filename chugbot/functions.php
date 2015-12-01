<?php
    define("MAX_SIZE_NUM", 10000);
    define("MIN_SIZE_NUM", -1);
    
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
    
    function genPickListForm(&$id2Name, $name, $staffHome, $plural = "") {
        if (empty($plural)) {
            $plural = $name . "s";
        }
        $ucName = ucfirst($name);
        $ucPlural = ucfirst($plural);
        $formName = "form_" . $name;
        $editUrl = urlIfy("edit" . $ucName . ".php");
        $addUrl = urlIfy("add" . $ucName . ".php");
        $article = "a";
        if (preg_match('/^[aeiou]/i', $name)) {
            $article = "an";
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
            $retVal  = $retVal . "<option value=\"$itemName\">$itemName</option>";
        }
        $formEnd = <<<EOM
</select>
<p class="guidelines"><small>Select $article $ucName from the drop-down list and click Edit, or click Add to add a new $ucName.</small></p>
<input type="hidden" name="fromStaffHomePage" id="fromStaffHomePage" value="$staffHome" />
<input class="button_text" type="submit" name="submit" value="Edit $ucName" formaction="$editUrl"/>
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
    
    function test_input($data) {
        if (empty($data)) {
            return $data;
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
        return $scheme . "://" . $_SERVER['HTTP_HOST'] . "/chugbot/";
    }
    
    function urlIfy($localLink) {
        return urlBaseText() . $localLink;
    }
    
    function homeUrl() {
        return urlIfy("index.php");
    }
    
    function homeAnchor() {
        $homeUrl = homeUrl();
        return "<a href=\"$homeUrl\">home</a>";
    }
    
    function staffHomeAnchor() {
        $homeUrl = urlIfy("staffHome.php");
        return "<a href=\"$homeUrl\">home</a>";
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
            $parts = explode("=/", $qs);
        } else if (! empty($qs_from_post)) {
            $parts = explode("=/", $qs_from_post);
        }
        if (count($parts) == 2 &&
            $parts[0] == "from") {
            $url = urlBaseText() . $parts[1];
        }
            
        return $url;
    }
    
    function footerText() {
        $homeUrl = homeUrl();
        return
            "<a href=\"http://www.campramahne.org/\">CRNE home</a><br><a href=\"$homeUrl\">ChugBot home</a>";
    }
    
    function errorString($data) {
        return "<font color=\"red\">* $data</font>";
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
        $retVal = "<font color=\"red\">$msg</font>";
        $retVal = $retVal . "Error: " . $sql . "<br>";
	return $retVal;
    }
    
    function connect_db() {
        $mysqli = new mysqli("localhost", "chugbot", "chugbot", "chugbot_db");
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