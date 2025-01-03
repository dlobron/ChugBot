<?php
session_start();
include_once 'dbConn.php';
include_once 'functions.php';
include_once 'formItem.php';
require_once 'fpdf/fpdf.php';
bounceToLogin();
checkLogout();
setup_camp_specific_terminology_constants();

abstract class OutputTypes
{
    const Html = 0;
    const Pdf = 1;
    const Csv = 2;
}

// A class to generate a printable PDF report.
class PDF extends FPDF
{
    public function GenTable($title, $header, $data, $generateCheckboxes=true)
    {
        // Split line break in title into two lines.
        $titleParts = explode("<br>", $title);
        $this->SetTitle($titleParts[0]);
        // Colors, line width and bold font
        $this->SetTextColor(128, 128, 128);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', $this->fontSize);
        foreach ($titleParts as $titleLine) {
            $this->Cell(0, 10, $titleLine, 0, 2, 'C');
        }
        if ($this->reportedItem) {
            $forText = "Report for " . $this->typeOfReport . " " . $this->reportedItem;
            $this->Cell(0, 10, $forText, 0, 2, 'C');
        }
        $this->SetFillColor(128, 128, 128);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);

        // Header
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($this->columnWidths[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        $fill = false;
        $sz = 5;
        foreach ($data as $row) {
            // Calculate the height of the row.
            $nb = 0;
            for ($i = 0; $i < count($row); $i++) {
                $nb = max($nb,
                    $this->NbLines($this->columnWidths[$i], $row[$i]));
            }
            $h = max($sz * $nb,10);
            // Issue a page break first if needed.
            $this->CheckPageBreak($h);
            // Draw the cells of the row.
            for ($i = 0; $i < count($row); $i++) {
                $alignment = ($i <= count($row) / 2) ? 'L' : 'R';
                $x = $this->GetX();
                $y = $this->GetY();
                $w = $this->columnWidths[$i];
                $this->Rect($x, $y, $w, $h);
                $this->MultiCell($w, $sz, $row[$i],
                    0, //'LR',
                    $alignment,
                    0); //$fill);
                $this->SetXY($x + $w, $y);
            }

            // optionaly add a set of blank checkboxes (used for things like attendance)
            // to every row of every report (done by default)
            if($generateCheckboxes) {
                $NUM_OF_CHECKBOXES = 10;
                foreach(range(1, $NUM_OF_CHECKBOXES) as $index) {
                    $this->Cell(10, $h, ' ', 1);
                }
            }
            $this->Ln($h);
            $fill = !$fill;
        }
        // Closing line
        $this->Cell(array_sum($this->columnWidths), 0, '', 'T');
    }

    public function CheckPageBreak($h)
    {
        // If the height h would cause an overflow, add a new page immediately.
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    public function NbLines($w, $txt)
    {
        // Computes the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n") {
            $nb--;
        }

        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }

            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }

                } else {
                    $i = $sep + 1;
                }

                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }

        }
        return $nl;
    }

    public function setColWidths($w)
    {
        $this->columnWidths = $w;
    }

    public function setFontSize($s)
    {
        $this->fontSize = $s;
    }

    public function setReportTypeAndNameOfItem($t, $n)
    {
        $this->typeOfReport = $t;
        $this->reportedItem = $n;
    }

    private $columnWidths = null;
    private $fontSize = 12;
    private $typeOfReport = null;
    private $reportedItem = null;
}

// A class to generate a zebra-striped report.
class ZebraReport
{
    public function __construct($db, $sql, $outputType)
    {
        $this->db = $db;
        $this->sql = $sql;
        $this->outputType = $outputType;
    }

    public function addIgnoreColumn($ic)
    {
        $this->ignoreCols[$ic] = 1;
    }

    public function addNewTableColumn($ntc)
    {
        $this->newTableColumns[$ntc] = true;
    }

    public function setCaption($c)
    {
        $this->caption = $c;
    }

    public function addCaptionReplaceColKey($key, $column, $default)
    {
        $this->captionReplaceColKeys[$key] = $column;
        $this->captionReplaceColDefault[$key] = $default;
    }

    public function setIdCol2EditPage($idCol, $editPage, $valCol)
    {
        $this->idCol2EditUrl[$idCol] = urlIfy($editPage);
        $this->valCol2IdCol[$valCol] = $idCol;
    }

    public function setIdNameMap($idNameMap, $regex = null)
    {
        $this->idNameMap = $idNameMap;
        $this->idNameMapRegex = $regex;
    }

    public function addIdNameMapCol($col)
    {
        $this->idNameMapCols[$col] = true;
    }

    private function shouldSkipColumn($col)
    {
        if (array_key_exists($col, $this->newTableColumns)) {
            return true; // Don't re-display new table columns.
        }
        if (array_key_exists($col, $this->idCol2EditUrl)) {
            return true; // Don't display ID columns.
        }
        if (array_key_exists($col, $this->ignoreCols)) {
            return true;
        }

        return false;
    }

    public function setReportTypeAndNameOfItem($t, $n)
    {
        $this->typeOfReport = $t;
        $this->reportedItem = $n;
    }

    public function setPdfFontSizeAndMult($size, $mult)
    {
        $this->pdfFontSize = $size;
        $this->mult = $mult;
    }

    public function needToStartNewTable($newTableColumnValues, $row)
    {
        static $firstTime = true;
        if ($firstTime) {
            // If it's our first time through the loop, then we should
            // always create a new table.
            $firstTime = false;
            return true;
        }
        foreach ($newTableColumnValues as $col => $val) {
            if (!array_key_exists($col, $row)) {
                continue;
            }
            if ($row[$col] != $val) {
                // If any new-table column value has changed from its
                // previous version, we need a new table.
                return true;
            }
        }

        // None of the new-table columns has changed.
        return false;
    }

