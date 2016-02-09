<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    $name = $edahName = "";
    $dbErr = $nameErr = "";
    $camperId2Name = array();
    $mysqli = connect_db();
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $edah_id = test_input($_POST["edah_id"]);
        if (empty($edah_id)) {
            $nameErr = errorString("Edah ID is required in order to find campers in edah.");
        }
        // Grab the camper IDs in this edah.
        $sql = "SELECT c.camper_id camper_id, c.first first, c.last last, e.name FROM campers c, edot e WHERE c.edah_id = e.edah_id and e.edah_id=\"$edah_id\"";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $dbErr = dbErrorString($sql, $mysqli->error);
        } else {
            while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
                $camperId = $row[0];
                $last = $row[2];
                $first = $row[1];
		$edahName = $row[3];
                $camperId2Name[$camperId] = "$last, $first";
            }
        }
    }

    $mysqli->close();
    ?>

<?php
    echo headerText("View Campers");
    
    $errText = genFatalErrorReport(array($dbErr, $nameErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<div class="centered_container">
<h1>View Campers</a></h1>
<h2>Campers for <?php echo $edahName; ?></h2>
<p>This page lists the <?php echo $edaName; ?> campers who have entered preferences in this system.  To update
information or settings for a camper, click the Edit button next to the camper's name.  To return to the staff admin
page, please click <?php echo staffHomeAnchor(); ?>.</p>
</div>

<br><br>
<div class="multi_form_container">
<?php
    if (count($camperId2Name) == 0) {
        echo "<h3>No $name campers were found in the system.</h3>";
    } else {
        asort($camperId2Name);
        $editUrl = urlIfy("editCamper.php");
        foreach ($camperId2Name as $camperId => $camperName) {
            $editUrl = urlIfy("editCamper.php");
            echo "<form class=\"appnitro\" method=\"post\" action=\"$editUrl\">";
            echo "<input type=hidden name=\"camper_id\" id=\"camper_id\" value=$camperId />";
            echo "<input type=hidden name=\"fromHome\" id=\"fromHome\" value=1 />";
            echo "<p>$camperName &nbsp; &nbsp; <input class=\"button_text\" type=\"submit\" name=\"submit\" value=\"Edit\" /></p>";
            echo "</form>";
        }
    }
    ?>
</div>

<div id="footer">
<?php
    echo footerText();
    ?>
</div>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>





