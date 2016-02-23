<?php
    session_start();
    include_once 'functions.php';
    include_once 'formItem.php';
    bounceToLogin();

    $dbErr = "";
    $sessionId2Name = array();
    $blockId2Name = array();
    $groupId2Name = array();
    $edahId2Name = array();
    $chugId2Name = array();
    $bunkId2Name = array();
    $reportMethodId2Name = array(
                                1 => "Yoetzet/Rosh (edah)",
                                2 => "Madrich (bunk)",
                                3 => "Chug Leader (chug)",
                                4 => "Director (everything)"
                                );
    
    $mysqli = connect_db();
    fillId2Name($mysqli, $chugId2Name, $dbErr,
                "chug_id", "chugim", "group_id",
                "groups");
    fillId2Name($mysqli, $sessionId2Name, $dbErr,
                "session_id", "sessions");
    fillId2Name($mysqli, $blockId2Name, $dbErr,
                "block_id", "blocks");
    fillId2Name($mysqli, $groupId2Name, $dbErr,
                "group_id", "groups");
    fillId2Name($mysqli, $edahId2Name, $dbErr,
                "edah_id", "edot");
    fillId2Name($mysqli, $bunkId2Name, $dbErr,
                "bunk_id", "bunks");
    ?>

<?php
    echo headerText("Chug Report");
    
    $errText = genFatalErrorReport(array($dbErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<div class="centered_container">
<!-- This empty div makes the display cleaner. -->
</div>

<div class="centered_container">
<h2>Chug Report Generator</h2>
<p>Use this page to create reports of camper chug assignments.  Use the drop-down menus to create
custom reports.  Start by choosing a report type, and then select filters as needed.  If you omit a filter,
all data will be shown.  For example, if you are reporting by edah, and you do not choose the edah filter, then
all edot will be shown in the report.</p>
</div>

<?php
    $errors = array();
    $reportMethod = 0;
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $reportMethod = test_input($_POST["report_method"]);
        $blockId = test_input($_POST["block_id"]);
        $edahId = test_input($_POST["edah_id"]);
        $bunkId = test_input($_POST["bunk_id"]);
        $chugId = test_input($_POST["chug_id"]);
        // Report method is required for POST.  All other filter parameters are
        // optional (if we don't have a filter, we show everything).
        if ($reportMethod == NULL) {
            array_push($errors, errorString("Please choose a report type"));
        }
    }
    
    // Display errors and exit, if needed.
    $errText = genFatalErrorReport($errors);
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    
    // Always show the report method drop-down.
    $reportMethodDropDown = new FormItemDropDown("Report Type", TRUE, "report_method", 0);
    $reportMethodDropDown->setGuideText("Choose your report type.  Yoetzet/Rosh Edah report is by edah, Madrich by bunk, Chug leader by chug, and Director shows assignments for the whole camp.");
    $reportMethodDropDown->setPlaceHolder("Choose Type");
    $reportMethodDropDown->setId2Name($reportMethodId2Name);
    $reportMethodDropDown->setColVal($reportMethod);
    $reportMethodDropDown->setInputSingular("Report Type");
    if ($reportMethod) {
        $reportMethodDropDown->setInputValue($reportMethod);
    }
    echo "<div class=\"top_container\"">;
    $reportMethodDropDown->renderHtml();
    echo "</div>";
    
    ?>

<div id="footer">
<?php
    echo footerText();
    ?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
    
    
    
    
    
    
    
    
    
    