    public function renderTable($generateCheckboxes=true)
    {
        if ($this->sql === null) {
            echo genFatalErrorReport(array("No table query was specified"));
            exit();
        }
        if (empty($this->sql)) {
            echo "<h3>No results were found.</h3>";
            return;
        }
        $err = "";
        $result = $this->db->doQuery($this->sql, $err);
        if ($result == false) {
            echo dbErrorString($this->sql, $err);
            exit();
        }
        // If no rows were found, display a message.
        if ($result->num_rows == 0) {
            echo "<h3>No results were found.</h3>";
            return;
        }
        // Step through the results, build the table, and display it.  We
        // start with a header, and then display zebra-striped rows.  If we
        // have a $newDivColumn, we create a new table section each
        // time that column's value changes.
        // We also build PDF tables, in case we've been asked to generate printable
        // output, and prepare to emit CSV if requested.
        $pdfTables = array();
        $pdf = new PDF('Landscape', 'mm', 'A4');
        $pdf->setReportTypeAndNameOfItem($this->typeOfReport, $this->reportedItem);
        $pdf->setFontSize($this->pdfFontSize);
        $pdfHeader = array();
        $pdfData = array();
        $pdfDataRow = array();
        $pdfCaptionText = "";
        $pdfColWidths = array();
        $html = "";
        $newTableColumnValues = array();
        $rowIndex = 0;
        if ($this->outputType == OutputTypes::Csv) {
            // Output headers so that the CSV is downloaded rather than displayed.
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=ChugReport.csv');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            // Create a file pointer connected to the output stream.
            $output = fopen('php://output', 'w');
        }
        while ($row = $result->fetch_assoc()) {
            if ($this->needToStartNewTable($newTableColumnValues, $row)) {
                if (!empty($newTableColumnValues)) {
                    // If we have a changed new table value, close the div and
                    // previous table before starting a new one.
                    $html .= "</div></table>";
                    // Add the table we just built to the PDF.
                    $pdf->AddPage();
                    $pdf->setColWidths($pdfColWidths);
                    $pdf->GenTable($pdfCaptionText, $pdfHeader, $pdfData, $generateCheckboxes);
                    // Re-initialize the PDF header and data arrays.  If we have
                    // CSV output, write the title, column headers, and data of
                    // the table we just built.
                    if ($this->outputType == OutputTypes::Csv) {
                        $titleParts = explode("<br>", $pdfCaptionText);
                        $csvTitle = $titleParts[0] . ": " . $this->typeOfReport . " " . $this->reportedItem;
                        fputcsv($output, array($csvTitle));
                        fputcsv($output, $pdfHeader);
                        foreach ($pdfData as $pdfRow) {
                            fputcsv($output, $pdfRow);
                        }
                    }
                }
                $html .= "<div ><table class=\"table table-bordered table-striped table-hover\">";
                $pdfHeader = array();
                $pdfData = array();
                $pdfColWidths = array();
                if ($this->caption) {
                    $captionText = $this->caption;
                    $pdfCaptionText = $this->caption;
                    $i = 0;
                    foreach ($this->newTableColumns as $ntc => $val) {
                        // If we have a new table column, create an edit link if requested.
                        $replaceText = "LINK" . "$i";
                        if (array_key_exists($ntc, $this->valCol2IdCol)) {
                            $idCol = $this->valCol2IdCol[$ntc];
                            $idVal = $row[$idCol];
                            $d = $row[$ntc];
                            if ($idVal) {
                                $editUrl = $this->idCol2EditUrl[$idCol] . "?eid=$idVal";
                                $linkText = "<a href=\"$editUrl\">$d</a>";
                                $captionText = str_replace($replaceText, $linkText, $captionText);
                                $pdfCaptionText = str_replace($replaceText, $d, $pdfCaptionText);
                            } else {
                                $captionText = str_replace($replaceText, $d, $captionText);
                                $pdfCaptionText = str_replace($replaceText, $d, $pdfCaptionText);
                            }
                        }
                        $i++;
                    }
                    // Loop through the caption text words, and check for
                    // any that appear in captionReplaceColKeys.  For any
                    // such values, replace the string with the corresponding
                    // value of $row.  If we did not get a value back, use the default.
                    foreach ($this->captionReplaceColKeys as $key => $column) {
                        $replaceText = $row[$column];
                        if (!$replaceText) {
                            $replaceText = $this->captionReplaceColDefault[$key];
                        }
                        $captionText = str_replace($key, $replaceText, $captionText);
                        $pdfCaptionText = str_replace($key, $replaceText, $pdfCaptionText);
                    }
                    $html .= "<hr><h4 style=\"text-align:center;\" class=\"mt-3\">$captionText</h4>";
                }
                $html .= "<thead><tr>";

                // Use the column keys as table headers.
                $colKeys = array_keys($row);

                // Count number of columns included
                $numberOfColumns = 0;
                foreach ($colKeys as $tableHeader) {
                    if (!$this->shouldSkipColumn($tableHeader)) {
                        $numberOfColumns++;
                    }
                }

                foreach ($colKeys as $tableHeader) {
                    if ($this->shouldSkipColumn($tableHeader)) {
                        continue;
                    }
                    // Replace table headers with camp-specific terms
                    if ($tableHeader == "edah") {
                        $tableHeader = edah_term_singular;
                    } elseif ($tableHeader == "chug") {
                        $tableHeader = chug_term_singular;
                    } elseif ($tableHeader == "block") {
                        $tableHeader = block_term_singular;
                    }
                    // I changed this to automatically size columns in display; we can just uncomment the other part to make all equal
                    $html .= "<th scope=\"col\">"/* style=\"width: " . 100/$numberOfColumns . "%;\">*/ . "$tableHeader</th>";
                    array_push($pdfHeader, $tableHeader);
                    // Initialize the width for the column corresponding to this
                    // header.
                    array_push($pdfColWidths, (strlen($tableHeader) * $this->mult) + $this->add);
                }
                $html .= "</tr></thead>";

                // Update new table column values.
                foreach ($this->newTableColumns as $ntc => $val) {
                    if(array_key_exists($ntc, $row)) {
                        $newTableColumnValues[$ntc] = $row[$ntc];
                    }
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
                $d = $row[$tableDataKey];
                $tableData = "";
                if (array_key_exists($tableDataKey, $this->valCol2IdCol)) {
                    $idCol = $this->valCol2IdCol[$tableDataKey];
                    $idVal = $row[$idCol];
                    if ($idVal) {
                        $editUrl = $this->idCol2EditUrl[$idCol] . "?eid=$idVal";
                        $tableData = "<a href=\"$editUrl\">$d</a>";
                    } else {
                        $tableData = $d;
                    }
                } else {
                    $tableData = $d;
                    // If this column value should be looked up, do so.
                    if (array_key_exists($tableDataKey, $this->idNameMapCols) &&
                        array_key_exists($tableData, $this->idNameMap)) {
                        $tableData = $this->idNameMap[$tableData];
                        if ($this->idNameMapRegex) {
                            $pattern = "/" . $this->idNameMapRegex . ".*$/";
                            $tableData = preg_replace($pattern, "", $tableData);
                        }
                    }
                }
                $html .= "<td style=\"padding: 5px;\">$tableData</td>";
                // Look up and replace the PDF/report column.
                if (array_key_exists($tableDataKey, $this->idNameMapCols) &&
                    array_key_exists($d, $this->idNameMap)) {
                    $d = $this->idNameMap[$d];
                    if ($this->idNameMapRegex) {
                        $pattern = "/" . $this->idNameMapRegex . ".*$/";
                        $d = preg_replace($pattern, "", $d);
                    }
                }
                array_push($pdfDataRow, $d);
                //if ((strlen($d) * $this->mult) > $pdfColWidths[$i]) {
                //$pdfColWidths[$i] = (strlen($d) * $this->mult);
                //}
                $words = explode(" ", $d);
                foreach ($words as $word) {
                    if ((strlen($word) * $this->mult) + $this->add > $pdfColWidths[$i]) {
                        $pdfColWidths[$i] = (strlen($word) * $this->mult) + $this->add;
                    }
                }
                $i++;
            }

            // optionally add a set of blank checkboxes (used for things like attendance)
            // to every row of every report (does by default)
            if($generateCheckboxes) {
                $NUM_OF_CHECKBOXES = 10;
                $html .= "<td style=\"font-size: 20px\">";
                foreach(range(1, $NUM_OF_CHECKBOXES) as $index) {
                    $html .= "&#9744;&nbsp;";
                }
                $html .= "</td>";
            }

            $html .= "</tr>";
            array_push($pdfData, $pdfDataRow); // Save this row.
            $pdfDataRow = array(); // Start a new row.
        }
        $pdf->AddPage();
        $pdf->setColWidths($pdfColWidths);
        $pdf->GenTable($pdfCaptionText, $pdfHeader, $pdfData, $generateCheckboxes);
        $html .= "</table></div>";

        if ($this->outputType == OutputTypes::Pdf) {
            $pdf->Output();
            exit();
        }
        if ($this->outputType == OutputTypes::Csv) {
            // Write the table title, headers, and data.
            $titleParts = explode("<br>", $pdfCaptionText);
            $csvTitle = $titleParts[0] . ": " . $this->typeOfReport . " " . $this->reportedItem;
            fputcsv($output, array($csvTitle));
            fputcsv($output, $pdfHeader);
            foreach ($pdfData as $pdfRow) {
                // optionally add a set of blank checkboxes (used for things like attendance)
                // to every row of every report (does by default)
                if($generateCheckboxes) {
                    $NUM_OF_CHECKBOXES = 10;
                    foreach(range(1, $NUM_OF_CHECKBOXES) as $index) {
                        $pdfRow[str_repeat(' ', $index)] = ' ';
                    }
                }

                fputcsv($output, $pdfRow);
            }
            fclose($output);
            exit();
        }

        echo $html;
    }

    private $idNameMapRegex = null;
    private $idNameMapCols = array();
    private $idNameMap = array();
    private $captionReplaceColKeys = array();
    private $captionReplaceColDefault = array();
    private $ignoreCols = array();
    private $idCol2EditUrl = array();
    private $valCol2IdCol = array();
    private $sql = null;
    private $db = null;
    private $caption = null;
    private $newTableColumns = array();
    private $headerTextMap = array();
    private $mult = 3.0;
    private $add = 2;
    private $typeOfReport = null;
    private $reportedItem = null;
    private $outputType = OutputTypes::Html;
    private $pdfFontSize = 12;
}

function addWhereClause(&$sql, &$db, $idHash,
    $entity = "bl.block_id",
    $haveWhereAlready = false) {
    $phrase = $haveWhereAlready ? "AND" : "WHERE";
    $haveWhere = false;
    if (count($idHash) > 0) {
        $sql .= " $phrase $entity IN (";
        foreach ($idHash as $activeBlockId => $val) {
            if ($haveWhere) {
                $sql .= ",?";
            } else {
                $sql .= "?";
            }
            $haveWhere = true;
            $db->addColVal($activeBlockId, 'i');
        }
        $sql .= ") ";
    }

    // Return YES if we have a WHERE coming in, or if we added one.
    return ($haveWhereAlready || $haveWhere);
}

function addActiveItemsToCaption(&$caption, $activeIdHash, $itemPluralName, $id2Name)
{
    if (!empty($activeIdHash)) {
        $ucName = ucfirst($itemPluralName);
        $caption .= "<br><u>$ucName</u>: ";
        $ct = 0;
        foreach ($activeIdHash as $itemId => $active) {
            $itemName = $id2Name[$itemId];
            if ($ct++ == 0) {
                $caption .= $itemName;
            } else {
                $caption .= ", $itemName";
            }
        }
    }
}

abstract class ReportTypes
{
    const None = 0;
    const ByEdah = 1;
    const ByChug = 2;
    const ByBunk = 3;
    const Director = 4;
    const CamperChoices = 5;
    const AllRegisteredCampers = 6;
    const ChugimWithSpace = 7;
    const CamperHappiness = 8;
    const RegisteredMissingPrefs = 9;
    const NotAssignedCampers = 10;
    const ByChugRoshAndDepartment = 11;
}

$dbErr = "";
$sessionId2Name = array();
$blockId2Name = array();
$groupId2Name = array();
$edahId2Name = array();
$chugId2Name = array();
$bunkId2Name = array();
$reportMethodId2Name = array(
    ReportTypes::ByEdah => "Yoetzet/Rosh " . ucfirst(edah_term_singular) . " (by " . edah_term_singular . ")",
    ReportTypes::ByBunk => "Madrich (by bunk)",
    ReportTypes::ByChug => ucfirst(chug_term_singular) . " Leader (by " . chug_term_singular . ")",
    ReportTypes::ByChugRoshAndDepartment => ucfirst(chug_term_singular) . " Leader (by department and rosh)",
    ReportTypes::Director => "Director (whole camp, sorted by edah)",
    ReportTypes::CamperChoices => "Camper Prefs and Assignment",
    ReportTypes::AllRegisteredCampers => "All Campers Who Have Submitted Preferences",
    ReportTypes::ChugimWithSpace => ucfirst(chug_term_plural) . " With Free Space",
    ReportTypes::CamperHappiness => "Camper Happiness",
    ReportTypes::RegisteredMissingPrefs => "Campers Missing Preferences for Time " . ucfirst(block_term_plural),
    ReportTypes::NotAssignedCampers => "Campers Not Assigned to " . ucfirst(chug_term_singular),
);

// Check for archived databases.
$availableArchiveYears = array();
$yearList = getArchiveYears($dbErr);
foreach ($yearList as $year) {
    // We store the years in a hash that maps year to year, because we
    // want to use it in a drop-down menu.
    $availableArchiveYears[$year] = $year;
}
asort($availableArchiveYears);

$activeEdahIds = array();
$activeBlockIds = array();
$activeChugIds = array();
$activeSessionIds = array();
$activeGroupIds = array();
$errors = array();
$reportMethod = ReportTypes::None;
$outputType = OutputTypes::Html;
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $reset = test_get_input("reset");
    $reportMethod = test_get_input("report_method");
    $sessionId = test_get_input("session_id");
    $bunkId = test_get_input("bunk_id");
    $groupId = test_get_input("group_id");
    $blockId = test_get_input("block_id");
    $doReport = test_get_input("do_report");
    $archiveYear = test_get_input("archive_year");
    if (test_get_input("print")) {
        $outputType = OutputTypes::Pdf;
    } else if (test_get_input("export")) {
        $outputType = OutputTypes::Csv;
    }
    // Populate active IDs, if any.
    populateActiveIds($activeBlockIds, "block_ids");
    populateActiveIds($activeChugIds, "chug_ids");
    populateActiveIds($activeEdahIds, "edah_ids");
    populateActiveIds($activeSessionIds, "session_ids");
    populateActiveIds($activeGroupIds, "group_ids");

