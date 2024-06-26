<?php
session_start();
include_once 'dbConn.php';
include_once 'functions.php';
bounceToLogin();
checkLogout();
setup_camp_specific_terminology_constants();

$dbErr = "";
$deleteOk = true;
$db = new DbConn();
$result = $db->runQueryDirectly("SELECT delete_ok FROM category_tables WHERE name = \"campers\"", $dbErr);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $deleteOk = intval($row["delete_ok"]);
}
$camperId2Name = array();
$camperId2Edah = array();
$edahId2Name = array();
fillId2Name(null, $edahId2Name, $dbErr, "edah_id", "edot");

$forEdahText = "all edot";
$sql = "SELECT c.camper_id camper_id, c.first first, c.last last, e.name edah_name, e.sort_order edah_sort_order FROM campers c, edot e WHERE c.edah_id = e.edah_id";
$edah_id = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $edah_id = test_post_input("edah_id");
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $edah_id = test_get_input("edah_id");
}
if ($edah_id) {
    $sql .= " AND e.edah_id = \"$edah_id\"";
    $forEdahText = $edahId2Name[$edah_id];
}
$sql .= " ORDER BY last, edah_sort_order, edah_name";
$db = new DbConn();
$result = $db->runQueryDirectly($sql, $dbErr);
while ($row = mysqli_fetch_assoc($result)) {
    $camperId = $row["camper_id"];
    $last = $row["last"];
    $first = $row["first"];
    $camperId2Edah[$camperId] = $row["edah_name"];
    $camperId2Name[$camperId] = "$last, $first";
}
?>

<?php
echo headerText("View Campers");

$errText = genFatalErrorReport(array($dbErr));
if (!is_null($errText)) {
    echo $errText;
    exit();
}
?>

<div class="container card card-body mt-3">
<h2>Campers for <?php echo $forEdahText; ?></h2>
<p>This page lists campers in <?php echo $forEdahText; ?> who have entered <?php echo chug_term_singular ?> preferences.  To update
information or settings for a camper, click the Edit button next to that camper's name.  To return to the staff admin
page, click <?php echo staffHomeAnchor(); ?>.</p>
</div>

<br>
<div class="container card card-body mt-3 mb-3">
<?php
if (count($camperId2Name) == 0) {
    if ($edah_id) {
        echo "<h3>No $forEdahText campers were found in the system.</h3>";
    } else {
        echo "<h3>No campers were found in the system.</h3>";
    }
} else {
    asort($camperId2Name);
    $editUrl = urlIfy("editCamper.php");
    $deleteUrlBase = urlIfy("delete.php");
    foreach ($camperId2Name as $camperId => $camperName) {
        $editUrl = urlIfy("editCamper.php");
        $edahName = $camperId2Edah[$camperId];
        $deleteUrl = $deleteUrlBase . "?idCol=camper_id&tableName=campers&tryAgainPage=staffHome.php";
        echo "<form method=\"POST\" action=\"$editUrl\">";
        echo "<input type=hidden name=\"camper_id\" id=\"camper_id\" value=$camperId>";
        echo "<input type=hidden name=\"fromHome\" id=\"fromHome\" value=1>";
        echo "<p><input class=\"btn btn-outline-primary me-2\" type=\"submit\" name=\"submit\" value=\"Edit\">";
        if ($deleteOk) {
            echo "<input class=\"btn btn-outline-danger me-2\" type=\"submit\" name=\"delete\" value=\"Delete\" onclick=\"return confirm('Are you sure you want to remove this camper?')\" " .
                "formaction=\"$deleteUrl\"/>";
        }echo "$camperName ($edahName) &nbsp; &nbsp;</p>";
        
        echo "</form>";
    }
}
?>
</div>
</div>

<?php
echo footerText();
?>

</body>
</html>





