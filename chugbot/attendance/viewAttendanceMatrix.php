<?php
    session_start();
    include_once '../dbConn.php';
    include_once '../functions.php';
    bounceToLogin("rosh");
    checkLogout();
    setup_camp_specific_terminology_constants();

    $dbErr = "";
    $sessionId2Name = array();
    $blockId2Name = array();
    $groupId2Name = array();
    $edahId2Name = array();
    $chugId2Name = array();
    $bunkId2Name = array();

    fillId2Name(null, $chugId2Name, $dbErr, "chug_id", "chugim", "group_id", "chug_groups");
    fillId2Name(null, $sessionId2Name, $dbErr, "session_id", "sessions");
    fillId2Name(null, $blockId2Name, $dbErr, "block_id", "blocks");
    fillId2Name(null, $groupId2Name, $dbErr, "group_id", "chug_groups");
    fillId2Name(null, $edahId2Name, $dbErr, "edah_id", "edot");
    fillId2Name(null, $bunkId2Name, $dbErr, "bunk_id", "bunks");

    echo headerText("View Attendance Matrix");

    // Get info from the form
    $startDate = test_get_input('start-date');
    $endDate = test_get_input('end-date');
    $edahId = test_get_input('edah');

    // additional function (at bottom of code) to ensure everything was properly formatted and valid values
    // were passed in
    validate_form_inputs();

    // get group ids for the edah to be used in creating individual tables
    $groupIds = array();
    $localErr = "";
    $dbc = new DbConn();
    $sql = "SELECT group_id FROM edot_for_group WHERE edah_id = $edahId";
    $result = $dbc->doQuery($sql, $localErr);
    if ($result == false) {
        echo dbErrorString($sql, $localErr);
        exit();
    }
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        array_push($groupIds, $row[0]);
    }
    if(count($groupIds) == 0) {
        echo "<div class=\"col-md-6 offset-md-3\"><div class=\"alert alert-danger alert-dismissible fade show m-2\" role=\"alert\">" . 
        "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button><h5><strong>Error:</strong> " . 
        "No Available Prakim</h5>No prakim are available for the provided " . edah_term_singular . ". Contact an administrator if you believe this to be an error.</div></div>";
        exit();
    }


