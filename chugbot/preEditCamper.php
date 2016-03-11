<?php
    session_start();
    include 'functions.php';
    
    echo headerText("Choose Camper to Edit");

    $emailErr = "";
    $dbError = FALSE;
    $camperId2Name = array();
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = test_input($_POST["email"]);
        if (empty($email)) {
            $emailErr = errorString("No email address was submitted.");
        } else if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = errorString("\"$email\" is not a valid email address.");
        }
        if (empty($emailErr)) {
            // Grab the campers who are associated with this email, and store them
            // associatively by ID in $camperId2Name.
            $mysqli = connect_db();
            $sql = "SELECT camper_id, first, last FROM campers WHERE email=\"$email\"";
            $result = $mysqli->query($sql);
            if ($result == FALSE) {
                echo(dbErrorString($sql, $mysqli->error));
                $dbError = TRUE;
            } else {
                while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                    $camperId2Name[$row[0]] = $row[1] . " " . $row[2];
                }
            }
            mysqli_free_result($result);
        }
    }
    ?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Choose Camper to Edit</a></h1>

<?php
    // If the email was invalid, display an error and "hit back button".  If no rows, report that no campers matched
    // and offer an Add link.  Otherwise, list each camper in the form, with the submit going to
    // the edit page and camper_id set to the camper's ID.
    if (! empty($emailErr)) {
        echo "<h3>An Error Occurred</h3>";
        echo "$emailErr<br>";
        echo "<p>Please hit the \"Back\" button and try again.</p>";
    } else if (count($camperId2Name) == 0) {
        $addUrl = urlIfy("addCamper.php");
        $camperHomeUrl = urlIfy("camperHome.php");
        echo "<h3>An Error Occurred</h3>";
        echo "No camper assignments were found for email $email.<br>";
        echo "You can click <a href=\"$addUrl\">here</a> to add a camper, or <a href=\"$camperHomeUrl\">here</a> to return to the home page.";
    } else {
        // List campers in a form.
        $editPageUrl = urlBaseText() . "editCamper.php";
        echo "<form id=\"pickCamperForm\" class=\"appnitro\" method=\"post\" action=\"$editPageUrl\"/>";
        echo "<div class=\"form_description\">";
        echo "<h3>Choose a Camper Associated with $email to Edit</h3>";
        echo "</div>";
        echo "<ul>";
        echo "<li>";
        echo "<select class=\"element select medium\" id=\"camper_id\" name=\"camper_id\">";
        foreach ($camperId2Name as $camperId => $camperName) {
            echo("<option value=\"$camperId\" >$camperName</option>");
        }
        echo "</select>";
        echo "<p class=\"guidelines\" id=\"guide_camper\"><small>Please choose the camper you want to edit, then click \"Edit Camper\".</small></p>";
        echo "</li>";
        echo "</ul>";
        
        echo "<input type=\"hidden\" id=\"fromHome\" name=\"fromHome\" value=\"1\" />";
        echo "<input class=\"control_button\" id=\"saveForm\" class=\"button_text\" type=\"submit\" name=\"submit\" value=\"Edit Camper\" />";
        echo "</form>";
    }
    
    ?>

</div>

<?php
    echo footerText();
?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
