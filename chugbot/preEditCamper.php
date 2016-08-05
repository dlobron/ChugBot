<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';

    $editPageUrl = urlIfy("editCamper.php");
    $notEnoughInputError = "";
    $emailErr = "";
    $dbError = "";
    $camperId2Name = array();
    $camperId2Edah = array();
    $edahId2Name = array();
    
    fillId2Name(NULL, $edahId2Name, $dbError,
                "edah_id", "edot");
    
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $email = test_input($_GET["email"]);
        $first = test_input($_GET["first"]);
        $last = test_input($_GET["last"]);
        $edah_id = test_input($_GET["edah_id"]);
        
        // Sanity checks.
        if (! empty($email) &&
            filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
            $emailErr = errorString("\"$email\" is not a valid email address.");
        }
        if (empty($email) && empty($first) && empty($last) && empty($edah_id)) {
            $notEnoughInputError = "Please choose at least one search term.";
        }
        
        if (empty($emailErr) && empty($notEnoughInputError)) {
            $db = new DbConn();
            $db->isSelect = TRUE;
            $db->addSelectColumn("camper_id");
            $db->addSelectColumn("first");
            $db->addSelectColumn("last");
            $db->addSelectColumn("edah_id");
            // Search by whichever terms we were given.
            $orderBy = NULL;
            if (! empty($email)) {
                $db->addWhereColumn("email", $email, 's');
            }
            if (! empty($first)) {
                $db->addWhereColumn("first", $first, 's');
                $orderBy = "ORDER BY first";
            }
            if (! empty($last)) {
                $db->addWhereColumn("last", $last, 's');
                $orderBy = "ORDER BY last";
                if (! empty($first)) {
                    $orderBy .= ", first";
                }
            }
            if (! empty($edah_id)) {
                $db->addWhereColumn("edah_id", $edah_id, 'i');
            }
            if (! is_null($orderBy)) {
                $db->addOrderByClause($orderBy);
            }
            $err = "";
            $result = $db->simpleSelectFromTable("campers", $err);
            if ($result == FALSE) {
                error_log(dbErrorString($sql, $err));
                $dbError = TRUE;
            } else {
                while ($row = mysqli_fetch_assoc($result)) {
                    $camperId2Name[$row["camper_id"]] = $row["last"] . ", " . $row["first"];
                    $camperId2Edah[$row["camper_id"]] = $edahId2Name[$row["edah_id"]];
                }
            }
            mysqli_free_result($result);
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

<div class="form_container">
<h1><a>Choose Camper to Edit</a></h1>

<?php
    // If the email was invalid, display an error and "hit back button".  If no rows, report that no campers matched
    // and offer an Add link.  Otherwise, list each camper in the form, with the submit going to
    // the edit page and camper_id set to the camper's ID.
    $camperHomeUrl = urlIfy("camperHome.php");
    $errText = genFatalErrorReport(array($dbErr, $emailErr, $notEnoughInputError),
                                   FALSE,
                                   $camperHomeUrl);
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    if (count($camperId2Name) == 0) {
        $errText = "No campers were found - please try again. Tip: you only need to enter one term - the system will find all matches.";
        echo genFatalErrorReport(array($errText),
                                 FALSE,
                                 $camperHomeUrl);
        exit();
    } else {
        // At this point, we've got more than one
        echo "<form id=\"pickCamperForm\" class=\"appnitro\" method=\"post\" action=\"$editPageUrl\"/>";
        echo "<div class=\"form_description\">";
        echo "<h4>Your search matched more than one camper! Please choose your camper profile from the list of matches.</h4>";
        echo "</div>";
        echo "<ul>";
        echo "<li>";
        echo "<select class=\"element select medium\" id=\"camper_id\" name=\"camper_id\">";
        asort($camperId2Name);
        foreach ($camperId2Name as $camperId => $camperName) {
            $edah = "-";
            if (array_key_exists($camperId, $camperId2Edah)) {
                $edah = $camperId2Edah[$camperId];
            }
            echo("<option value=\"$camperId\" >$camperName ($edah)</option>");
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
