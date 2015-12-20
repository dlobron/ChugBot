<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Choose a Camper to Edit</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php
    session_start();
    include 'functions.php';
?>

<body id="main_body" >

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Choose Camper to Edit</a></h1>
<h3>Choose Camper to Edit</h3>

<?php
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

<?php
    // If the email was invalid, display an error and "hit back button".  If no rows, report that no campers matched
    // and offer an Add link.  Otherwise, list each camper in the form, with the submit going to
    // the edit page and camper_id set to the camper's ID.
    if (! empty($emailErr)) {
        echo "$emailErr<br>";
        echo "<p>Please hit the \"Back\" button and try again.</p>";
    } else if (count($camperId2Name) == 0) {
        $urlBase = urlBaseText();
        echo "No camper assignments were found for email $email.<br>";
        echo "You can click <a href=\"$urlBase/addCamper.php\">here</a> to add a camper, or <a href=\"$urlBase/camperHome.php\">here</a> to try again.";
    } else {
        // List campers in a form.
        $editPageUrl = urlBaseText() . "editCamper.php";
        echo "<form id=\"pickCamperForm\" class=\"appnitro\" method=\"post\" action=\"$editPageUrl\"/>";
        echo "<div class=\"form_description\">";
        echo "<ul>";
        echo "<li>";
        echo "<div>";
        echo "<select class=\"element select medium\" id=\"camper_id\" name=\"camper_id\">";
        foreach ($camperId2Name as $camperId => $camperName) {
            echo("<option value=\"$camperId\" >$camperName</option>");
        }
        echo "</select>";
        echo "</div><p class=\"guidelines\" id=\"guide_camper\"><small>Please choose the camper you want to edit, then click \"Edit Camper\".</small></p>";
        echo "</li>";
        echo "</ul>";
        
        echo "<input type=\"hidden\" id=\"fromHome\" name=\"fromHome\" value=\"1\" />";
        echo "<input id=\"saveForm\" class=\"button_text\" type=\"submit\" name=\"submit\" value=\"Edit Camper\" />";
        echo "</form>";
    }
    
    ?>

<div id="footer">
<?php
    echo footerText();
?>
</div>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