    // Report method is required for GET.  All other filter parameters are
    // optional (if we don't have a filter, we show everything).
    // Exception: if $reset is true, we set report type to none, and reset
    // other values.
    if ($reset) {
        $reportMethod = ReportTypes::None;
        $bunkId = null;
        $groupId = null;
        $blockId = null;
        $sessionId = null;
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

        // Add a check for each report type that we display this way- add
        // more of these as needed.
        if ($lhs == "edah" ||
            $lhs == "edah_id") {
            $reportMethod = ReportTypes::ByEdah;
            $activeEdahIds[$cparts[1]] = 1;
        } else if ($lhs == "block") {
            $activeBlockIds[$cparts[1]] = 1;
        } else if ($lhs == "session") {
            $activeSessionIds[$cparts[1]] = 1;
        } else if ($lhs == "print") {
            $outputType = OutputTypes::Pdf;
        } else if ($lhs == "export") {
            $outputType = OutputTypes::Csv;
        } else if ($lhs == "chug") {
            $activeChugIds[$cparts[1]] = 1;
        }
    }
}
if ($doReport && ($reportMethod === null)) {
    // The user must specify the type for a report.
    array_push($errors, errorString("Please choose a report type"));
}

if ($outputType == OutputTypes::Html) {
    echo headerText(ucfirst(chug_term_singular). " Report");
}

$errText = genFatalErrorReport(array($dbErr));
if (!is_null($errText)) {
    if ($outputType == OutputTypes::Html) {
        echo headerText(ucfirst(chug_term_singular). " Report");
    }
    echo $errText;
    exit();
}

// Per-chug report require at least one chug ID to display.
if (($reportMethod == ReportTypes::ByChug || $reportMethod == ReportTypes::ByChugRoshAndDepartment) &&
    $doReport &&
    count($activeChugIds) == 0) {
    array_push($errors, errorString("Please choose at least one " . chug_term_singular . " for this report"));
}

// Campers missing data for a time block requires at least one time block.
if ($reportMethod == ReportTypes::RegisteredMissingPrefs &&
    $doReport &&
    empty($blockId)) {
    array_push($errors, errorString("Please choose a time " . block_term_singular . " from which we'll report preferences missing"));
}

// Display errors and exit, if needed.
$errText = genFatalErrorReport($errors);
if (!is_null($errText)) {
    echo $errText;
    exit();
}

// Fill the ID -> name hashes that will populate the drop-down
// menus.
fillId2Name($archiveYear, $chugId2Name, $dbErr,
    "chug_id", "chugim", "group_id",
    "chug_groups");
fillId2Name($archiveYear, $sessionId2Name, $dbErr,
    "session_id", "sessions");
fillId2Name($archiveYear, $blockId2Name, $dbErr,
    "block_id", "blocks");
fillId2Name($archiveYear, $groupId2Name, $dbErr,
    "group_id", "chug_groups");
fillId2Name($archiveYear, $edahId2Name, $dbErr,
    "edah_id", "edot");
fillId2Name($archiveYear, $bunkId2Name, $dbErr,
    "bunk_id", "bunks");

$archiveText = "";
if (!empty($availableArchiveYears)) {
    $archiveText = "<p>To view data from previous summers, choose a year from the Year drop-down menu</p>";
}

$chug_term_singular = ucfirst(chug_term_singular);
$actionTarget = htmlspecialchars($_SERVER["PHP_SELF"]);
$pageStart = <<<EOM
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function(event) {
        var report_method = document.getElementById("report_method");
        report_method.addEventListener("change", function(e) {
            window.location.href = window.location.pathname + "?report_method=" + e.target.value
        });
    });
</script>

<div class="card card-body mt-3 mb-3 container">

<h1><a>$chug_term_singular Assignment Report</a></h1>
<form id="main_form" method="GET" action="$actionTarget">
<div class="page-header">
<h2>$chug_term_singular Assignment Report</h2>
<p>Start by choosing a report type, then select filters as needed.  Required options are marked with a <font color="red">*</font>.</p>
$archiveText
</div>
<ul>

EOM;
if ($outputType == OutputTypes::Html) {
    echo $pageStart;
}