?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="card card-body mt-2 p-3 mb-3 ms-5 me-5">
    <h1>View Attendance Matrix</h1>
    <div class="page-header"><h2><?php echo $edahId2Name[$edahId]; ?> Attendance Matrix</h2>
    <h4> Dates: <?php echo date("D F j, Y", strtotime($startDate)) . " - " . date("D F j, Y", strtotime($endDate)); ?> </h4>
    </div>

    <p>Utilize this attendance matrix to explore trends within an <?php echo edah_term_singular ?> by perek. There is a separate table for each perek below, and in each one, campers have their own row reflecting their attendance records.
    Campers marked as absent on a particular day have the corresponding cell highlighted in <span style="background:#f8d7da;">red</span> and denoted with this icon: <i class="bi bi-x-circle-fill"></i>. 
    Campers for whom attendance has not been taken have the cell for that day highlighted in <span style="background:#fff3cd;">yellow</span> and marked with this icon: <i class="bi bi-exclamation-triangle"></i>.
    Otherwise, campers marked as present have no highlighting and are noted with this icon: <i class="bi bi-check-circle"></i>.
    Hover over the corresponding icon for a camper on a particular day to see their attendance status and which <?php echo chug_term_singular; ?> they were assigned for that day.</p>


    <?php
    $tableCount = 0;
    foreach($groupIds as $groupId) {
        // step 1: SQL to get dates and correct block for that date
        $date2BlockId = array();
        $localErr = "";
        $dbc = new DbConn();
        $sql = "SELECT date, block_id FROM attendance_block_by_date WHERE group_id = $groupId AND date >= '$startDate' AND date <= '$endDate' ORDER BY date";
        $result = $dbc->doQuery($sql, $localErr);
        if ($result == false) {
            echo dbErrorString($sql, $localErr);
            exit();
        }
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            array_push($date2BlockId, array($row[0], $row[1])); // [date, block_id]
        }
        if(count($date2BlockId) == 0) {
            continue;
        }


        // step 2: build mega SQL to get camper, present/absent by date (also creates the heading for dates)
        $localErr = "";
        $db = new DbConn();
        // basic info
        $initialSql = "SELECT c.camper_id, b.name as bunk, CONCAT(c.last, ', ', c.first) AS name, ";
        foreach($date2BlockId as $dateBlockArr) {
            // attendance status for date
            $initialSql .= "MAX(CASE WHEN a.date = '$dateBlockArr[0]' AND a.camper_id = c.camper_id THEN a.present ELSE NULL END) AS \"$dateBlockArr[0]\",";
            // chug assignment for date
            $initialSql .= "COALESCE(MAX(CASE WHEN m.camper_id = c.camper_id AND ci.block_id = $dateBlockArr[1] THEN ch.name END),'No assignment') " .
                "AS \"$dateBlockArr[0]-chug\",";
        }
        // trim final comma - okay because already ensured there are 1+ dates
        $initialSql = rtrim($initialSql, ",") . " ";
        // add FROM/JOINS to SQL
        $initialSql .= "FROM campers c " .
            "JOIN block_instances bi ON c.session_id = bi.session_id " . 
            "JOIN matches m ON m.camper_id = c.camper_id " . 
            "JOIN chug_instances ci ON ci.chug_instance_id = m.chug_instance_id " . 
            "JOIN attendance a ON a.camper_id = c.camper_id AND a.chug_instance_id = ci.chug_instance_id " . 
            "JOIN chugim ch ON ch.chug_id = ci.chug_id " . 
            "JOIN bunks b on b.bunk_id = c.bunk_id ";
        // only this edah and group_id
        $initialSql .= "WHERE ch.group_id = $groupId AND c.edah_id = $edahId ";
        // grouping, ordering
        $initialSql .= "GROUP BY c.camper_id ORDER BY bunk, name";

        // determine if entire column is null
        $secondSql = "SELECT initial_result.`camper_id`, initial_result.`bunk`, initial_result.`name`,";
        // previous 2 columns + null check, by date
        foreach($date2BlockId as $dateBlockArr) {
            // 2 columns from initial result
            $secondSql .= " initial_result.`$dateBlockArr[0]`, initial_result.`$dateBlockArr[0]-chug`, ";
            // null check based on those columns
            $secondSql .= "CASE WHEN COUNT(`$dateBlockArr[0]`) OVER () = 0 THEN 1 ELSE 0 END AS `$dateBlockArr[0]_all_null`,";
        }
        // trim final comma - okay because already ensured there are 1+ dates
        $secondSql = rtrim($secondSql, ",") . " ";
        // conclude second part
        $secondSql .= "FROM initial_result";

        // build SQL from pieces
        $sql = "WITH initial_result AS ($initialSql) $secondSql";


        // run SQL
        $result = $db->runQueryDirectly($sql, $localErr);
        if ($result == false) {
            echo dbErrorString($sql, $localErr);
            exit();
        }

        // step 3: build table
        $attTable = "";
        $bunkText = "";
        $multipleBunks = False;
        $dateColHeadings = "";
        $rowsMade = 0;
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            // breakdown of $row:
                // [0] - camper id                  [1] - bunk 
                // [2] - name                       [3, 6, 9, ...] - attendance status
                // [4, 7, 10, ...] - chug assigned corresponding to previous status
                // [5, 8, 11, ...] - 1 if no attendance was taken this day; 0 otherwise

            // each time through the loop, we will create an html table row to include in the larger table
            // it will signal if campers are present/absent by cell color
            $tr = "<tr>";

            // bunk
            $tr .= "<td>$row[1]</td>";
            // name
            $tr .= "<td>$row[2]</td>";

            // rest of row will be composed of status icons corresponding to present/absent/attendance not taken, and hovering
            // on that icon will say status and chug assignment (if exists)
            for($i = 3; $i < count($row); $i += 3) {
                if($row[$i+2] != 1) {
                    // determine unique data for this camper and day
                    $tooltipText = $icon = $class = "";
                    if(is_null($row[$i])) { // attendance not yet taken
                        $tooltipText = "<b>Attendance Not Taken</b><br><b>" . ucfirst(chug_term_singular) . ":</b>${row[$i+1]}";
                        $icon = "<i class=\"bi bi-exclamation-triangle\"></i>";
                        $class = "table-warning";
                    }
                    else if($row[$i] == 0) { // absent
                        $tooltipText = "<b>Absent</b><br><b>" . ucfirst(chug_term_singular) . ":</b>${row[$i+1]}";
                        $icon = "<i class=\"bi bi-x-circle-fill\"></i>";
                        $class = "table-danger";
                    }
                    else if ($row[$i] == 1) { // present
                        $tooltipText = "<b>Present</b><br><b>" . ucfirst(chug_term_singular) . ":</b>${row[$i+1]}";
                        $icon = "<i class=\"bi bi-check-circle\"></i>";
                    }

                    // assemble cell
                    $tr .= "<td class=\"text-center align-middle $class\"><span data-bs-toggle=\"tooltip\" data-bs-html=\"true\" title=\"$tooltipText\" " . 
                        "style=\"border-bottom: 1px dashed #999;text-decoration: none;\">$icon</span></td>";
                }
            }

            $tr .= "</tr>";

            $attTable .= $tr;

            // test to see if there are multiple bunks or just one
            if($bunkText == "") {
                $bunkText = $row[1];
            }
            if($row[1] != $bunkText) {
                $multipleBunks = True;
            } 

            // if this is the first iteration, make correct column headers
            if ($rowsMade == 0) {
                $fieldInfo = $result->fetch_fields();
                for($i = 3; $i < count($row); $i += 3) {
                    if($row[$i+2] != 1) {
                        $fieldinfo = mysqli_fetch_field_direct($result, $i);
                        $dateColHeadings .= "<th scope=\"col\">" . date("D F j, Y", strtotime($fieldInfo[$i]->name)) . "</th>";
                    }
                }
            }
            $rowsMade++;
        }

        // if there is only one bunk for the edah, no need to include bunk info in table
        if(!$multipleBunks) {
            $attTable = str_replace("><td>$bunkText</td>", ">", $attTable);
        }

        // final section which will be returned showing the attendance matrix for the edah
        if($dateColHeadings != "" && $rowsMade > 0) {
            $attendanceMatrix = "<div class=\"card card-body bg-light mb-3 mt-3 attendance-matrix\">";
            $attendanceMatrix .= "<h5 class=\"text-center\" id=\"group$groupId\">" . $edahId2Name[$edahId] . ": <b>" . $groupId2Name[$groupId] . "</b></h5>";
            $attendanceMatrix .= "<table class=\"table table-hover\" id=\"attendanceMatrix$groupId\"><thead class=\"table-dark\"><tr>";
            if($multipleBunks) {
                $attendanceMatrix .= "<th scope=\"col\">Bunk</th>";
            }
            $attendanceMatrix .= "<th scope=\"col\">Name</th>$dateColHeadings</tr></thead>";
            $attendanceMatrix .= "<tbody>" . $attTable . "</tbody>";
            $attendanceMatrix .= "</table>";
            $attendanceMatrix .= "</div>";
            if($attTable != "") {
                echo $attendanceMatrix;
            }
            $tableCount++;
        }
    }

    // if no tables were made (all null, no attendance taken, bad dates, etc) display message indicating that
    if($tableCount == 0) {
        echo "<div class=\"alert alert-info mt-4 col-lg-8 offset-sm-2\" role=\"alert\"><h5>No Attendance Taken for Selected Dates</h5>" . 
        "No attendance records were found for $edahId2Name[$edahId] for the selected dates, so no summary matrix can be shown.</div>";
    }
    ?>


