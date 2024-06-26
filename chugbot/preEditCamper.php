<?php
session_start();
include_once 'dbConn.php';
include_once 'functions.php';
setup_camp_specific_terminology_constants();
checkLogout();

$db = new DbConn();
$sql = "SELECT enable_selection_process FROM admin_data";
$err = "";
$result = $db->runQueryDirectly($sql, $err);
$enableSelectionProcess = true;
if ($result) {
    $row = $result->fetch_assoc();
    if ($row) {
        $enableSelectionProcess = (bool)$row["enable_selection_process"];
    }
}

$thisPageUrl = urlIfy("preEditCamper.php");
$editPageUrl = urlIfy("editCamper.php");
$rankPageUrl = urlIfy("rankCamperChoices.html");
$notEnoughInputError = "";
$emailErr = "";
$dbErr = "";
$camperId2Name = array();
$camperId2Edah = array();
$edahId2Name = array();
$camperIdToEdit = null;
$camperNameToEdit = null;
$nextPage = null;

fillId2Name(null, $edahId2Name, $dbErr,
    "edah_id", "edot");

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $email = test_get_input("email"); // Email search is currently not displayed, but we support it here.
    $first = test_get_input("first");
    $last = test_get_input("last");
    $edah_id = test_get_input("edah_id");
    $nextPage = test_get_input("next_page"); // 1=edit, 2=rank
    $camperIdToEdit = test_get_input("camper_id");

    // Sanity checks.
    if (!empty($email) &&
        filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $emailErr = errorString("\"$email\" is not a valid email address.");
    }
    if (empty($email) && empty($first) && empty($last) && empty($edah_id) &&
        empty($camperIdToEdit)) {
        $notEnoughInputError = "Please choose at least one search term.";
    }

    if (empty($emailErr) && empty($notEnoughInputError)) {
        $db = new DbConn();
        $db->isSelect = true;
        $db->addSelectColumn("camper_id");
        $db->addSelectColumn("first");
        $db->addSelectColumn("last");
        $db->addSelectColumn("edah_id");
        // Search by whichever terms we were given.
        $orderBy = null;
        if (!empty($email)) {
            $db->addWhereColumn("email", $email, 's');
        }
        if (!empty($first)) {
            $db->addWhereColumn("first", $first, 's');
            $orderBy = "ORDER BY first";
        }
        if (!empty($last)) {
            $db->addWhereColumn("last", $last, 's');
            $orderBy = "ORDER BY last";
            if (!empty($first)) {
                $orderBy .= ", first";
            }
        }
        if (!empty($edah_id)) {
            $db->addWhereColumn("edah_id", $edah_id, 'i');
        }
        if (!is_null($orderBy)) {
            $db->addOrderByClause($orderBy);
        }
        $result = $db->simpleSelectFromTable("campers", $dbErr);
        if ($result == false) {
            error_log(dbErrorString($sql, $dbErr));
        } else {
            $first = null;
            while ($row = mysqli_fetch_assoc($result)) {
                $camperId2Name[$row["camper_id"]] = $row["last"] . ", " . $row["first"];
                $camperId2Edah[$row["camper_id"]] = $edahId2Name[$row["edah_id"]];
                $first = $row["first"];
            }
            if (count($camperId2Name) == 1) {
                $camperIdToEdit = key($camperId2Name);
                $camperNameToEdit = $first;
            }
        }
        mysqli_free_result($result);
    }
}
// Special case: if exactly one camper ID was found, or supplied, and we
// have our destination page, we can redirect.
if ($camperIdToEdit && $nextPage) {
    if ($nextPage == 1) {
        $editPageUrl .= "?eid=" . $camperIdToEdit;
        header('Location: ' . $editPageUrl);
    } else {
        $_SESSION["camper_id"] = $camperIdToEdit;
        header('Location: ' . $rankPageUrl);
    }
    die();
}

echo headerText("Choose Edit");
?>



<?php
// If the email was invalid, display an error and "hit back button".  If no rows, report that no campers matched
// and offer an Add link.
$camperHomeUrl = urlIfy("camperHome.php");
$errText = genFatalErrorReport(array($dbErr, $emailErr, $notEnoughInputError),
    false,
    $camperHomeUrl);
if (!is_null($errText)) {
    echo $errText;
    exit();
}
if (count($camperId2Name) == 0) {
    $errText = "No campers were found - please try again. Tip: you only need to enter one term - the system will find all matches.";
    echo genFatalErrorReport(array($errText),
        false,
        $camperHomeUrl);
    exit();
}

echo "<div class=\"card card-body mt-3 container\">";
echo "<h1><a>Choose Edit</a></h1>";
echo "<form method=\"get\" action=\"$thisPageUrl\"/>";
// If we found more than one camper for the search items, display a drop-down
// list to select.
if (count($camperId2Name) > 1) {
    echo "<div class=\"page-header\">";
    echo "<h4>Your search matched more than one camper! Please choose your camper profile from the list of matches.</h4>";
    echo "</div>";
    echo "<ul>";
    echo "<li>";
    echo "<select class=\"form-select element medium\" id=\"camper_id\" name=\"camper_id\">";
    asort($camperId2Name);
    foreach ($camperId2Name as $camperId => $camperName) {
        $edah = "-";
        if (array_key_exists($camperId, $camperId2Edah)) {
            $edah = $camperId2Edah[$camperId];
        }
        echo ("<option value=\"$camperId\" >$camperName ($edah)</option>");
    }
    echo "</select>";
    echo "<p class=\"guidelines\" id=\"guide_camper\"><small>Choose your camper name.</small></p>";
    echo "</li>";
}

// Let the user choose either to update their info or proceed directly to
// the chugim ranking.
echo "<li>";
if (!$camperNameToEdit) { echo "<h3>Wecome!</h3>";}
else { echo "<h3>Welcome, $camperNameToEdit!</h3>"; }
echo "<p>You can update your personal data, or go directly to the " . chug_term_singular . " ranking page - please choose one.</p>";
if ($nextPage === null ||
    $nextPage == 1) {
    echo "<label class=\"form-check-label\"><input class=\"form-check-input me-2\" type=\"radio\" name=\"next_page\" value=1 checked>Update Personal Data</label><br>";
} else {
    echo "<label class=\"form-check-label\"><input class=\"form-check-input me-2\" type=\"radio\" name=\"next_page\" value=1>Update Personal Data</label><br>";
}

if ($enableSelectionProcess) {
    if ($nextPage == 2) {
        echo "<label class=\"form-check-label\"><input class=\"form-check-input me-2\" type=\"radio\" name=\"next_page\" value=2 checked>Update " . ucfirst(chug_term_plural) . "</label><br>";
    } else {
        echo "<label class=\"form-check-label\"><input class=\"form-check-input me-2\" type=\"radio\" name=\"next_page\" value=2>Update " . ucfirst(chug_term_plural) . "</label><br>";
    }
}
echo "<p class=\"guidelines\"><small>Choose Update Personal Data to edit personal info, or Update " . ucfirst(chug_term_plural) . " to proceed directly to " .
    "the " . chug_term_singular . " ranking page.</small></p>";
echo "</li>";
echo "</ul>";
echo "<input type=\"hidden\" id=\"fromHome\" name=\"fromHome\" value=\"1\" />";
if ($camperIdToEdit) {
    echo "<input type=\"hidden\" name=\"camper_id\" value=\"$camperIdToEdit\" />";
}
echo "<input class=\"btn btn-success mt-3\" id=\"saveForm\" type=\"submit\" name=\"submit\" value=\"Go\" />";

echo "</form>";

?>

</div>

<?php
echo footerText();
?>

</body>
</html>
