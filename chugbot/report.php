<?php
    session_start();
    include_once 'functions.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    abstract class ReportTypes
    {
        const None = 0;
        const ByEdah = 1;
        const ByChug = 2;
        const ByBunk = 3;
        const Everybody = 4;
    }

    $dbErr = "";
    $sessionId2Name = array();
    $blockId2Name = array();
    $groupId2Name = array();
    $edahId2Name = array();
    $chugId2Name = array();
    $bunkId2Name = array();
    $reportMethodId2Name = array(
                                 ReportTypes::ByEdah    => "Yoetzet/Rosh (by edah)",
                                 ReportTypes::ByBunk    => "Madrich (by bunk)",
                                 ReportTypes::ByChug    => "Chug Leader (by chug)",
                                 ReportTypes::Everybody => "Director (everybody)"
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
<h2></h2>


<?php
    $errors = array();
    $reportMethod = ReportTypes::None;
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $reset = test_input($_POST["reset"]);
        $reportMethod = test_input($_POST["report_method"]);
        $edahId = test_input($_POST["edah_id"]);
        $bunkId = test_input($_POST["bunk_id"]);
        $chugId = test_input($_POST["chug_id"]);
        
        // Grab active block IDs.
        $activeBlockIds = array();
        populateActiveIds($activeBlockIds, "block_ids");

        // Report method is required for POST.  All other filter parameters are
        // optional (if we don't have a filter, we show everything).
        // Exception: if $reset is true, we set report type to none, and reset
        // other values.
        if ($reset) {
            $reportMethod = ReportTypes::None;
            $activeBlockIds = array();
            $edahId = NULL;
            $bunkId = NULL;
            $chugId = NULL;
        } else if ($reportMethod == NULL) {
            array_push($errors, errorString("Please choose a report type"));
        }
    }
    
    // Display errors and exit, if needed.
    $errText = genFatalErrorReport($errors);
    if (! is_null($errText)) {
        echo $errText;
        exit(); 
    }
    
    $actionTarget = htmlspecialchars($_SERVER["PHP_SELF"]);
    $pageStart = <<<EOM
<div class="form_container">
    
<h1><a>Chug Report Generator</a></h1>
<form id="main_form" class="appnitro" method="post" action="$actionTarget">
<div class="form_description">
<h2>Chug Report Generator</h2>
<p>Use this page to create reports of camper chug assignments.  Use the drop-down menus to create
custom reports.  Start by choosing a report type, and then select filters as needed.  If you omit a filter,
all data will be shown.  For example, if you are reporting by edah, and you do not choose the edah filter, then
all edot will be shown in the report.  Required options are marked with a <font color="red">*</font>.</p>
</div>
<ul>
    
EOM;
    echo $pageStart;
    
    // Always show the report method drop-down.
    $reportMethodDropDown = new FormItemDropDown("Report Type", TRUE, "report_method", 0);
    $reportMethodDropDown->setGuideText("Step 1: Choose your report type.  Yoetzet/Rosh Edah report is by edah, Madrich by bunk, Chug leader by chug, and Director shows assignments for the whole camp.");
    $reportMethodDropDown->setPlaceHolder("Choose Type");
    $reportMethodDropDown->setId2Name($reportMethodId2Name);
    $reportMethodDropDown->setColVal($reportMethod);
    $reportMethodDropDown->setInputSingular("Report Type");
    if ($reportMethod) {
        $reportMethodDropDown->setInputValue($reportMethod);
    }

    echo $reportMethodDropDown->renderHtml();
    
    // All report methods include a time block filter.
    if ($reportMethod) {
        $blockChooser = new FormItemInstanceChooser("Time Blocks", FALSE, "block_ids", 1);
        $blockChooser->setId2Name($blockId2Name);
        $blockChooser->setActiveIdHash($activeBlockIds);
        $blockChooser->setGuideText("Step 2: Choose the time block(s) you wish to display.  If you do not choose any, all applicable blocks will be shown.");
        echo $blockChooser->renderHtml();
    }
    
    // If we have a report method specified, display the appropriate filter fields.
    if ($reportMethod == ReportTypes::ByEdah) {
        
        
        
    }
    
    
    $cancelUrl = "";
    if (isset($_SESSION['admin_logged_in'])) {
        $cancelUrl = urlIfy("staffHome.php");
    } else {
        $cancelUrl = urlIfy("index.php");
    }
    
    echo "<li class=\"buttons\">";
    echo "<input id=\"submitFormButton\" class=\"button_text\" type=\"submit\" name=\"submit\" value=\"Submit\" />";
    echo "<input id=\"resetFormButton\" class=\"button_text\" type=\"submit\" name=\"reset\" value=\"Reset\" />";
    echo "<a href=\"$cancelUrl\">Cancel</a>";
    echo "</li></ul></form>";
    
    ?>

<div id="footer">
<?php
    echo footerText();
    ?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
    
    
    
    
    
    
    
    
    
    