// Always show the report method drop-down and archive year menu (if any).
$reportMethodDropDown = new FormItemDropDown("Report Type", true, "report_method", 0);
$reportMethodDropDown->setGuideText("<b>Step 1:</b> Choose your report type.  <br><u>Yoetzet/Rosh " . ucfirst(edah_term_singular) . "</u> report is by " . edah_term_plural . ".<br><u>Madrich</u> report is by bunk.<br><u>" . ucfirst(chug_term_singular) . " leader</u> report is by " . chug_term_singular . ".<br><u>Director</u> report shows assignments for the whole camp.<br><u>Camper Prefs</u> report shows camper preferences and assignment, if any.<br><u>" . $reportMethodId2Name[ReportTypes::ChugimWithSpace] . "</u> report shows " . chug_term_plural . " with space remaining.<br><u>Campers missing preferences</u> report shows campers who are in the system, but who have not entered preferences for a particular time " . block_term_singular . ".<br><u>Campers not Assigned to " . ucfirst(chug_term_singular) . "</u> report shows which campers do not yet have " . chug_term_singular . " assignments.");
$reportMethodDropDown->setPlaceHolder("Choose Type");
$reportMethodDropDown->setId2Name($reportMethodId2Name);
$reportMethodDropDown->setColVal($reportMethod);
$reportMethodDropDown->setInputSingular("Report Type");
if ($reportMethod) {
    $reportMethodDropDown->setInputValue($reportMethod);
}

$archiveYearDropDown = null;
if (!empty($availableArchiveYears)) {
    $defaultYear = yearOfCurrentSummer();
    $archiveYearDropDown = new FormItemDropDown("Year", false, "archive_year", 1);
    $archiveYearDropDown->setGuideText("<b>Optional:</b> To view archived data from a previous summer, choose the year here. To see current data, leave this option set to $defaultYear.");
    $archiveYearDropDown->setPlaceHolder("Current Year");
    $archiveYearDropDown->setId2Name($availableArchiveYears);
    $archiveYearDropDown->setColVal($archiveYear);
    $archiveYearDropDown->setInputSingular("Year");
    $archiveYearDropDown->setDefaultMsg($defaultYear);
    if ($archiveYear) {
        $archiveYearDropDown->setInputValue($archiveYear);
    }
}

if ($outputType == OutputTypes::Html) {
    echo $reportMethodDropDown->renderHtml();
    if (!is_null($archiveYearDropDown)) {
        echo $archiveYearDropDown->renderHtml();
    }
}

if ($reportMethod &&
    $outputType == OutputTypes::Html) {
    // Add a hidden field to all HTML reports, indicating that the page should display a report
    // when this submit comes in.
    echo "<input type=\"hidden\" name=\"do_report\" id=\"do_report\" value=\"1\" />";
}

// All report methods except AllRegisteredCampers include a time block filter.
$liNumCounter = 0;
if ($reportMethod &&
    $reportMethod != ReportTypes::AllRegisteredCampers) {
    if ($reportMethod == ReportTypes::RegisteredMissingPrefs) {
        // For missing prefs, the user must select exactly one time block (to avoid confusion by the user).
        $blockChooser = new FormItemDropDown("Time " . ucfirst(block_term_plural) . " Missing Preferences", true, "block_id", $liNumCounter++);
        $blockChooser->setGuideText("<b>Step 2:</b> Select one time " . block_term_singular . ". The report will show campers who are missing preferences for this " . block_term_singular . ".");
        $blockChooser->setPlaceHolder("Choose a Time " . ucfirst(block_term_singular));
        $blockChooser->setInputClass("element medium");
        $blockChooser->setId2Name($blockId2Name);
        $blockChooser->setColVal($blockId);
        $blockChooser->setInputSingular("block");
    } else {
        $blockChooser = new FormItemInstanceChooser("Time " . ucfirst(block_term_plural), false, "block_ids", $liNumCounter++);
        $blockChooser->setId2Name($blockId2Name);
        $blockChooser->setActiveIdHash($activeBlockIds);
        $blockChooser->setGuideText("<b>Step 2:</b> Choose the time " . block_term_plural . " you wish to display.  If you do not choose any, all " . block_term_plural . " will be shown.");
    }
    if ($outputType == OutputTypes::Html) {
        echo $blockChooser->renderHtml();
    }
}

