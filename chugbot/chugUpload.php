<?php
session_start();
include_once 'dbConn.php';
include_once 'functions.php';
bounceToLogin();
checkLogout();
setup_camp_specific_terminology_constants();

// ensure camper importer is enabled
$enableChugimImporter = check_enabled("enable_chugim_importer");
if (!$enableChugimImporter) {
    $redirUrl = urlIfy("../staffHome.php?from=chugimUpload.php");
    header("Location: $redirUrl");
    exit();
}

// map edah name, block name, chug group name to relevant ids
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
$sql = "SELECT * FROM blocks";
$result = $dbConn->doQuery($sql, $dbErr);
if ($result == false) {
    error_log($dbErr);
}
$block_name_to_id = [];
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $block_name_to_id[$row["name"]] = $row["block_id"];
}

$dbConn->isSelect = true;
$sql = "SELECT * FROM chug_groups";
$result = $dbConn->doQuery($sql, $dbErr);
if ($result == false) {
    error_log($dbErr);
}
$group_name_to_id = [];
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $group_name_to_id[$row["name"]] = $row["group_id"];
}

// get all current chugim to ensure no duplicates are uploaded
$dbConn->isSelect = true;
$sql = "SELECT name, group_id FROM chugim";
$result = $dbConn->doQuery($sql, $dbErr);
if ($result == false) {
    error_log($dbErr);
}
$chug_name_to_group = [];
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $chug_name_to_group[$row["name"]] = $row["group_id"];
}

// list of all required fields
$fields = array(chug_term_singular, "department", "group", "min", "max");
$block_heading_name = array();
$edah_heading_name = array();
foreach(array_keys($block_name_to_id) as $col) {
    array_push($block_heading_name, $col . "." . block_term_singular);
}
foreach(array_keys($edah_name_to_id) as $col) {
    array_push($edah_heading_name, $col . "." . edah_term_singular);
}
$fields = array_merge($fields, $block_heading_name);
$fields = array_merge($fields, $edah_heading_name);
array_push($fields, "rosh", "description");

