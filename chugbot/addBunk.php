<?php
    session_start();
    include_once 'functions.php';
    bounceToLogin();
    
    // define variables and set to empty values
    $name = "";
    $nameErr = $dbErr = "";
    $edahIdsForBunk = array();
    $edahId2Name = array();
    
    $mysqli = connect_db();
    fillId2Name($mysqli, $edahId2Name, $dbErr,
                "edah_id", "edot");
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = test_input($_POST["name"]);
        if (empty($name)) {
            $nameErr = errorString("Bunk name/number is required");
        }
        if (! empty($_POST['edah_ids'])) {
            foreach ($_POST['edah_ids'] as $edah_id) {
                $edahId = test_input($edah_id);
                if (empty($edahId)) {
                    continue;
                }
                $edahIdsForBunk[$edahId] = 1;
            }
        } 
        if (empty($nameErr) && empty($dbErr)) {
            $sql = "INSERT INTO bunks (name) VALUES (\"$name\")";
            $submitOk = $mysqli->query($sql);
            $bunkIdNum;
            if ($submitOk == FALSE) {
                $dbErr = dbErrorString($sql, $mysqli->error);
            } else {
                $bunkIdNum = $mysqli->insert_id;
                updateActiveInstances($mysqli,
                                      $edahIdsForBunk,
                                      $submitOk,
                                      $dbErr,
                                      "bunk_id",
                                      $bunkIdNum,
                                      "edah_id",
                                      "bunk_instances");
                $paramHash = array("name" => $name,
                                   "bunk_id" => $bunkIdNum,
                                   "edah_ids[]" => array_keys($edahIdsForBunk));
                echo(genPassToEditPageForm("editBunk.php", $paramHash));
            }

        }
    }
    
    $mysqli->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Add Bunk</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php
    $errText = genFatalErrorReport(array($dbErr, $nameErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<body id="main_body" >

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Add Bunk</a></h1>
<form id="form_1063620" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Add Bunk</h2>
<p>Please enter information for this bunk (<font color="red">*</font> = required field).</p>
<p>A bunk has a name or number (required), and can optionally be associated with one or more Edot.</p>
</div>
<ul>

<li id="li_1" >
<label class="description" for="name"><font color="red">*</font> Bunk Name/Number</label>
<div>
<input id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
<p class="guidelines" id="guide_1"><small>Choose a name or number for this bunk ("1", "12", "Tikvah Village", etc.)</small></p>
</div>
</li>

<li id="li_2" >
<label class="description" for="edah_ids"> Edot</label>
<div>
<?php
    echo genCheckBox($edahId2Name, $edahIdsForBunk, "edah_ids");
    ?>
</select>
</div><p class="guidelines" id="guide_2"><small>Associate this bunk with one or more Edot (optional).</small></p>
</li>

<li class="buttons">
<input type="hidden" name="fromAddPage" id="fromAddPage" value="1" />
<input type="hidden" name="form_id" value="1063620" />
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
<?php echo staffHomeAnchor("Cancel"); ?>
</li>
</ul>
</form>
<div id="footer">
<?php
    echo footerText();
    ?>
</div>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