// If we have a report method specified, display the appropriate filter fields.
if ($reportMethod == ReportTypes::ByEdah) {
    // Display an optional Edah drop-down filter.
    $edahChooser = new FormItemInstanceChooser(ucfirst(edah_term_plural), false, "edah_ids", $liNumCounter++);
    $edahChooser->setId2Name($edahId2Name);
    $edahChooser->setActiveIdHash($activeEdahIds);
    $edahChooser->setGuideText("<b>Step 3:</b> Choose one or more " . edah_term_plural . " for your report, or leave empty to see all " . edah_term_plural);
    if ($outputType == OutputTypes::Html) {
        echo $edahChooser->renderHtml();
    }
} else if ($reportMethod == ReportTypes::ByBunk) {
    // Same as edah, but with a bunk filter.
    $bunkChooser = new FormItemDropDown("Bunk", false, "bunk_id", $liNumCounter++);
    $bunkChooser->setGuideText("<b>Step 3:</b> Choose a bunk/tzrif, or leave empty to see all bunks");
    $bunkChooser->setInputClass("element medium");
    $bunkChooser->setInputSingular("bunk");
    $bunkChooser->setColVal($bunkId);
    $bunkChooser->setId2Name($bunkId2Name);

    if ($outputType == OutputTypes::Html) {
        echo $bunkChooser->renderHtml();
    }
} else if ($reportMethod == ReportTypes::ByChug || $reportMethod == ReportTypes::ByChugRoshAndDepartment) {
    // Similar to the above, but the filter is by chug.  Also, in this case, the
    // input is required (the user must choose at least one chug).
    $chugChooser = new FormItemInstanceChooser(ucfirst(chug_term_plural), true, "chug_ids", $liNumCounter++);
    $chugChooser->setId2Name($chugId2Name);
    $chugChooser->setActiveIdHash($activeChugIds);
    $chugChooser->setGuideText("<b>Step 3:</b> Choose one or more " . chug_term_plural . " for this report.");

    if ($outputType == OutputTypes::Html) {
        echo $chugChooser->renderHtml();
    }
} else if ($reportMethod == ReportTypes::CamperChoices ||
    $reportMethod == ReportTypes::ChugimWithSpace ||
    $reportMethod == ReportTypes::RegisteredMissingPrefs ||
    $reportMethod == ReportTypes::NotAssignedCampers) {
    // The camper choices report can be filtered by group and
    // block.  The same applies to chugim with space and campers not
    // assigned to a chug.  Missing prefs is similar,
    // except it filters by session and not by group.
    $edahChooser = new FormItemInstanceChooser("Show Campers in these " . ucfirst(edah_term_plural), false, "edah_ids", $liNumCounter++);
    $edahChooser->setGuideText("<b>Step 3:</b> Choose one or more " . edah_term_plural . ", or leave empty to see all " . edah_term_plural . ".");
    $edahChooser->setActiveIdHash($activeEdahIds);
    $edahChooser->setId2Name($edahId2Name);

    if ($reportMethod == ReportTypes::RegisteredMissingPrefs) {
        $sessionChooser = new FormItemInstanceChooser("Show Campers Registered for these Sessions", false, "session_ids", $liNumCounter++);
        $sessionChooser->setGuideText("<b>Step 4:</b> Choose a session, or leave empty to see all sessions");
        $sessionChooser->setActiveIdHash($activeSessionIds);
        $sessionChooser->setId2Name($sessionId2Name);
    } else {
        $groupChooser = new FormItemInstanceChooser("Groups", false, "group_ids", $liNumCounter++);
        $groupChooser->setGuideText("<b>Step 4:</b> Choose one or more " . chug_term_singular . " groups, or leave empty to see all groups.");
        $groupChooser->setActiveIdHash($activeGroupIds);
        $groupChooser->setId2Name($groupId2Name);
    }

    if ($outputType == OutputTypes::Html) {
        echo $edahChooser->renderHtml();
        if ($reportMethod == ReportTypes::RegisteredMissingPrefs) {
            echo $sessionChooser->renderHtml();
        } else {
            echo $groupChooser->renderHtml();
        }
    }
} else if ($reportMethod == ReportTypes::Director) {
    // The director report shows all options, so there are no filter fields
    // except time block.

} else if ($reportMethod == ReportTypes::AllRegisteredCampers ||
    $reportMethod == ReportTypes::CamperHappiness) {
    // Display optional edah and session filters.
    // For the AllRegisteredCampers report, the step level
    // here is 2, because we did not display a block filter.
    $step = ($reportMethod == ReportTypes::AllRegisteredCampers) ? 2 : 3;
    $edahChooser = new FormItemInstanceChooser("Show Campers in these " . ucfirst(edah_term_plural), false, "edah_ids", $liNumCounter++);
    $edahChooser->setGuideText("<b>Step $step:</b> Choose one or more " . edah_term_plural . ", or leave empty to see all " . edah_term_plural . ".");
    $edahChooser->setActiveIdHash($activeEdahIds);
    $edahChooser->setId2Name($edahId2Name);
    $step++;


    $sessionChooser = new FormItemInstanceChooser("Show Campers Registered for these Sessions", false, "session_ids", $liNumCounter++);
    $sessionChooser->setGuideText("<b>Step $step:</b> Choose a session, or leave empty to see all sessions");
    $sessionChooser->setActiveIdHash($activeSessionIds);
    $sessionChooser->setId2Name($sessionId2Name);

    if ($outputType == OutputTypes::Html) {
        echo $edahChooser->renderHtml();
        echo $sessionChooser->renderHtml();
    }
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

if ($outputType == OutputTypes::Html) {
    echo "<li class=\"buttons\">";
    echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\"$buttonText\" />";

    echo "<a class=\"btn btn-link\" href=\"$cancelUrl\">Home</a>";
    if ($doReport) {
        echo "<br><br><input class=\"btn btn-light btn-outline-secondary me-3\" type=\"submit\" name=\"print\" title=\"Print this table\" value=\"Print...\" />";
        echo "<input class=\"btn btn-light btn-outline-secondary\" type=\"submit\" name=\"export\" title=\"Export to a file\" value=\"Export\" />";
    }
    echo "</li></ul></form>";

    echo "<form id=\"reset_form\" method=\"GET\" action=\"$actionTarget\">";
    echo "<ul><li class=\"buttons\">";
    echo "<input id=\"resetFormButton\" class=\"btn btn-light btn-outline-secondary mb-3\" type=\"submit\" name=\"reset\" value=\"Reset\" />";
    echo "</li></ul></form>";
}

if ($doReport) {
    // Prepare and display the report, setting the SQL according to the report
    // type.  If we have an archive year, pull from that database.
    $edahText = "";
    $i = 0;
    foreach ($activeEdahIds as $edah_id => $val) {
        $i++;
        $edahName = $edahId2Name[$edah_id];
        if (empty($edahText)) {
            $edahText = $edahName;
        } else if ($i == count($activeEdahIds)) {
            $edahText .= " and " . $edahName;
        } else {
            $edahText .= ", " . $edahName;
        }
    }
    $db = new DbConn($archiveYear);
    $db->isSelect = true;
    if ($reportMethod == ReportTypes::AllRegisteredCampers) {
        // List all campers in the system in one report, sorted by session, edah, and name.
        $sql = "SELECT CONCAT(c.last, ', ', c.first) AS name, s.name session, s.session_id session_id, e.name edah, e.edah_id edah_id FROM " .
            "campers c, sessions s, edot e " .
            "WHERE c.session_id = s.session_id AND c.edah_id = e.edah_id ";
        addWhereClause($sql, $db, $activeEdahIds,
            "c.edah_id", true);
        addWhereClause($sql, $db, $activeSessionIds,
            "c.session_id", true);
        addWhereClause($sql, $db, $activeSessionIds,
            "c.session_id", true);
        $sql .= "ORDER BY name, edah, session";
        $allCampersReport = new ZebraReport($db, $sql, $outputType);
        $allCampersReport->setReportTypeAndNameOfItem("All Campers", "Whole Camp");
        $allCampersReport->addIgnoreColumn("session_id");
        $allCampersReport->addIgnoreColumn("edah_id");
        //$allCampersReport->addNewTableColumn("edah");
        //$allCampersReport->addNewTableColumn("session");
        //$allCampersReport->setCaption("LINK0 Campers in Session LINK1");
        //$allCampersReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
        //$allCampersReport->setIdCol2EditPage("session_id", "editSession.php", "session");
        $allCampersReport->renderTable($generateCheckboxes=false);
    } else if ($reportMethod == ReportTypes::ByEdah) {
        // Note: this report was improved to show each camper and their assignments on one line.
        // It largely built off of the code for the CamperHappiness report, just removing preferences and adding bunks

        // First, get a list of all groups with at least one match for the selected
        // block/edot/session.  Use this to make a chug-group clause, which we'll
        // use in our main SELECT.
        $localErr = "";
        $dbc = new DbConn();
        $sql = "SELECT DISTINCT g.name groupname " .
            "FROM chug_groups g, matches m, chug_instances i, chugim c, campers ca " .
            "WHERE i.chug_instance_id = m.chug_instance_id " .
            "AND i.chug_id = c.chug_id " .
            "AND c.group_id = g.group_id " .
            "AND m.camper_id = ca.camper_id ";
        addWhereClause($sql, $dbc, $activeBlockIds,
            "i.block_id", true);
        addWhereClause($sql, $dbc, $activeEdahIds,
            "ca.edah_id", true);
        $sql .= "ORDER BY groupname";
        $result = $dbc->doQuery($sql, $localErr);
        if ($result == false) {
            echo dbErrorString($sql, $localErr);
            exit();
        }
        if ($result->num_rows == 0) {
            // If we did not find any preferences, report no rows.
            $sql = "";
        } else {
            // If we have prefs and matches, we can build our main SQL.
            // Otherwise, we'll use the SQL from above, which will report
            // zero rows found.
            $groupClause = "";
            $i = 0;
            $chugGroups = []; // Array which will hold every applicable chug group
            while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                $gn = $row[0];
                array_push($chugGroups, $gn);
                $groupClause .= " max(case when p.group_name = \"" . $gn . "\" then p.chug_name else null end)\"" . $gn . "\", " .
                "max(case when p.group_name = \"" . $gn . "\" then p.chug_id else null end)\"" . $gn . " ID\"";
                if ($i++ < ($result->num_rows - 1)) {
                    $groupClause .= ", ";
                }
            }
            // Now, build our actual query, using the group clause we just created.
            // The parentheses around the FROM tables ahead of the left join on preferences
            // are essential, because of a quirk in the way MySQL evaluates statements:
            $sql = "SELECT CONCAT(c.last, ', ', c.first) name, c.camper_id camper_id, IFNULL(bu.name,\"-\") bunk, bu.bunk_id, " . 
            "e.name edah, e.edah_id, e.sort_order edah_sort_order, e.rosh_name rosh, e.rosh_phone roshphone, p.block_id block_id, b.name block";
            if ($groupClause) {
                 $sql .= ", " . $groupClause;
            }
            $sql .= "FROM campers c, bunks bu, edot e, " .
            "(SELECT " .
            "m.camper_id camper_id, c.name chug_name, c.chug_id chug_id, g.name group_name, b.name block_name, b.block_id block_id " .
            "FROM chug_groups g, blocks b, (matches m, chug_instances i, chugim c) " .
            "LEFT OUTER JOIN preferences p " .
            "ON p.group_id = c.group_id AND p.block_id = i.block_id AND m.camper_id = p.camper_id " .
            "WHERE i.block_id = b.block_id " .
            "AND c.chug_id = i.chug_id " .
            "AND c.group_id = g.group_id " .
            "AND m.chug_instance_id = i.chug_instance_id ";
        addWhereClause($sql, $db, $activeBlockIds,
            "i.block_id", true);
        $sql .= ") p " .
                "JOIN blocks AS b ON b.block_id = p.block_id ".
                "WHERE c.camper_id = p.camper_id " . 
                "AND c.edah_id = e.edah_id " .
                "AND c.bunk_id = bu.bunk_id ";
            addWhereClause($sql, $db, $activeEdahIds,
                "c.edah_id", true);
            $sql .= " GROUP BY camper_id, block_id ORDER BY edah_sort_order, edah, block, name";
        }
        $edahReport = new ZebraReport($db, $sql, $outputType);
        $edahReport->setReportTypeAndNameOfItem("Edah", $edahText);
        $edahReport->addNewTableColumn("edah");
        $edahReport->setCaption(ucfirst(chug_term_singular). " Assignments by " . ucfirst(edah_term_singular) . " for LINK0<br>Rosh: ROSH, PHONE");
        $edahReport->addIgnoreColumn("edah_sort_order");
        $edahReport->addIgnoreColumn("rosh");
        $edahReport->addIgnoreColumn("roshphone");
        $edahReport->setIdCol2EditPage("camper_id", "editCamper.php", "name");
        $edahReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
        $edahReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
        if(!empty($chugGroups)) {
            foreach ($chugGroups as $groupName) {
                $edahReport->setIdCol2EditPage($groupName . " ID", "editChug.php", $groupName . " Assignment");
            }
        }
        $edahReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
        $edahReport->addCaptionReplaceColKey("ROSH", "rosh", "none listed");
        $edahReport->addCaptionReplaceColKey("PHONE", "roshphone", "no rosh phone");
        $edahReport->renderTable($generateCheckboxes=false);
    } else if ($reportMethod == ReportTypes::ByBunk) {
        // Per-bunk report.  This the same as the per-edah report, except
        // organized by bunk.
        $sql = "SELECT CONCAT(c.last, ', ', c.first) AS name, IFNULL(b.name,\"-\") bunk, bl.name block, e.name edah, e.sort_order edah_sort_order, " .
            "g.name group_name, ch.name assignment, c.camper_id camper_id, b.bunk_id bunk_id, e.edah_id edah_id, g.group_id group_id, " .
            "ch.chug_id chug_id, bl.block_id block_id " .
            "FROM matches AS m " .
            "JOIN chug_instances AS i ON m.chug_instance_id = i.chug_instance_id " .
            "JOIN blocks AS bl ON i.block_id = bl.block_id " .
            "JOIN chugim AS ch ON i.chug_id = ch.chug_id " .
            "JOIN campers AS c ON c.camper_id = m.camper_id " .
            "JOIN edot AS e ON c.edah_id = e.edah_id " .
            "JOIN chug_groups AS g ON g.group_id = ch.group_id " .
            "LEFT OUTER JOIN bunks b ON c.bunk_id = b.bunk_id ";
        $haveWhere = addWhereClause($sql, $db, $activeBlockIds);
        if ($bunkId) {
            if (!$haveWhere) {
                $sql .= "WHERE b.bunk_id = ? ";
            } else {
                $sql .= "AND b.bunk_id = ? ";
            }
            $db->addColVal($bunkId, 'i');
        }
        $sql .= "ORDER BY bunk, name, edah_sort_order, edah, group_name";

        // Create and display the report.
        $bunkReport = new ZebraReport($db, $sql, $outputType);
        $bunkReport->setReportTypeAndNameOfItem("Bunk", $bunkId2Name[$bunkId]);
        $bunkReport->addNewTableColumn("bunk");
        $bunkReport->setCaption(ucfirst(chug_term_singular). " Assignments by Bunk for LINK0");
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
            "e.rosh_name rosh, e.rosh_phone roshphone, ch.name chug_name, IFNULL(b.name, \"-\") bunk, bl.name block, " .
            "ch.chug_id chug_id, bl.block_id block_id, b.bunk_id bunk_id, e.edah_id edah_id, c.camper_id " .
            "FROM edot AS e " .
            "JOIN campers AS c ON c.edah_id = e.edah_id " .
            "JOIN matches AS m ON m.camper_id = c.camper_id " .
            "JOIN chug_instances AS i ON i.chug_instance_id = m.chug_instance_id " .
            "JOIN chugim AS ch ON ch.chug_id = i.chug_id " .
            "JOIN blocks AS bl ON bl.block_id = i.block_id " .
            "LEFT OUTER JOIN bunks AS b ON b.bunk_id = c.bunk_id ";
        $haveWhere = addWhereClause($sql, $db, $activeBlockIds);
        addWhereClause($sql, $db, $activeChugIds, "ch.chug_id",
            $haveWhere);
        $sql .= " ORDER BY chug_name, edah_sort_order, edah, block, camper, bunk";

        $chugReport = new ZebraReport($db, $sql, $outputType);
        $chugReport->addNewTableColumn("chug_name");
        $chugReport->addNewTableColumn("edah");
        $chugReport->addNewTableColumn("block");
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
        $chugReport->setCaption("LINK0: LINK1 campers for LINK2<br>Rosh: ROSH, PHONE");
        $chugReport->addCaptionReplaceColKey("ROSH", "rosh", "none listed");
        $chugReport->addCaptionReplaceColKey("PHONE", "roshphone", "no rosh phone");
        $chugReport->addCaptionReplaceColKey("BLOCK", "block", "no " . block_term_singular . " name");
        $chugReport->renderTable();
    } else if ($reportMethod == ReportTypes::ByChugRoshAndDepartment) {
        $sql = "SELECT CONCAT(c.last, ', ', c.first) AS camper, e.name edah, e.sort_order edah_sort_order, " .
            "ch.rosh_name rosh, ch.department_name department_name, ch.name chug_name, IFNULL(b.name, \"-\") bunk, bl.name block, " .
            "ch.chug_id chug_id, bl.block_id block_id, b.bunk_id bunk_id, e.edah_id edah_id, c.camper_id " .
            "FROM edot AS e " .
            "JOIN campers AS c ON c.edah_id = e.edah_id " .
            "JOIN matches AS m ON m.camper_id = c.camper_id " .
            "JOIN chug_instances AS i ON i.chug_instance_id = m.chug_instance_id " .
            "JOIN chugim AS ch ON ch.chug_id = i.chug_id " .
            "JOIN blocks AS bl ON bl.block_id = i.block_id " .
            "LEFT OUTER JOIN bunks AS b ON b.bunk_id = c.bunk_id ";
        $haveWhere = addWhereClause($sql, $db, $activeBlockIds);
        addWhereClause($sql, $db, $activeChugIds, "ch.chug_id",
            $haveWhere);
        $sql .= " ORDER BY department_name, rosh, chug_name, edah_sort_order, edah, block, camper, bunk";

        $chugReport = new ZebraReport($db, $sql, $outputType);
        $chugReport->addNewTableColumn("chug_name");
        $chugReport->addNewTableColumn("edah");
        $chugReport->addNewTableColumn("block");
        $chugReport->addIgnoreColumn("edah_sort_order");
        $chugReport->addIgnoreColumn("rosh");
        $chugReport->addIgnoreColumn("department_name");
        $chugReport->addIgnoreColumn("chug_name");
        $chugReport->addIgnoreColumn("block");
        $chugReport->setIdCol2EditPage("camper_id", "editCamper.php", "camper");
        $chugReport->setIdCol2EditPage("chug_id", "editChug.php", "chug_name");
        $chugReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
        $chugReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
        $chugReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
        $chugReport->setCaption("DEPARTMENT - LINK0: LINK1 campers for LINK2<br>Rosh: ROSH");
        $chugReport->addCaptionReplaceColKey("ROSH", "rosh", "none listed");
        $chugReport->addCaptionReplaceColKey("DEPARTMENT", "department_name", "No department");
        $chugReport->addCaptionReplaceColKey("BLOCK", "block", "no " . block_term_singular . " name");
        $chugReport->renderTable();
    } else if ($reportMethod == ReportTypes::CamperChoices) {
        // Report camper choices (1-6) and assignment, if any.
        $sql = "SELECT CONCAT(c.last, ', ', c.first) AS name, bl.name block, e.name edah, e.sort_order edah_sort_order, " .
            "bl.name block, g.name group_name, IFNULL(ma.chug_name, \"Not Assigned Yet\") assignment, e.edah_id edah_id, g.group_id group_id, bl.block_id block_id, " .
            "p.first_choice_id first_choice, p.second_choice_id second_choice, p.third_choice_id third_choice, " .
            "p.fourth_choice_id fourth_choice, p.fifth_choice_id fifth_choice, p.sixth_choice_id sixth_choice " .
            "FROM campers AS c " .
            "JOIN preferences AS p ON c.camper_id = p.camper_id " .
            "JOIN chug_groups AS g ON g.group_id = p.group_id " .
            "JOIN blocks AS bl ON bl.block_id = p.block_id " .
            "JOIN edot AS e ON c.edah_id = e.edah_id " .
            "LEFT OUTER JOIN  " .
            "(SELECT ma.camper_id camper_id, i.block_id block_id, ch.group_id group_id, ch.name chug_name " .
            "FROM matches AS ma, chug_instances AS i, chugim AS ch " .
            "WHERE ma.chug_instance_id = i.chug_instance_id " .
            "AND i.chug_id = ch.chug_id) AS ma " .
            "ON ma.camper_id = c.camper_id AND ma.block_id = bl.block_id AND ma.group_id = g.group_id ";
        $haveWhere = addWhereClause($sql, $db, $activeBlockIds);
        $haveWhere = addWhereClause($sql, $db, $activeEdahIds,
            "c.edah_id", $haveWhere);
        $haveWhere = addWhereClause($sql, $db, $activeGroupIds,
            "g.group_id", $haveWhere);
        $sql .= "ORDER BY edah_sort_order, edah, block, name, group_name";
        $camperReport = new ZebraReport($db, $sql, $outputType);
        $camperReport->setIdNameMap($chugId2Name, " -");
        $camperReport->addIdNameMapCol("first_choice");
        $camperReport->addIdNameMapCol("second_choice");
        $camperReport->addIdNameMapCol("third_choice");
        $camperReport->addIdNameMapCol("fourth_choice");
        $camperReport->addIdNameMapCol("fifth_choice");
        $camperReport->addIdNameMapCol("sixth_choice");
        $camperReport->addNewTableColumn("edah"); // New table when edah changes
        $camperReport->addNewTableColumn("block"); // New table when block changes
        $camperReport->setCaption("LINK0 Camper Preferences and Assignments for LINK1");
        $camperReport->addIgnoreColumn("edah_sort_order");
        $camperReport->addIgnoreColumn("block");
        $camperReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
        $camperReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
        $camperReport->setIdCol2EditPage("group_id", "editGroup.php", "group_name");
        $camperReport->addCaptionReplaceColKey("BLOCK", "block", "no " . block_term_singular . " name");
        $camperReport->setPdfFontSizeAndMult(9.0, 2.0);
        $camperReport->renderTable();
    } else if ($reportMethod == ReportTypes::Director) {
        // The director report is similar to the edah report, but unfiltered.
        $sql = "SELECT c.last AS last_name, c.first AS first_name, IFNULL(b.name, \"-\") bunk, bl.name block, e.name edah, e.sort_order edah_sort_order, " .
            "g.name group_name, ch.name assignment, c.camper_id camper_id, b.bunk_id bunk_id, e.edah_id edah_id, g.group_id group_id, " .
            "ch.chug_id chug_id, bl.block_id block_id " .
            "FROM matches AS m " .
            "JOIN chug_instances AS i ON i.chug_instance_id = m.chug_instance_id " .
            "JOIN blocks AS bl ON bl.block_id = i.block_id " .
            "JOIN chugim AS ch ON ch.chug_id = i.chug_id " .
            "JOIN campers AS c ON c.camper_id = m.camper_id " .
            "JOIN edot AS e ON e.edah_id = c.edah_id " .
            "JOIN chug_groups AS g ON g.group_id = ch.group_id " .
            "LEFT OUTER JOIN bunks AS b ON b.bunk_id = c.bunk_id ";
        addWhereClause($sql, $db, $activeBlockIds);
        $sql .= " ORDER BY edah_sort_order, edah, last_name, first_name, block, group_name";

        // Create and display the report.
        $directorReport = new ZebraReport($db, $sql, $outputType);
        $directorReport->addNewTableColumn("edah");
        $directorReport->setCaption(ucfirst(chug_term_singular). " Assignments by Edah for LINK0");
        $directorReport->addIgnoreColumn("edah_sort_order");
        $directorReport->setIdCol2EditPage("camper_id", "editCamper.php", "last_name");
        $directorReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
        $directorReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");
        $directorReport->setIdCol2EditPage("group_id", "editGroup.php", "group_name");
        $directorReport->setIdCol2EditPage("chug_id", "editChug.php", "assignment");
        $directorReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
        $directorReport->renderTable();
    } else if ($reportMethod == ReportTypes::ChugimWithSpace) {
        // Display chugim with space, filtering by edah, block, and
        // group, as requested.

        // First, build a sub-query that computes the assigned count
        // for each chug.
        $inner = "SELECT c.chug_id chug_id, b.name block_name, count(*) match_count FROM matches m, chugim c, chug_instances i, blocks b, campers ca " .
            "WHERE m.chug_instance_id = i.chug_instance_id AND i.chug_id = c.chug_id AND i.block_id = b.block_id AND ca.camper_id = m.camper_id ";
        addWhereClause($inner, $db, $activeGroupIds,
            "c.group_id", true);
        addWhereClause($inner, $db, $activeBlockIds,
            "b.block_id", true);
        addWhereClause($inner, $db, $activeEdahIds,
            "ca.edah_id", true);
        $inner .= " GROUP BY 1, 2";

        // Now, build and fun the full query.
        $fullSql = "SELECT c.chug_id chug_id, CONCAT(c.name, ' (', g.name, ')') chug_name, " .
            "a.match_count num_campers_assigned, a.block_name " . block_term_singular . "_name, " .
            "CASE WHEN c.max_size = 0 OR c.max_size = " . MAX_SIZE_NUM . " THEN \"No limit\" ELSE c.max_size END num_campers_allowed " .
            "FROM chugim c, chug_groups g, (";
        $fullSql .= $inner;
        $fullSql .= ") a " .
            "WHERE c.chug_id = a.chug_id AND c.group_id = g.group_id AND " .
            "(a.match_count < c.max_size OR c.max_size = 0 OR c.max_size = " . MAX_SIZE_NUM . ")";
        $chugimWithSpaceReport = new ZebraReport($db, $fullSql, $outputType);
        $chugimWithSpaceReport->addNewTableColumn("edah");
        $caption = ucfirst(chug_term_plural) . " With Free Space";
        if (!empty($edahText)) {
            $caption .= " for " . $edahText;
        }
        $chugimWithSpaceReport->setCaption($caption);
        $chugimWithSpaceReport->setIdCol2EditPage("chug_id", "editChug.php", "chug_name");
        $chugimWithSpaceReport->addIgnoreColumn("max_campers");
        $chugimWithSpaceReport->renderTable($generateCheckboxes=false);
    } else if ($reportMethod == ReportTypes::CamperHappiness) {
        // First, get a list of all groups with at least one match for the selected
        // block/edot/session.  Use this to make a chug-group clause, which we'll
        // use in our main SELECT.
        $localErr = "";
        $dbc = new DbConn();
        $sql = "SELECT DISTINCT g.name groupname " .
            "FROM chug_groups g, matches m, chug_instances i, chugim c, campers ca " .
            "WHERE i.chug_instance_id = m.chug_instance_id " .
            "AND i.chug_id = c.chug_id " .
            "AND c.group_id = g.group_id " .
            "AND m.camper_id = ca.camper_id ";
        addWhereClause($sql, $dbc, $activeBlockIds,
            "i.block_id", true);
        addWhereClause($sql, $dbc, $activeEdahIds,
            "ca.edah_id", true);
        if ($sessionId) {
            $sql .= "AND ca.session_id = ? ";
            $dbc->addColVal($sessionId, 'i');
        }
        $sql .= "ORDER BY groupname";
        $result = $dbc->doQuery($sql, $localErr);
        if ($result == false) {
            echo dbErrorString($sql, $localErr);
            exit();
        }
        if ($result->num_rows == 0) {
            // If we did not find any preferences, report no rows.
            $sql = "";
        } else {
            // If we have prefs and matches, we can build our main SQL.
            // Otherwise, we'll use the SQL from above, which will report
            // zero rows found.
            $groupClause = "";
            $i = 0;
            while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                $gn = $row[0];
                $groupClause .= " max(case when p.group_name = \"" . $gn . "\" then p.chug_name else null end) \"" .
                    $gn . " Assignment\", max(case when p.group_name = \"" . $gn . "\" then p.happiness_level else null end) \"" .
                    $gn . " Pref Level\" ";
                if ($i++ < ($result->num_rows - 1)) {
                    $groupClause .= ", ";
                }
            }
            // Now, build our actual query, using the group clause we just created.
            // The parentheses around the FROM tables ahead of the left join on preferences
            // are essential, because of a quirk in the way MySQL evaluates statements:
            //
            $sql = "SELECT CONCAT(c.last, ', ', c.first) name, c.camper_id camper_id, p.block_name " . block_term_singular . ", p.block_id block_id ";
            if ($groupClause) {
                $sql .= ", " . $groupClause;
            }
            $sql .= "FROM campers c, " .
                "(SELECT " .
                "m.camper_id camper_id, c.name chug_name, g.name group_name, b.name block_name, b.block_id, " .
                "CASE WHEN i.chug_id = p.first_choice_id THEN 1 " .
                "WHEN i.chug_id = p.second_choice_id THEN 2 " .
                "WHEN i.chug_id = p.third_choice_id THEN 3 " .
                "WHEN i.chug_id = p.fourth_choice_id THEN 4 " .
                "WHEN i.chug_id = p.fifth_choice_id THEN 5 " .
                "WHEN i.chug_id = p.sixth_choice_id THEN 6 " .
                "ELSE \"no pref\" " .
                "END AS happiness_level " .
                "FROM chug_groups g, blocks b, (matches m, chug_instances i, chugim c) " .
                "LEFT OUTER JOIN preferences p " .
                "ON p.group_id = c.group_id AND p.block_id = i.block_id AND m.camper_id = p.camper_id " .
                "WHERE i.block_id = b.block_id " .
                "AND c.chug_id = i.chug_id " .
                "AND c.group_id = g.group_id " .
                "AND m.chug_instance_id = i.chug_instance_id ";
            addWhereClause($sql, $db, $activeBlockIds,
                "i.block_id", true);
            $sql .= ") p " .
                "WHERE c.camper_id = p.camper_id ";
            if ($sessionId) {
                $sql .= " AND c.session_id = ? ";
                $db->addColVal($sessionId, 'i');
            }
            addWhereClause($sql, $db, $activeEdahIds,
                "c.edah_id", true);
            $sql .= " GROUP BY camper_id, " . block_term_singular . " ORDER BY name, " . block_term_singular;
        }
        $camperHappinessReport = new ZebraReport($db, $sql, $outputType);
        $camperHappinessReport->addNewTableColumn("edah");
        $caption = "Camper Happiness Report";
        if (!empty($edahText)) {
            $caption .= " for " . $edahText;
        }
        if ($sessionId) {
            $caption .= " for " . $sessionId2Name[$sessionId];
        }
        $camperHappinessReport->setCaption($caption);
        $camperHappinessReport->setIdCol2EditPage("camper_id", "editCamper.php", "name");
        $camperHappinessReport->setIdCol2EditPage("block_id", "editBlock.php", "block");
        $camperHappinessReport->addIgnoreColumn("camper_id");
        $camperHappinessReport->addIgnoreColumn("block_id");
        $camperHappinessReport->renderTable($generateCheckboxes=false);
    } else if ($reportMethod == ReportTypes::RegisteredMissingPrefs) {
        $sql = "SELECT DISTINCT a.name name, a.email email, a.edah edah, a.session session FROM " .
            "(SELECT CONCAT(c.last, ', ', c.first) AS name, c.email email, e.name edah, s.name session, p.preference_id pref_id " .
            "FROM edot e, sessions s, campers c LEFT OUTER JOIN " .
            "(SELECT * FROM preferences ";
        // Optionally filter prefs by block.
        if (!empty($blockId)) {
            $sql .= " WHERE block_id = $blockId";
        }
        $sql .= ") p ON p.camper_id = c.camper_id ";
        // Optionally filter campers by edah and session.
        $haveWhere = false;
        $haveWhere = addWhereClause($sql, $db, $activeEdahIds, "c.edah_id");
        $haveWhere = addWhereClause($sql, $db, $activeSessionIds, "c.session_id", $haveWhere);
        if ($haveWhere) {
            $sql .= " AND ";
        } else {
            $sql .= " WHERE ";
        }
        $sql .= "c.edah_id = e.edah_id AND c.session_id = s.session_id) a ";
        $sql .= "WHERE a.pref_id IS NULL ORDER BY edah, session, name";

        $campersMissingPrefsReport = new ZebraReport($db, $sql, $outputType);
        $caption = "Campers Missing Preferences for ";
        $caption .= $blockId2Name[$blockId];
        addActiveItemsToCaption($caption, $activeEdahIds, "edot", $edahId2Name);
        addActiveItemsToCaption($caption, $activeSessionIds, "sessions", $sessionId2Name);
        $campersMissingPrefsReport->setCaption($caption);
        //$campersMissingPrefsReport->addNewTableColumn("edah");
        //$campersMissingPrefsReport->addNewTableColumn("session");
        $campersMissingPrefsReport->renderTable($generateCheckboxes=false);
    } else if ($reportMethod == ReportTypes::NotAssignedCampers) {
        // Display a list of campers (by perek) who have not yet been assigned to a chug

        // First, build an inner sql query creating a table with every camper and the desired chug groups/time blocks:
        $inner = "SELECT CONCAT(c.last, ', ', c.first) AS name, c.camper_id AS c_id, c.edah_id, e.name AS edah, e.sort_order edah_sort_order, " .
            "IFNULL(b.name,\"-\") bunk, bl.name AS block, bl.block_id as bl_id, c.camper_id, c.bunk_id, cg.name AS chug_group, cg.group_id " .
            "FROM campers AS c " .
            "JOIN edot AS e on c.edah_id = e.edah_id " .
            "JOIN edot_for_block AS eb ON c.edah_id = eb.edah_id " .
            "JOIN block_instances AS bi ON c.session_id=bi.session_id " .
            "JOIN blocks AS bl ON eb.edah_id = e.edah_id AND eb.block_id = bl.block_id AND bi.block_id=bl.block_id " .
            "JOIN edot_for_group AS eg ON c.edah_id = eg.edah_id " .
            "JOIN chug_groups AS cg ON eg.edah_id = e.edah_id AND eg.group_id = cg.group_id " .
            "LEFT OUTER JOIN bunks b ON c.bunk_id = b.bunk_id";

        $haveWhere = false;
        $haveWhere = addWhereClause($inner, $db, $activeGroupIds, "cg.group_id");
        $haveWhere = addWhereClause($inner, $db, $activeBlockIds, "bl.block_id", $haveWhere);
        $haveWhere = addWhereClause($inner, $db, $activeEdahIds, "c.edah_id", $haveWhere);

        // Now, build the full query.
        $fullSql = "SELECT * FROM (" . $inner . ") AS ca ";
        $fullSql .= "LEFT OUTER JOIN (SELECT m.camper_id, m.chug_instance_id, i.block_id, i.chug_instance_id AS \"inst\", ch.chug_id, ch.group_id AS g_id " .
            "FROM matches m, chug_instances i, chugim ch " .
            "WHERE m.chug_instance_id=i.chug_instance_id AND ch.chug_id=i.chug_id) AS ma " .
            "ON ma.camper_id=ca.camper_id AND ma.block_id=ca.bl_id AND ma.g_id=ca.group_id " .
            "WHERE ma.chug_instance_id IS NULL " .
            "ORDER BY edah_sort_order, edah, name, block, chug_group";

        $notAssignedReport = new ZebraReport($db, $fullSql, $outputType);
        $caption = "Campers Missing " . ucfirst(chug_term_singular) . " Assignment(s)";
        $notAssignedReport->setCaption($caption);

        $notAssignedReport->setIdCol2EditPage("c_id", "editCamper.php", "name");
        $notAssignedReport->setIdCol2EditPage("bunk_id", "editBunk.php", "bunk");
        $notAssignedReport->setIdCol2EditPage("bl_id", "editBlock.php", "block");
        $notAssignedReport->setIdCol2EditPage("group_id", "editGroup.php", "chug_group");
        $notAssignedReport->setIdCol2EditPage("edah_id", "editEdah.php", "edah");

        $notAssignedReport->addIgnoreColumn("block_id");
        $notAssignedReport->addIgnoreColumn("bunk_id");
        $notAssignedReport->addIgnoreColumn("edah_id");
        $notAssignedReport->addIgnoreColumn("edah_sort_order");
        $notAssignedReport->addIgnoreColumn("group_id");
        $notAssignedReport->addIgnoreColumn("camper_id");
        $notAssignedReport->addIgnoreColumn("chug_instance_id");
        $notAssignedReport->addIgnoreColumn("inst");
        $notAssignedReport->addIgnoreColumn("chug_id");
        $notAssignedReport->addIgnoreColumn("g_id");

        $notAssignedReport->renderTable($generateCheckboxes=false);
    }
}

if ($outputType == OutputTypes::Html) {
    echo "</div>";
    echo footerText();
    echo "</body></html>";
}