// Check for a query string that signals a message.
$parts = explode("&", $_SERVER['QUERY_STRING']);
$message = null;
foreach ($parts as $part) {
    $cparts = explode("=", $part);
    if ($cparts[0] == "success") {
        $message = "<div class=\"alert alert-success\" role=\"alert\"><h4 class=\"alert-heading\">" . ucfirst(chug_term_plural) . " successfully imported!</h4></div>";
        break;
    } else if ($cparts[0] == "chugimWithErrors" && isset($_SESSION['chugimWithErrors'])) {
        $message = "<div class=\"alert alert-danger\" role=\"alert\">";
        $message .= "<h4 class=\"alert-heading\">Error importing " . chug_term_plural . " from CSV.</h4><p>Please try again, or escalate to an administrator.</p>";
        $message .= "<hr><p class=\"mb-0\">The following " . chug_term_singular . "/" . chug_term_plural . " had issues with importing:</p>";
        $chugimString = implode("</li><li>", $_SESSION['chugimWithErrors']);
        $message .= "<ul class=\"mb-0\"><li>" . $chugimString . "</li></ul></div>";
        unset($_SESSION['chugimWithErrors']);
        break;
    } else if ($cparts[0] == "missingHeader") {
        $message = "<div class=\"alert alert-danger\" role=\"alert\">";
        $message .= "<h4 class=\"alert-heading\">Error importing " . chug_term_plural . " from CSV.</h4>";
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
        header('Content-Disposition: attachment; filename="' . chug_term_plural . 'Sample.csv";');
        $f = fopen('php://output', 'w');
        // add header rows
        fputcsv($f, $fields, ","); 
        // create some fake sample data
        $count = 0;
        while ($count < sizeof($group_name_to_id)*3) {
            $blockEdahLen = sizeof($block_name_to_id) + sizeof($edah_name_to_id) - 1;
            // randomly marks a few edot/block columns with "1" to indicate the chug is available for that edah/block
            // builds a string of commas, then inserts a few "1"s starting at the end of the string
            $blockEdah = str_repeat(",", $blockEdahLen);
            $temp = $blockEdahLen - random_int(0,3);
            while ($temp >= 0) {
                $blockEdah = substr_replace($blockEdah, "1", $temp, 0);
                $temp = $temp - random_int(1,4);
            }
            fputcsv($f, array_merge(array(ucfirst(chug_term_singular) . " $count", null, array_keys($group_name_to_id)[$count % sizeof($group_name_to_id)], 
                random_int(0,5), random_int(5,20)), explode(",", $blockEdah), array(null, null)), ",");
            $count++;
        }
        exit();
    }
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
            $redirUrl = urlBaseText() . "chugUpload.php?missingHeader=" . urlencode($field);
            header("Location: $redirUrl");
            exit();
        }
    }

    // determine next chug_id so we can prepare entries to db tables chugim, chug_instances, and edot_for_chug
    $nextChugId = 0;
    $dbErr = "";
    $sql = "SET information_schema_stats_expiry = 0";
    $result = $dbConn->doQuery($sql, $dbErr);
    $sql = "SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'chugim'";
    $result = $dbConn->doQuery($sql, $dbErr);
    if ($result == false) {
        error_log($dbErr);
    }
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $nextChugId = (int)$row['AUTO_INCREMENT'];
    }

    $dbConn->mysqliClient()->begin_transaction();
    $chugimWithErrors = array();
    foreach($csv as $chug) {
        $error = null;
        // check for duplicate
        if (array_key_exists($chug[chug_term_singular], $chug_name_to_group)) {
            if ($chug_name_to_group[$chug[chug_term_singular]] == $group_name_to_id[$chug['group']]) {
                array_push($chugimWithErrors, "\"<b>" . $chug[chug_term_singular] . "</b>\" (" . $chug["group"] . ") already exists");
                continue;
            }
        }

        // verify group is valid
        $groupId = null;
        if (array_key_exists($chug['group'], $group_name_to_id)) {
            $groupId = $group_name_to_id[$chug['group']];
        } else {
            $error = "invalid " . chug_term_singular . " group: '" . $chug['group'] . "'";
        }

        // min is non-negative number
        $min = convert_to_nonnegative_int($chug['min']);
        if ($min < 0 || $min != $chug['min']) {
            $error = "min ('" . $chug['min'] . "') is not a nonnegative integer";
        }
        // max is non-negative number
        $max = convert_to_nonnegative_int($chug['max']);
        if ($max < 0 || $max != $chug['max']) {
            $error = "max ('"  . $chug['max'] . "') is not a nonnegative integer";
        }
        // min is smaller than max
        if ($min > $max & $error == null) {
            $error = "min ($min) is larger than max ($max)";
        }
        
        // check for errors
        if ($error != null) {
            array_push($chugimWithErrors, "\"" . $chug[chug_term_singular] . "\" (" . $chug["group"] . ") - $error");
            continue;
        }

        // prepare to add chug to db
        $stmt = $dbConn->mysqliClient()->prepare("INSERT INTO chugim(name, group_id, max_size, min_size, description, chug_id, department_name, rosh_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiisiss", $chug[chug_term_singular], $groupId, $max, $min, $chug['description'], $nextChugId, $chug['department'], $chug['rosh']);
        $stmt->execute();

        // add chug_instances to db based on selected blocks
        foreach($block_heading_name as $header) {
            if (!empty($chug[$header])) {
                $block_id = $block_name_to_id[substr($header, 0, -strlen(block_term_singular)-1)];

                $stmt = $dbConn->mysqliClient()->prepare("INSERT INTO chug_instances(chug_id, block_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $nextChugId, $block_id);
                $stmt->execute();
            }
        }

        // add edot_for_chug to db based on selected edot
        foreach($edah_heading_name as $header) {
            if (!empty($chug[$header])) {
                $edah_id = $edah_name_to_id[substr($header, 0, -strlen(edah_term_singular)-1)];

                $stmt = $dbConn->mysqliClient()->prepare("INSERT INTO edot_for_chug(chug_id, edah_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $nextChugId, $edah_id);
                $stmt->execute();
            }
        }

        $nextChugId ++;
    }

    // if errors, do not commit new additions to db
    if (count($chugimWithErrors) > 0) {
        $dbConn->mysqliClient()->rollback();
        $redirUrl = urlBaseText() . "chugUpload.php?chugimWithErrors";
        $_SESSION['chugimWithErrors'] = $chugimWithErrors;
        header("Location: $redirUrl");
        exit();
    }

    $dbConn->mysqliClient()->commit();

    $redirUrl = urlBaseText() . "chugUpload.php?success";
    header("Location: $redirUrl");
    exit();
}
?>

<?php
echo headerText("Bulk Upload " . ucfirst(chug_term_plural));

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
<h2>Bulk Upload <?php echo ucfirst(chug_term_plural)?></h2>
<p class="mb-1"><b>Upload a CSV file with the following columns</b>: </p>
<ul style="column-count: 3; column-gap:20px;"><li><?php echo implode("</li><li>", $fields)?></li></ul>
<p>To download a template CSV file (with columns properly pre-set), click <a href="chugUpload.php?downloadSample">HERE</a></p>

<p class="mb-1">Expand any of the below fields to view allowed values for each column</p>

<?php
$detailAccordion = new bootstrapAccordion($name="detail", $flush=false, $alwaysOpen=true);

// group
$elementTitle = "Valid values for&nbsp<strong>group</strong>";
$elementBody = "<ul style=\"column-count: 3; column-gap:20px;\" class=\"mb-0\"><li>" . implode("</li><li>", array_keys($group_name_to_id)) . "</li></ul>";
$detailAccordion->addAccordionElement($id="Group", $title=$elementTitle, $body=$elementBody, $open=false);

// minmax
$elementTitle = "Valid values for&nbsp<b>min</b>&nbspand&nbsp<b>max</b>";
$elementBody = "<b>min</b> and <b>max</b> must each be non-negative whole numbers as regular numerals. <b>min</b> should be smaller than <b>max</b>";
$detailAccordion->addAccordionElement($id="MinMax", $title=$elementTitle, $body=$elementBody, $open=false);

// block/edah
$elementTitle = "Valid values for any&nbsp<b>" . block_term_singular . "</b>&nbspor&nbsp<b>" . edah_term_singular . "</b>&nbspcolumn";
$elementBody = "For clarity, here are the headers for each of these columns. The CSV file uploaded must match each bullet point exactly" . 
                "<ul style=\"column-count: 3; column-gap:20px;\" class=\"mb-2\"><li>" . implode("</li><li>", array_merge($block_heading_name, $edah_heading_name)) . "</li></ul>" .
                "If the cell for an associated column/row is NOT blank, the " . chug_term_singular . " will be available for that " . block_term_singular . "/" . edah_term_singular . ".";
$detailAccordion->addAccordionElement($id="BlockEdah", $title=$elementTitle, $body=$elementBody, $open=false);

// all else
$elementTitle = "Valid values for&nbsp<strong>department</strong>,&nbsp<strong>rosh</strong>, and/or&nbsp<strong>description</strong>";
$elementBody = "Each of these fields are optional; it is okay if the cells are left blank<ul class=\"mb-0\">" . 
                "<li><b>rosh</b> indicates the head counselor for the " . chug_term_singular . " <b>department</b> indicates the division. Both of these are for your information and are not critical</li>" . 
                "<li><b>description</b> is shown to campers when leveling their " . chug_term_plural . "enter an optional (fun encouraged!) one here</li></ul>";
$detailAccordion->addAccordionElement($id="MoreInfo", $title=$elementTitle, $body=$elementBody, $open=false);

echo $detailAccordion->renderHtml();

?>

<div class="alert alert-primary mt-3" role="alert">
  <h5 class="alert-heading">Important Note</h4>
  <p>There <b>CANNOT</b> be multiple <?php echo chug_term_plural?> within the same <?php echo chug_term_singular?> group which share the same name. Attempting to upload multiple <?php echo chug_term_plural?> with the same name in one group or uploading a duplicate <?php echo chug_term_singular?> which already exists will result in an error</p>
  <p class="mb-0">If you need to make updates to the availability of an existing <?php echo chug_term_singular?>, edit it within the menu on the staff home page</p>
</div>

<h4>Select File to Upload</h4>
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

<?php 

function convert_to_nonnegative_int($value) {
    $number = (int)$value;
    if ($number >= 0) {
        return $number;
    }
    return null;
}

?>