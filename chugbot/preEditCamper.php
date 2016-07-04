<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';

    $editPageUrl = urlIfy("editCamper.php");
    $notEnoughInputError = "";
    $emailErr = "";
    $dbError = "";
    $camperId2Name = array();
    $haveNameInfo = FALSE;
    $edahName = "the selected edah"; // Default
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $email = test_input($_GET["email"]);
        $first = test_input($_GET["first"]);
        $last = test_input($_GET["last"]);
        $edah_id = test_input($_GET["edah_id"]);
        if (! empty($first) && ! empty($last) && ! empty($edah_id)) {
            $haveNameInfo = TRUE;
        }
        if (! empty($email) &&
            filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
            $emailErr = errorString("\"$email\" is not a valid email address.");
        }
        if (! empty($edah_id)) {
            // Get the name for the edah, if any, in case we need to display
            // an error message.
            $db = new DbConn();
            $db->isSelect = TRUE;
            $db->addSelectColumn("name");
            $db->addWhereColumn("edah_id", $edah_id, 'i');
            $err = "";
            $result = $db->simpleSelectFromTable("edot", $err);
            if ($result) {
                $row = $result->fetch_row();
                $edahName = $row[0];
            }
            mysqli_free_result($result);
        }
        // In order to find a camper, we need either an email address or the
        // camper's name and edah.
        if (empty($email) &&
            ! $haveNameInfo) {
            $notEnoughInputError = "To edit a camper, you must give either the email for that camper or the camper's name and edah.";
        }
        
        if (empty($emailErr) &&
            empty($notEnoughInputError)) {
            if (! empty($email)) {
                // If we got an email address, grab the camper(s) associated with it,
                // and store them associatively by ID in $camperId2Name.
                $db = new DbConn();
                $db->isSelect = TRUE;
                $db->addSelectColumn("camper_id");
                $db->addSelectColumn("first");
                $db->addSelectColumn("last");
                $db->addWhereColumn("email", $email, 's');
                $err = "";
                $result = $db->simpleSelectFromTable("campers", $err);
                if ($result == FALSE) {
                    error_log(dbErrorString($sql, $err));
                    $dbError = TRUE;
                } else {
                    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                        $camperId2Name[$row[0]] = $row[1] . " " . $row[2];
                    }
                }
                mysqli_free_result($result);
            }
            if ($haveNameInfo &&
                count($camperId2Name) == 0) {
                // As above, but select camper ID by first, last, and edah.  We
                // only use the name if the email did not find any campers.
                $db = new DbConn();
                $db->isSelect = TRUE;
                $db->addSelectColumn("camper_id");
                $db->addWhereColumn("first", $first, 's');
                $db->addWhereColumn("last", $last, 's');
                $db->addWhereColumn("edah_id", $edah_id, 'i');
                $err = "";
                $result = $db->simpleSelectFromTable("campers", $err);
                if ($result == FALSE) {
                    error_log(dbErrorString($sql, $err));
                    $dbError = TRUE;
                } else {
                    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                        $camperId2Name[$row[0]] = $first . " " . $last;
                    }
                }
                mysqli_free_result($result);
            }
        }
    }
    // Special case: if exactly one camper ID was found, we can redirect immediately
    // to edit that camper.
    if (count($camperId2Name) == 1) {
        $editPageUrl .= "?eid=" . key($camperId2Name);
        header('Location: ' . $editPageUrl);
        die();
    }
  
    echo headerText("Choose Camper to Edit");
?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Choose Camper to Edit</a></h1>

<?php
    // If the email was invalid, display an error and "hit back button".  If no rows, report that no campers matched
    // and offer an Add link.  Otherwise, list each camper in the form, with the submit going to
    // the edit page and camper_id set to the camper's ID.
    $camperHomeUrl = urlIfy("camperHome.php");
    $errText = genFatalErrorReport(array($dbErr, $emailErr, $notEnoughInputError));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    if (count($camperId2Name) == 0) {
        $errText = "";
        if (! empty($email)) {
            $errText = "No campers were found for email $email";
            if ($haveNameInfo) {
                $errText .= ", or named $first $last in $edahName.";
            } else {
                $errText .= ".";
            }
        } else {
             $errText = "No campers named $first $last were found in $edahName.";
        }        
        echo genFatalErrorReport(array($errText),
                                 FALSE,
                                 $camperHomeUrl);
        exit();
    } else {
        // At this point, we've got more than one
        echo "<form id=\"pickCamperForm\" class=\"appnitro\" method=\"post\" action=\"$editPageUrl\"/>";
        echo "<div class=\"form_description\">";
        if (! empty($email)) {
            echo "<h3>Choose a Camper Associated with $email to Edit</h3>";
        } else {
            // If more than one camper is found with the same name and edah,
            // note this here and just show the drop-down.
            $ct = count($camperId2Name);
            echo "<h3>$ct campers named $first $last were found! Please choose one to edit, and check his/her details to see if it's you!</h3>";
        }
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
