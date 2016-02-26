<?php
    session_start();
    include_once 'functions.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    // A class to generate a zebra-striped report.
    class ZebraReport {
        function __construct($sql) {
            $this->sql = $sql;
            $this->mysqli = connect_db();
        }
        
        function __destruct() {
            $this->mysqli->close();
        }
        
        public function setNewTableColumn($ntc) {
            $this->newTableColumn = $ntc;
        }
        
        public function setCaption($c) {
            $this->caption = $c;
        }
        
        public function setIdCol2EditPage($idCol, $editPage, $valCol) {
            $this->idCol2EditUrl[$idCol] = urlIfy($editPage);
            $this->valCol2IdCol[$valCol] = $idCol;
        }
        
        public function renderTable() {
            if ($this->sql == NULL) {
                echo genFatalErrorReport(array("No table query was specified"));
                exit();
            }
            $result = $this->mysqli->query($this->sql);
            if ($result == FALSE) {
                echo dbErrorString($this->sql, $this->mysqli->error);
                exit();
            }
            // If no rows were found, display a message.
            if ($result->num_rows == 0) {
                echo "<h3>No matching assignments were found.</h3>";
                return;
            }
            // Step through the results, build the table, and display it.  We
            // start with a header, and then display zebra-striped rows.  If we
            // have a $newDivColumn, we create a new table section each
            // time that column's value changes.
            $html = "";
            $newTableColumnValue = NULL;
            $rowIndex = 0;
            while ($row = $result->fetch_assoc()) {
                if ($newTableColumnValue == NULL ||
                    ($this->newTableColumn != NULL &&
                     $row[$this->newTableColumn] != $newTableColumnValue))
                {
                    if ($newTableColumnValue != NULL) {
                        // If we have a changed new table value, close the div and
                        // previous table before starting this one.
                        $html .= "</div></table>";
                    }
                    $html .= "<div class=zebra><table>";
                    if ($this->caption) {
                        $captionText = $this->caption;
                        if ($this->newTableColumn) {
                            $itemText = $row[$this->newTableColumn];
                            if (array_key_exists($this->newTableColumn, $this->valCol2IdCol)) {
                                $idCol = $this->valCol2IdCol[$this->newTableColumn];
                                $idVal = $row[$idCol];
                                $editUrl = $this->idCol2EditUrl[$idCol] . "?eid=$idVal";
                                $d = $row[$this->newTableColumn];
                                $itemText = "<a href=\"$editUrl\">$d</a>";
                            }
                            $captionText .= " for " . $itemText;
                        }
                        $html .= "<caption>$captionText</caption>";
                    }
                    $html .= "<tr>";
                    
                    // Use the column keys as table headers.  Don't re-display the
                    // new-table column, since it's in the table header.  Also, do not
                    // display the ID columns.
                    $i = 0;
                    $colKeys = array_keys($row);
                    foreach ($colKeys as $tableHeader) {
                        if ($this->newTableColumn &&
                            $this->newTableColumn == $tableHeader) {
                            continue; // Don't re-display the new table column.
                        }
                        if (array_key_exists($tableHeader, $this->idCol2EditUrl)) {
                            continue; // Don't display ID columns.
                        }
                        $html .= "<th>$tableHeader</th>";
                    }
                    $html .= "</tr>";
                    
                    // Set the new value.
                    if ($this->newTableColumn != NULL) {
                        $newTableColumnValue = $row[$this->newTableColumn];
                    } else {
                        // If we don't have a new-table column specified, use
                        // a constant.
                        $newTableColumnValue = 1;
                    }
                    $rowIndex = 0; // Reset row index.
                    continue;
                }
                // Regular table data.
                $oddText = "";
                if ($rowIndex++ % 2 != 0) {
                    $oddText = "class=zebradarkstripe";
                }
                $html .= "<tr $oddText>";
                $i = 0;
                foreach ($colKeys as $tableDataKey) {
                    if ($this->newTableColumn &&
                        $this->newTableColumn == $tableDataKey) {
                        continue; // Don't re-display the new table column.
                    }
                    if (array_key_exists($tableDataKey, $this->idCol2EditUrl)) {
                        continue; // Don't display ID columns.
                    }
                    // If we have an ID value corresponding to this table key,
                    // display the table data as a link to the edit page.
                    $tableData = "";
                    if (array_key_exists($tableDataKey, $this->valCol2IdCol)) {
                        $idCol = $this->valCol2IdCol[$tableDataKey];
                        $idVal = $row[$idCol];
                        $editUrl = $this->idCol2EditUrl[$idCol] . "?eid=$idVal";
                        $d = $row[$tableDataKey];
                        $tableData = "<a href=\"$editUrl\">$d</a>";
                    } else {
                        $tableData = $row[$tableDataKey];
                    }
                    $html .= "<td>$tableData</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</table></div>";
            
            echo $html;
        }
        
        private $idCol2EditUrl = array();
        private $valCol2IdCol = array();
        private $mysqli;
        private $sql = NULL;
        private $caption = NULL;
        private $newTableColumn = NULL;
        private $headerTextMap = array();
    }
    
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
        $doReport = test_input($_POST["do_report"]);
        
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
    } else {
        // Check for a query string like ?edah=1&block=1.  Use that to deduce
        // the report method and parameters.
        $parts = explode("&", $_SERVER['QUERY_STRING']);
        foreach ($parts as $part) {
            $cparts = explode("=", $part);
            if (count($cparts) != 2) {
                continue;
            }
            $lhs = strtolower($cparts[0]);
            if ($lhs == "edah") {
                $reportMethod = ReportTypes::ByEdah;
                $edahId = $cparts[1];
                $doReport = 1;
            } else if ($lhs == "block") {
                $activeBlockIds[$cparts[1]] = 1;
            }
            
            // Add more as needed.
            
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
    
<h1><a>Chug Assignment Report</a></h1>
<form id="main_form" class="appnitro" method="post" action="$actionTarget">
<div class="form_description">
<h2>Chug Assignment Report</h2>
<p>Start by choosing a report type, then select filters as needed.  Required options are marked with a <font color="red">*</font>.</p>
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
    $liNumCounter = 0;
    if ($reportMethod) {
        $blockChooser = new FormItemInstanceChooser("Time Blocks", FALSE, "block_ids", $liNumCounter++);
        $blockChooser->setId2Name($blockId2Name);
        $blockChooser->setActiveIdHash($activeBlockIds);
        $blockChooser->setGuideText("Step 2: Choose the time block(s) you wish to display.  If you do not choose any, all blocks will be shown.");
        echo $blockChooser->renderHtml();
        
        // Add a hidden field indicating that the page should display a report
        // when this submit comes in.
        echo "<input type=\"hidden\" name=\"do_report\" id=\"do_report\" value=\"1\" />";
    }
    
    // If we have a report method specified, display the appropriate filter fields.
    if ($reportMethod == ReportTypes::ByEdah) {
        // Display an optional Edah drop-down filter.
        $edahChooser = new FormItemDropDown("Edah", FALSE, "edah_id", $liNumCounter++);
        $edahChooser->setGuideText("Step 3: Choose an edah, or leave empty to see all edot");
        $edahChooser->setInputClass("element select medium");
        $edahChooser->setInputSingular("edah");
        $edahChooser->setColVal($edahId);
        $edahChooser->setId2Name($edahId2Name);
        echo $edahChooser->renderHtml();
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
    echo "<button onclick=\"history.go(-1);\">Back </button>";
    echo "<a href=\"$cancelUrl\">Home</a>";
    echo "</li></ul></form>";
    
    if ($doReport) {
        // Prepare and display the report, setting the SQL according to the report
        // type.
        if ($reportMethod == ReportTypes::ByEdah) {
            $sql = "SELECT CONCAT(c.last, ', ', c.first) AS name, b.name bunk, bl.name block, e.name edah, g.name group_name, ch.name assignment, " .
            "c.camper_id camper_id, b.bunk_id bunk_id, e.edah_id edah_id, g.group_id group_id, ch.chug_id chug_id, bl.block_id block_id " .
            "FROM campers c, bunks b, blocks bl, matches m, chugim ch, edot e, groups g " .
            "WHERE c.bunk_id = b.bunk_id AND m.block_id = bl.block_id AND m.chug_id = ch.chug_id AND c.edah_id = e.edah_id AND m.camper_id = c.camper_id AND g.group_id = m.group_id ";
            if (count($activeBlockIds) > 0) {
                $sql .= "AND bl.block_id IN (" . implode(",", array_keys($activeBlockIds)) . ") ";
            }
            if ($edahId) {
                $sql .= "AND c.edah_id = $edahId ";
            }
            $sql .= "ORDER BY edah, name, block, group_name";
            
            // Create and display the report.
            $edahReport = new ZebraReport($sql);
            $edahReport->setNewTableColumn("edah");
            $edahReport->setCaption("Chug Matches");
            $edahReport->setIdCol2EditPage("camper_id", "editCamper.php", "name");
            $edahReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
            $edahReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
            $edahReport->setIdCol2EditPage("group_id", "editGroup.php", "group_name");
            $edahReport->setIdCol2EditPage("chug_id", "editChug.php", "assignment");
            $edahReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
            $edahReport->renderTable();
        }
    }
    
    ?>

<div id="footer">
<?php
    echo footerText();
    ?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
    
    
    
    
    
    
    
    
    
    
