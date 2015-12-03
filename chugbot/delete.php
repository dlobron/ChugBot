<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    $dbErr = $tableNameErr  = $itemNameErr = "";
    $comma_sep = $table_name = $item_name = "";
    $deletedOk = FALSE;
    $mysqli = connect_db();
    
    $mysqli = connect_db();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $vals = csv_input($_POST["name"]);
        if (count($vals) != 2) {
            $tableNameErr = errorString("Could not parse table name from input (bad CSV)");
            $itemNameErr = errorString("Could not parse item name from input (bad CSV)");
        } else {
            $item_name = $vals[0];
            $table_name = $vals[1];
            if (empty($table_name)) {
                $tableNameErr = errorString("Missing table");
            }
            if (empty($item_name)) {
                $itemNameErr = errorString("Missing name of item to be deleted");
            }
        }
        if (empty($idErr) &&
            empty($tableNameErr) &&
            empty($tableNameErr)) {
            // Do the deletion if we have all parameters.
            $sql = "DELETE FROM $table_name where name = \"$item_name\"";
            $submitOk = $mysqli->query($sql);
            if ($submitOk == FALSE) {
                $dbErr = dbErrorString($sql, $mysqli->error);
            } else {
                $deletedOk = TRUE;
            }
        }
    }
    $mysqli->close();
    
    ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Object Deletion</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<body id="main_body" >

<?php
    $errText = genFatalErrorReport(array($dbErr, $tableNameErr, $itemNameErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<?php
    if ($deletedOk) {
        $homeUrl = urlIfy("staffHome.php");
        echo "<div id=\"centered_container\">";
        echo "<h3>Deletion Successsful!</h3>";
        echo "<p>You have successfully deleted $item_name.  Please click <a href=\"$homeUrl\">here</a> to go back, or wait to be redirected.<p>";
        echo "</div>";
        echo "<script type=\"text/javascript\">";
        echo "setTimeout(function () { window.location.href= '$homeUrl'; },2000);";
        echo "</script>";
    }
    ?>

<div id="footer">
<?php
    echo footerText();
    ?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>



