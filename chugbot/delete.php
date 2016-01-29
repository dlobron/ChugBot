<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    $dbErr = $tableNameErr = $itemIdErr = $idColErr = "";
    $comma_sep = $table_name = $item_id = $id_col = "";
    $deletedOk = FALSE;
    $mysqli = connect_db();
    
    $mysqli = connect_db();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $vals = split_input($_POST["name"], '||');
        if (count($vals) != 3) {
            $tableNameErr = errorString("Could not parse table name from input");
            $itemIdErr = errorString("Could not parse item ID from input");
            $idColErr = errorString("Could not parse ID column from input");
        } else {
            $item_id = $vals[0];
            $id_col = $vals[1];
            $table_name = $vals[2];
            if (empty($table_name)) {
                $tableNameErr = errorString("Missing table");
            }
            if (empty($item_id)) {
                $itemIdErr = errorString("Missing ID of item to be deleted");
            }
            if (empty($id_col)) {
                $idColErr = errorString("Missing ID column");
            }
        }
        if (empty($itemIdErr) &&
            empty($tableNameErr) &&
            empty($idColErr)) {
            // Do the deletion if we have all parameters.
            $sql = "DELETE FROM $table_name where $id_col = \"$item_id\"";
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

<?php
    echo headerText("Delete Item");
    
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



