<?php
session_start();
include_once 'dbConn.php';
include_once 'functions.php';
bounceToLogin();
checkLogout();
setup_camp_specific_terminology_constants();

$fields = array(edah_term_singular, "session", "first_name", "last_name", "bunk", "email", "email2", "needs_first_choice");

// ensure camper importer is enabled
$enableCamperImporter = check_enabled("enable_camper_importer");
if (!$enableCamperImporter) {
    $redirUrl = urlIfy("../staffHome.php?from=camperUpload.php");
    header("Location: $redirUrl");
    exit();
}

// Check for a query string that signals a message.
$parts = explode("&", $_SERVER['QUERY_STRING']);
$message = null;
foreach ($parts as $part) {
    $cparts = explode("=", $part);
    if ($cparts[0] == "success") {
        $message = "<div class=\"alert alert-success\" role=\"alert\"><h4 class=\"alert-heading\">Campers successfully imported!</h4></div>";
        break;
    } else if ($cparts[0] == "campersWithErrors" && isset($_SESSION['campersWithErrors'])) {
        $message = "<div class=\"alert alert-danger\" role=\"alert\">";
        $message .= "<h4 class=\"alert-heading\">Error importing campers from CSV.</h4><p>Please try again, or escalate to an administrator.</p>";
        $message .= "<hr><p class=\"mb-0\">The following camper(s) had issues with importing:</p>";
        $camperString = implode("</li><li>", $_SESSION['campersWithErrors']);
        $message .= "<ul class=\"mb-0\"><li>" . $camperString . "</li></ul></div>";
        unset($_SESSION['campersWithErrors']);
        break;
    } else if ($cparts[0] == "missingHeader") {
        $message = "<div class=\"alert alert-danger\" role=\"alert\">";
        $message .= "<h4 class=\"alert-heading\">Error importing campers from CSV.</h4>";
        if (isset($cparts[1])) {
            $message .= "<hr><p class=\"mb-0\">The following column header is missing from the uploaded CSV:</p>";
            $message .= "<ul class=\"mb-1\"><li><b>" . urldecode($cparts[1]) . "</b></li></ul>";
            $message .= "<p class=\"mb-0\">Please make sure it is included and try again, or escalate to an administrator for more assistance";
        }
        $message .= "</div>";
        break;
    } else if ($cparts[0] == "downloadSample") {
        // orig adapted from https://stackoverflow.com/a/16251849
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="camperSample.csv";');
        $f = fopen('php://output', 'w');
        // add header rows
        fputcsv($f, $fields, ","); 
        // create some fake sample data
        $dbConn = new DbConn();
        $dbErr = "";
        $dbConn->isSelect = true;
        $sql = "SELECT e.name AS 'edah', s.name AS 'session', b.name AS 'bunk' FROM edot e, sessions s, bunks b, bunk_instances bi " . 
            "WHERE b.bunk_id = bi.bunk_id AND e.edah_id = bi.edah_id GROUP BY edah, session, bunk " . 
            "ORDER BY e.sort_order, s.name, bunk+0>0 DESC, bunk+0, LENGTH(bunk), bunk;";
        $result = $dbConn->doQuery($sql, $dbErr);
        if ($result == false) {
            error_log($dbErr);
        }
        $count = 1;
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            // sets 1 of every 4 sample campers to "needs_first_choice"
            $firstPref = null;
            if ($count % 4 == 0) {$firstPref = true;}
            // add sample camper to the output CSV
            fputcsv($f, array($row['edah'], $row['session'], "First $count", "Last $count", $row['bunk'], "example@example.com", null, $firstPref), ",");
            $count++;
        }
        exit();
    }
}

$dbConn = new DbConn();
$dbErr = "";
$dbConn->isSelect = true;
$sql = "SELECT * FROM edot ORDER BY sort_order";
$result = $dbConn->doQuery($sql, $dbErr);
if ($result == false) {
    error_log($dbErr);
}
$edah_name_to_id = [];
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $edah_name_to_id[$row["name"]] = $row["edah_id"];
}

$dbConn->isSelect = true;
$sql = "SELECT * FROM sessions";
$result = $dbConn->doQuery($sql, $dbErr);
if ($result == false) {
    error_log($dbErr);
}
$session_name_to_id = [];
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $session_name_to_id[$row["name"]] = $row["session_id"];
}

$dbConn->isSelect = true;
$sql = "SELECT * FROM bunks ORDER BY name+0>0 DESC, name+0, LENGTH(name), name";
$result = $dbConn->doQuery($sql, $dbErr);
if ($result == false) {
    error_log($dbErr);
}
$bunk_name_to_id = [];
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $bunk_name_to_id[$row["name"]] = $row["bunk_id"];
}