</div>
</body>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>


<?php 
function validate_form_inputs()
{
    $fullErrorMsg = "<div class=\"col-md-6 offset-md-3\"><div class=\"alert alert-danger alert-dismissible fade show m-2\" role=\"alert\">" . 
        "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button><h5><strong>Error:</strong> " . 
        "Invalid Selections</h5>Return to <a href=\"roshHome.php\" class=\"alert-link\">previous page</a> to fix following issue(s):<ul class=\"mb-0\">";
    $errors = "";

    // declare scope of variables
    global $edahId, $startDate, $endDate;

    // Step 1: edah id
    // get all edah ids, and then ensure there is a match between passed one and list
    $valid = false;
    $localErr = "";
    $dbc = new DbConn();
    $sql = "SELECT edah_id FROM edot";
    $result = $dbc->doQuery($sql, $localErr);
    if ($result == false) {
        echo dbErrorString($sql, $localErr);
        exit();
    }
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        if($edahId == $row[0]) {
            $valid = true;
            break;
        }
    }
    if(!$valid) {
        $errors .= "<li>Improper " . edah_term_singular . " id</li>";
    }

    // Step 2: start, end date format
    $fullDate = strtotime($startDate);
    $startDate = date('Y-m-d', $fullDate);
    if($fullDate == "") {
        $errors .= "<li>Invalid start date</li>";
    }

    // Step 3: start, end date format
    $fullDate = strtotime($endDate);
    $endDate = date('Y-m-d', $fullDate);
    if($fullDate == "") {
        $errors .= "<li>Invalid end date</li>";
    }

    // Step 4: verify start date is before end date
    if(strtotime($endDate) < strtotime($startDate)) {
        $errors .= "<li>End date is before start date -- order must be reversed</li>";
    }
    

    // build/return final error message if necessary
    if($errors != "") {
        echo $fullErrorMsg . $errors . "</ul></div></div>";
        exit();
    }
}

?>