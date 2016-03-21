<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';
    include_once 'formItem.php';
    bounceToLogin();
    
    // A class to generate a zebra-striped report.
    class ZebraReport {
        function __construct($db, $sql) {
            $this->db = $db;
            $this->sql = $sql;
        }
        
        public function addIgnoreColumn($ic) {
            $this->ignoreCols[$ic] = 1;
        }
        
        public function setNewTableColumn($ntc) {
            $this->newTableColumn = $ntc;
        }
        
        public function setCaption($c) {
            $this->caption = $c;
        }
        
        public function addCaptionReplaceColKey($key, $column, $default) {
            $this->captionReplaceColKeys[$key] = $column;
            $this->captionReplaceColDefault[$key] = $default;
        }
        
        public function setIdCol2EditPage($idCol, $editPage, $valCol) {
            $this->idCol2EditUrl[$idCol] = urlIfy($editPage);
            $this->valCol2IdCol[$valCol] = $idCol;
        }
        
        private function shouldSkipColumn($col) {
            if ($this->newTableColumn &&
                $this->newTableColumn == $col) {
                return TRUE; // Don't re-display the new table column.
            }
            if (array_key_exists($col, $this->idCol2EditUrl)) {
                return TRUE; // Don't display ID columns.
            }
            if (array_key_exists($col, $this->ignoreCols)) {
                return TRUE;
            }
            
            return FALSE;
        }
        
        public function renderTable() {
            if ($this->sql == NULL) {
                echo genFatalErrorReport(array("No table query was specified"));
                exit();
            }
            $err = "";
            $result = $this->db->doQuery($this->sql, $err);
            if ($result == FALSE) {
                echo dbErrorString($err);
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
                            // If we have a new table column, create an edit link if requested.
                            if (array_key_exists($this->newTableColumn, $this->valCol2IdCol)) {
                                $idCol = $this->valCol2IdCol[$this->newTableColumn];
                                $idVal = $row[$idCol];
                                $editUrl = $this->idCol2EditUrl[$idCol] . "?eid=$idVal";
                                $d = $row[$this->newTableColumn];
                                $linkText = "<a href=\"$editUrl\">$d</a>";
                                $captionText = str_replace("LINK", $linkText, $captionText);
                            }
                        }
                        // Loop through the caption text words, and check for
                        // any that appear in captionReplaceColKeys.  For any
                        // such values, replace the string with the corresponding
                        // value of $row.  If we did not get a value back, use the default.
                        foreach ($this->captionReplaceColKeys as $key => $column) {
                            $replaceText = $row[$column];
                            if (! $replaceText) {
                                $replaceText = $this->captionReplaceColDefault[$column];
                            }
                            $captionText = str_replace($key, $replaceText, $captionText);
                        }
                        $html .= "<caption>$captionText</caption>";
                    }
                    $html .= "<tr>";
                    
                    // Use the column keys as table headers.
                    $colKeys = array_keys($row);
                    foreach ($colKeys as $tableHeader) {
                        if ($this->shouldSkipColumn($tableHeader)) {
                            continue;
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
                    $rowIndex = 0; // Reset row index, so each table gets its own zebra striping.
                }
                // Compute stripe color, and add table data.
                $oddText = "";
                if ($rowIndex++ % 2 != 0) {
                    $oddText = "class=zebradarkstripe";
                }
                $html .= "<tr $oddText>";
                $i = 0;
                foreach ($colKeys as $tableDataKey) {
                    if ($this->shouldSkipColumn($tableDataKey)) {
                        continue;
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
        
        private $captionReplaceColKeys = array();
        private $captionReplaceColDefault = array();
        private $ignoreCols = array();
        private $idCol2EditUrl = array();
        private $valCol2IdCol = array();
        private $sql = NULL;
        private $db = NULL;
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
        const Director = 4;
    }

    $dbErr = "";
    $sessionId2Name = array();
    $blockId2Name = array();
    $groupId2Name = array();
    $edahId2Name = array();
    $chugId2Name = array();
    $bunkId2Name = array();
    $reportMethodId2Name = array(
                                 ReportTypes::ByEdah    => "Yoetzet/Rosh Edah (by edah)",
                                 ReportTypes::ByBunk    => "Madrich (by bunk)",
                                 ReportTypes::ByChug    => "Chug Leader (by chug)",
                                 ReportTypes::Director  => "Director (whole camp, sorted by edah)"
                                );
    
    fillId2Name($chugId2Name, $dbErr,
                "chug_id", "chugim", "group_id",
                "groups");
    fillId2Name($sessionId2Name, $dbErr,
                "session_id", "sessions");
    fillId2Name($blockId2Name, $dbErr,
                "block_id", "blocks");
    fillId2Name($groupId2Name, $dbErr,
                "group_id", "groups");
    fillId2Name($edahId2Name, $dbErr,
                "edah_id", "edot");
    fillId2Name($bunkId2Name, $dbErr,
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
            
            // By-edah report.
            if ($lhs == "edah") {
                $reportMethod = ReportTypes::ByEdah;
                $edahId = $cparts[1];
                $doReport = 1;
            } else if ($lhs == "block") {
                $activeBlockIds[$cparts[1]] = 1;
            }
            
            // Add more of these as needed.
            
        }
    }
    
    // Per-chug report requires a chug ID to display.
    if ($reportMethod == ReportTypes::ByChug &&
        $doReport &&
        $chugId == NULL) {
        array_push($errors, errorString("Please choose a chug for this report"));
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
    } else if ($reportMethod == ReportTypes::ByBunk) {
        // Same as edah, but with a bunk filter.
        $bunkChooser = new FormItemDropDown("Bunk", FALSE, "bunk_id", $liNumCounter++);
        $bunkChooser->setGuideText("Step 3: Choose a bunk/tzrif, or leave empty to see all bunks");
        $bunkChooser->setInputClass("element select medium");
        $bunkChooser->setInputSingular("bunk");
        $bunkChooser->setColVal($bunkId);
        $bunkChooser->setId2Name($bunkId2Name);
        echo $bunkChooser->renderHtml();
    } else if ($reportMethod == ReportTypes::ByChug) {
        // Similar to the above, but the filter is by chug.  Also, in this case, the
        // input is required.
        $chugChooser = new FormItemDropDown("Chug", TRUE, "chug_id", $liNumCounter++);
        $chugChooser->setGuideText("Step 3: Choose a chug for this report.");
        $chugChooser->setInputClass("element select medium");
        $chugChooser->setInputSingular("chug");
        $chugChooser->setColVal($chugId);
        $chugChooser->setId2Name($chugId2Name);
        echo $chugChooser->renderHtml();
    } else if ($reportMethod == ReportTypes::Director) {
        // The director report shows all options, so there are no filter fields
        // except time block.
        
    }
    
    $cancelUrl = "";
    if (isset($_SESSION['admin_logged_in'])) {
        $cancelUrl = urlIfy("staffHome.php");
    } else {
        $cancelUrl = urlIfy("index.php");
    }
    
    $buttonText = "Go";
    if ($reportMethod) {
        $buttonText = "Display";
    }
    
    echo "<li class=\"buttons\">";
    echo "<input id=\"submitFormButton\" class=\"button_text\" type=\"submit\" name=\"submit\" value=\"$buttonText\" />";
    echo "<input id=\"resetFormButton\" class=\"button_text\" type=\"submit\" name=\"reset\" value=\"Reset\" />";
    echo "<button onclick=\"history.go(-1);\">Back </button>";
    echo "<a href=\"$cancelUrl\">Home</a>";
    echo "</li></ul></form>";
    
    if ($doReport) {
        // Prepare and display the report, setting the SQL according to the report
        // type.
        $db = new DbConn();
        $db->isSelect = TRUE;
        if ($reportMethod == ReportTypes::ByEdah) {
            // Per-edah report.
            $sql = "SELECT CONCAT(c.last, ', ', c.first) AS name, b.name bunk, bl.name block, e.name edah, e.sort_order edah_sort_order, " .
            "g.name group_name, ch.name assignment, c.camper_id camper_id, b.bunk_id bunk_id, e.edah_id edah_id, g.group_id group_id, " .
            "ch.chug_id chug_id, bl.block_id block_id " .
            "FROM campers c, bunks b, blocks bl, matches m, chugim ch, edot e, groups g, chug_instances i " .
            "WHERE c.bunk_id = b.bunk_id AND m.chug_instance_id = i.chug_instance_id AND i.block_id = bl.block_id AND i.block_id = bl.block_id " .
            "AND i.chug_id = ch.chug_id AND c.edah_id = e.edah_id AND m.camper_id = c.camper_id AND g.group_id = ch.group_id ";
            if (count($activeBlockIds) > 0) {
                $sql .= "AND bl.block_id IN (" . implode(",", array_keys($activeBlockIds)) . ") ";
            }
            if ($edahId) {
                $sql .= "AND c.edah_id = ? ";
                $db->addColVal($edahId, 'i');
            }
            $sql .= "ORDER BY edah_sort_order, edah, name, block, group_name";
            
            // Create and display the report.
            $edahReport = new ZebraReport($db, $sql);
            $edahReport->setNewTableColumn("edah");
            $edahReport->setCaption("Chug Assignments by Edah for LINK");
            $edahReport->addIgnoreColumn("edah_sort_order");
            $edahReport->setIdCol2EditPage("camper_id", "editCamper.php", "name");
            $edahReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
            $edahReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
            $edahReport->setIdCol2EditPage("group_id", "editGroup.php", "group_name");
            $edahReport->setIdCol2EditPage("chug_id", "editChug.php", "assignment");
            $edahReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
            $edahReport->renderTable();
        } else if ($reportMethod == ReportTypes::ByBunk) {
            // Per-bunk report.  This the same as the per-edah report, except
            // organized by bunk.
            $sql = "SELECT CONCAT(c.last, ', ', c.first) AS name, b.name bunk, bl.name block, e.name edah, e.sort_order edah_sort_order, " .
            "g.name group_name, ch.name assignment, c.camper_id camper_id, b.bunk_id bunk_id, e.edah_id edah_id, g.group_id group_id, " .
            "ch.chug_id chug_id, bl.block_id block_id " .
            "FROM campers c, bunks b, blocks bl, matches m, chugim ch, edot e, groups g, chug_instances i " .
            "WHERE c.bunk_id = b.bunk_id AND m.chug_instance_id = i.chug_instance_id AND i.block_id = bl.block_id AND i.block_id = bl.block_id " .
            "AND i.chug_id = ch.chug_id AND c.edah_id = e.edah_id AND m.camper_id = c.camper_id AND g.group_id = ch.group_id ";
            if (count($activeBlockIds) > 0) {
                $sql .= "AND bl.block_id IN (" . implode(",", array_keys($activeBlockIds)) . ") ";
            }
            if ($bunkId) {
                $sql .= "AND b.bunk_id = $bunkId ";
                $db->addColVal($bunkId, 'i');
            }
            $sql .= "ORDER BY bunk, name, edah_sort_order, edah, group_name";
            
            // Create and display the report.
            $bunkReport = new ZebraReport($db, $sql);
            $bunkReport->setNewTableColumn("bunk");
            $bunkReport->setCaption("Chug Assignments by Bunk for LINK");
            $bunkReport->addIgnoreColumn("edah_sort_order");
            $bunkReport->setIdCol2EditPage("camper_id", "editCamper.php", "name");
            $bunkReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
            $bunkReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
            $bunkReport->setIdCol2EditPage("group_id", "editGroup.php", "group_name");
            $bunkReport->setIdCol2EditPage("chug_id", "editChug.php", "assignment");
            $bunkReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
            $bunkReport->renderTable();
        } else if ($reportMethod == ReportTypes::ByChug) {
            // The chug report is meant for chug leaders.  The leaders need a separate sheet
            // for each edah that comes to the chug.  For each edah, the sheet should have:
            // - Rosh name and phone at the top, together with the edah name.
            // - List of campers in the edah: name and bunk.
            $sql = "SELECT CONCAT(c.last, ', ', c.first) AS camper, e.name edah, e.sort_order edah_sort_order, " .
            "e.rosh_name rosh, e.rosh_phone roshphone, ch.name chug_name, b.name bunk, bl.name block, " .
            "ch.chug_id chug_id, bl.block_id block_id, b.bunk_id bunk_id, e.edah_id edah_id, c.camper_id " .
            "FROM edot e, campers c, matches m, chug_instances i, chugim ch, bunks b, blocks bl " .
            "WHERE e.edah_id = c.edah_id AND c.camper_id = m.camper_id AND m.chug_instance_id = i.chug_instance_id AND " .
            "i.chug_id = ch.chug_id AND i.block_id = bl.block_id AND ch.chug_id = $chugId AND c.bunk_id = b.bunk_id ";
            if (count($activeBlockIds) > 0) {
                $sql .= "AND i.block_id IN (" . implode(",", array_keys($activeBlockIds)) . ") ";
            }
            $sql .= "ORDER BY edah, edah_sort_order, block, camper, bunk";
            
            $chugReport = new ZebraReport($db, $sql);
            $chugReport->setNewTableColumn("edah");
            $chugReport->addIgnoreColumn("edah_sort_order");
            $chugReport->addIgnoreColumn("rosh");
            $chugReport->addIgnoreColumn("roshphone");
            $chugReport->addIgnoreColumn("chug_name");
            $chugReport->addIgnoreColumn("block");
            $chugReport->setIdCol2EditPage("camper_id", "editCamper.php", "camper");
            $chugReport->setIdCol2EditPage("chug_id", "editChug.php", "chug_name");
            $chugReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
            $chugReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
            $chugReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
            $chugReport->setCaption("LINK campers for BLOCK<br>Rosh: ROSH, PHONE");
            $chugReport->addCaptionReplaceColKey("ROSH", "rosh", "none listed");
            $chugReport->addCaptionReplaceColKey("PHONE", "roshphone", "no rosh phone");
            $chugReport->addCaptionReplaceColKey("BLOCK", "block", "no block name");
            $chugReport->renderTable();
        } else if ($reportMethod == ReportTypes::Director) {
            // The director report is similar to the edah report, but unfiltered.
            $sql = "SELECT CONCAT(c.last, ', ', c.first) AS name, b.name bunk, bl.name block, e.name edah, e.sort_order edah_sort_order, " .
            "g.name group_name, ch.name assignment, c.camper_id camper_id, b.bunk_id bunk_id, e.edah_id edah_id, g.group_id group_id, " .
            "ch.chug_id chug_id, bl.block_id block_id " .
            "FROM campers c, bunks b, blocks bl, matches m, chugim ch, edot e, groups g, chug_instances i " .
            "WHERE c.bunk_id = b.bunk_id AND m.chug_instance_id = i.chug_instance_id AND i.block_id = bl.block_id AND i.block_id = bl.block_id " .
            "AND i.chug_id = ch.chug_id AND c.edah_id = e.edah_id AND m.camper_id = c.camper_id AND g.group_id = ch.group_id ";
            if (count($activeBlockIds) > 0) {
                $sql .= "AND bl.block_id IN (" . implode(",", array_keys($activeBlockIds)) . ") ";
            }
            $sql .= "ORDER BY edah_sort_order, edah, name, block, group_name";
            
            // Create and display the report.
            $directorReport = new ZebraReport($db, $sql);
            $directorReport->setNewTableColumn("edah");
            $directorReport->setCaption("Chug Assignments by Edah for LINK");
            $directorReport->addIgnoreColumn("edah_sort_order");
            $directorReport->setIdCol2EditPage("camper_id", "editCamper.php", "name");
            $directorReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
            $directorReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
            $directorReport->setIdCol2EditPage("group_id", "editGroup.php", "group_name");
            $directorReport->setIdCol2EditPage("chug_id", "editChug.php", "assignment");
            $directorReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
            $directorReport->renderTable();
        }
    }
    
    ?>

</div>

<?php
    echo footerText();
    ?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
    
    
    
    
    
    
    
    
    
    
