<?php
    session_start();
    include_once 'functions.php';
    include_once 'dbConn.php';
    bounceToLogin();
    
    $dbErr = $itemIdErr = $qsErr = "";
    $comma_sep = $table_name = $item_id = $id_col = "";
    $deletedOk = FALSE;
    $mysqli = connect_db();
    
    $parts = explode("&", $_SERVER['QUERY_STRING']); // Expect: idCol=$idcol&tableName=$tableName
    if (count($parts) != 2) {
        $qsErr = errorString("Bad query string");
    }
    foreach ($parts as $part) {
        $cparts = explode("=", $part);
        if (count($cparts) != 2) {
            $qsErr = errorString("Bad query string");
            break;
        }
        if ($cparts[0] == "idCol") {
            $id_col = $cparts[1];
        } else if ($cparts[0] == "tableName") {
            $table_name = $cparts[1];
        } else {
            $qsErr = errorString("Bad query string");
            break;
        }
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $item_id = test_input($_POST["$id_col"]);
        if (empty($item_id)) {
            $itemIdErr = errorString("Could not parse item ID from input");
        }
        if (empty($itemIdErr) &&
            empty($qsErr)) {
            // Do the deletion if we have all parameters.
            $db = new DbConn();
            $db->addWhereColumn($id_col, $item_id, 'i');
            $deletedOk = $db->deleteFromTable($table_name, $dbErr);
        }
    }
    
    $mysqli->close();
    
    ?>

<?php
    echo headerText("Delete Item");
    
    $errText = genFatalErrorReport(array($dbErr, $qsErr, $itemIdErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<?php
    if ($deletedOk) {
        $homeUrl = urlIfy("staffHome.php");
        echo "<div class=\"centered_container\">";
        echo "<h3>Deletion Successful!</h3>";
        echo "<p>Deletion successful.  Please click <a href=\"$homeUrl\">here</a> to go back, or wait to be redirected.<p>";
        echo "</div>";
        echo "<script type=\"text/javascript\">";
        echo "setTimeout(function () { window.location.href= '$homeUrl'; },1000);";
        echo "</script>";
    }
    ?>

<?php
    echo footerText();
    ?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>



