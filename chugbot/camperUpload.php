<?php
session_start();
include_once 'dbConn.php';
include_once 'functions.php';
bounceToLogin();

// Check for a query string that signals a message.
$parts = explode("&", $_SERVER['QUERY_STRING']);
$message = null;
foreach ($parts as $part) {
    $cparts = explode("=", $part);
    if ($cparts[0] == "success") {
        $message = "<font color=\"green\">Campers successfully imported!</font>";
        break;
    } else if ($cparts[0] == "campersWithErrors") {
        $message = "<font color=\"red\">Error importing campers from CSV.</font> Please try again, or escalate to an administrator.";
        $message .= "<br><br>The following campers had issues with importing: " . urldecode($cparts[1]);
        break;
    }
}

$dbConn = new DbConn();
$dbErr = "";
$dbConn->isSelect = true;
$sql = "SELECT * FROM edot";
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
$sql = "SELECT * FROM bunks";
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
    array_shift($csv); # remove header row

    $dbConn->mysqliClient()->begin_transaction();
    $campersWithErrors = array();
    foreach($csv as $camper) {
        $edahId = $edah_name_to_id[$camper["edah"]];
        $sessionId = $session_name_to_id[$camper["session"]];
        $bunkId = $bunk_name_to_id[$camper["bunk"]];
        $needsFirstChoice = !empty($camper["needs_first_choice"]);

        if (!$edahId || !$sessionId || !$bunkId) {
            array_push($campersWithErrors, $camper["first_name"] . " " . $camper["last_name"]);
            break;
        }

        $stmt = $dbConn->mysqliClient()->prepare("INSERT INTO campers(edah_id, session_id, first, last, bunk_id, email, email2, needs_first_choice) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $edahId, $sessionId, $camper["first_name"], $camper["last_name"], $bunkId, $camper["email"], $camper["email2"], $needsFirstChoice);
        $stmt->execute();
    }

    if (count($campersWithErrors) > 0) {
        $dbConn->mysqliClient()->rollback();
        $redirUrl = urlBaseText() . "camperUpload.php?campersWithErrors=" . implode(',', $campersWithErrors);
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
echo headerText("Upload Campers");

$errText = genFatalErrorReport(array($dbErr), true);
if (!is_null($errText)) {
    echo $errText;
}
?>

<?php
if ($message) {
    $messageText = <<<EOM
<div class="container well">
<h2>$message</h2>
</div>
EOM;
    echo $messageText;
}
?>

<div class="card card-body mt-3 p-3 container">
<h2>Upload Campers</h2>
<p><b>Upload a CSV file with the following columns</b>: edah, session, first_name, last_name, bunk, email, email2, needs_first_choice</p>
<p>Valid values for <b>edah</b>: <?php echo implode(", ", array_keys($edah_name_to_id)); ?></p>
<p>Valid values for <b>session</b>: <?php echo implode(", ", array_keys($session_name_to_id)); ?></p>
<p>Valid values for <b>bunk</b>: <?php echo implode(", ", array_keys($bunk_name_to_id)); ?></p>

<div class="row">
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
    <div class="col-4 mb-3">
        <input class="form-control col-sm" type="file" name="csv" id="csv">
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