if ($dbErr) {
    echo headerText("Camper Upload Error");
    $tryAgainUrl = urlIfy("camperUpload.php");
    $errText = genFatalErrorReport(array($dbErr), false,
        $tryAgainUrl);
    echo $errText;
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_FILES["csv"]["tmp_name"])) {
    $csv = array_map("str_getcsv", file($_FILES["csv"]["tmp_name"]));
    array_walk($csv, function(&$a) use ($csv) {
        $a = array_combine(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv[0]), $a);
      });
    $header = $csv[0];
    array_shift($csv); # remove header row

    // ensure all headers are present and correct
    foreach($fields as $field) {
        if (!array_key_exists($field, $header)) {
            $redirUrl = urlBaseText() . "camperUpload.php?missingHeader=$field";
            header("Location: $redirUrl");
            exit();
        }
    }

    $dbConn->mysqliClient()->begin_transaction();
    $campersWithErrors = array();
    foreach($csv as $camper) {
        $edahId = null;
        $sessionId = null;
        $bunkId = null;
        if (array_key_exists($camper[edah_term_singular], $edah_name_to_id)) {
            $edahId = $edah_name_to_id[$camper[edah_term_singular]];
        } else {
            $error = "invalid " . edah_term_singular . ": '" . $camper[edah_term_singular] . "'";
        }
        if (array_key_exists($camper["session"], $session_name_to_id)) {
            $sessionId = $session_name_to_id[$camper["session"]];
        } else {
            $error = "invalid session: '" . $camper["session"] . "'";
        }
        if (array_key_exists($camper["bunk"], $bunk_name_to_id)) {
            $bunkId = $bunk_name_to_id[$camper["bunk"]];
        } else {
            $error = "invalid bunk: '" . $camper["bunk"] . "'";
        }
        $needsFirstChoice = !empty($camper["needs_first_choice"]);

        if (!$edahId || !$sessionId || !$bunkId) {
            array_push($campersWithErrors, $camper["first_name"] . " " . $camper["last_name"] . " ($error)");
            continue;
        }

        $stmt = $dbConn->mysqliClient()->prepare("INSERT INTO campers(edah_id, session_id, first, last, bunk_id, email, email2, needs_first_choice) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $edahId, $sessionId, $camper["first_name"], $camper["last_name"], $bunkId, $camper["email"], $camper["email2"], $needsFirstChoice);
        $stmt->execute();
    }

    if (count($campersWithErrors) > 0) {
        $dbConn->mysqliClient()->rollback();
        $redirUrl = urlBaseText() . "camperUpload.php?campersWithErrors";
        $_SESSION['campersWithErrors'] = $campersWithErrors;
        header("Location: $redirUrl");
        exit();
    }

    $dbConn->mysqliClient()->commit();

    $redirUrl = urlBaseText() . "camperUpload.php?success";
    header("Location: $redirUrl");
    exit();
}
?>

<?php
echo headerText("Bulk Upload Campers");

$errText = genFatalErrorReport(array($dbErr), true);
if (!is_null($errText)) {
    echo $errText;
}
?>

<?php
if ($message) {
    $messageText = "<div class=\"container well mt-3\">$message</div>";
    echo $messageText;
}
?>

<div class="card card-body mt-3 p-3 container">
<h2>Bulk Upload Campers</h2>
<p><b>Upload a CSV file with the following columns</b>: <?php echo implode(", ", $fields)?></p>
<p>To download a template CSV file, click <a href="camperUpload.php?downloadSample">HERE</a></p>

<p class="mb-1">Expand any of the below fields to view allowed values for each column</p>

<?php
$detailAccordion = new bootstrapAccordion($name="detail", $flush=false, $alwaysOpen=true);

// edah
$elementTitle = "Valid values for&nbsp<strong>" . edah_term_singular . "</strong>";
$elementBody = "<ul style=\"column-count: 3; column-gap:20px;\" class=\"mb-0\"><li>" . implode("</li><li>", array_keys($edah_name_to_id)) . "</li></ul>";
$detailAccordion->addAccordionElement($id="Edah", $title=$elementTitle, $body=$elementBody, $open=false);

// session
$elementTitle = "Valid values for&nbsp<b>session</b>";
$elementBody = "<ul style=\"column-count: 3; column-gap:20px;\" class=\"mb-0\"><li>" . implode("</li><li>", array_keys($session_name_to_id)) . "</li></ul>";
$detailAccordion->addAccordionElement($id="Session", $title=$elementTitle, $body=$elementBody, $open=false);

// bunk
$elementTitle = "Valid values for&nbsp<b>bunk</b>";
$elementBody = "<ul style=\"column-count: 3; column-gap:20px;\" class=\"mb-0\"><li>" . implode("</li><li>", array_keys($bunk_name_to_id)) . "</li></ul>";
$detailAccordion->addAccordionElement($id="BlockEdah", $title=$elementTitle, $body=$elementBody, $open=false);

// all else
$elementTitle = "Other details";
$elementBody = "<ul class=\"mb-0\"><li>If <b>needs_first_choice</b> is NOT blank, the camper will always receive their first choice preference</li>" . 
                "<li><b>email</b> should have an email; <b>email2</b> can be blank</li>" .
                "<li>Campers will search for their information using <b>edah</b>, <b>first_name</b>, and <b>last_name</b> to enter preferences. It is recommended to use preferred names for <b>first_name</b></li></ul>";
$detailAccordion->addAccordionElement($id="MoreInfo", $title=$elementTitle, $body=$elementBody, $open=false);

echo $detailAccordion->renderHtml();

?>

<h4 class="mt-4">Select File to Upload</h4>
<div class="row">
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
    <div class="col-4 mb-3">
        <input class="form-control col-sm" type="file" name="csv" id="csv" accept=".csv" required>
    </div>
  <input type="submit" class="btn btn-primary" value="Upload" name="submit">
</form>
</div>
</div>

<?php
echo footerText();
?>

</body>
</html>
