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

    echo headerText("View Attendance");

    // Get info from the form
    $date = test_get_input('date');
    $groupId = test_get_input('group');
    $rawEdahIds = [];
    if(test_get_input('edah') != "") {
        $rawEdahIds = test_input($_GET['edah']);
    }
    $edahIds = [];

    // additional function (at bottom of code) to ensure everything was properly formatted and valid values
    // were passed in
    validate_form_inputs();

    // get active block id for particular date/chug group
    $localErr = "";
    $dbc = new DbConn();
    $sql = "SELECT block_id FROM attendance_block_by_date WHERE group_id = $groupId and date = '$date'";
    $result = $dbc->doQuery($sql, $localErr);
    if ($result == false) {
        echo dbErrorString($sql, $localErr);
        exit();
    }
    $activeBlockId = NULL;
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $activeBlockId = $row[0]; // only thing being returned
    }
    if(is_null($activeBlockId)) {
        echo "<div class=\"col-md-6 offset-md-3\"><div class=\"alert alert-danger alert-dismissible fade show m-2\" role=\"alert\">" . 
        "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button><h5><strong>Error:</strong> " . 
        "No Attendance Records Exist</h5>No attendance has been taken yet for the provided day. Try again once someone has taken attendance.</div></div>";
        exit();
    }

?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="card card-body mt-2 p-3 mb-3 container">
    <h1>View Attendance</h1>
    <div class="page-header"><h2><?php echo $groupId2Name[$groupId]; ?> Attendance</h2>
    <h4> Date: <?php echo $date; ?> </h4>
    </div>

    <p>By default, all campers are shown. Campers marked as absent are highlighted in <span style="background:#f8d7da;">red</span>. Campers for whom attendance has not been taken are highlighted in <span style="background:#fff3cd;">yellow</span>.
    If a <?php echo chug_term_singular; ?> name is underlined, hover over it (or click if on a mobile device) to see more information.
    Beneath the report for an edah is a button which copies a plaintext report of missing campers to your clipboard (which can then be easily sent to madrichim).</p>
    
    Toggle the switch below to adjust if all campers are shown or only missing ones:

    <div class="form-check form-switch ps-0 fw-bold text-center">
        <span class="me-1">All Campers</span>
        <input class="form-check-input ms-0 float-none me-1" type="checkbox" onChange="togglePresent()" id="all_absent_switch">
        <span class="">Only Absent Campers</span>
    </div>

    <?php 
    foreach($edahIds as $edahId) {
        // Step 1: SQL for all campers, chug assignments, and if they are present or not
        $localErr = "";
        $dbc = new DbConn();
        // this sql statement gets all campers in the edah with their chug assignments
        $sql = "SELECT c.camper_id, CONCAT(c.last, ', ', c.first) AS name, b.name AS bunk, ch.name AS chug, ch.department_name, ch.rosh_name, a.present is_present " .
            "FROM campers c JOIN matches m ON c.camper_id = m.camper_id " .
            "JOIN bunks b ON c.bunk_id = b.bunk_id " . 
            "JOIN chug_instances i ON m.chug_instance_id = i.chug_instance_id " . 
            "JOIN chugim ch on ch.chug_id = i.chug_id " . 
            "JOIN chug_groups g on ch.group_id = g.group_id ";
        // include present/absent
        $sql .= "LEFT OUTER JOIN (SELECT * FROM attendance WHERE date = \"$date\") a ON c.camper_id = a.camper_id AND i.chug_instance_id = a.chug_instance_id ";
        // filter by block, edah, group, and only show "active" campers
        $sql .= "WHERE i.block_id = $activeBlockId AND c.inactive = 0 AND c.edah_id = $edahId and ch.group_id = $groupId ";
        // order result by bunk and then last name
        $sql .= "ORDER BY bunk, name";
        
        // run SQL
        $result = $dbc->doQuery($sql, $localErr);
        if ($result == false) {
            echo dbErrorString($sql, $localErr);
            exit();
        }

        
        $attTable = "";
        $bunkText = "";
        $multipleBunks = False;
        $campersMissingAttendance = 0;
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            // breakdown of $row:
                // [0] - camper id          [1] - name 
                // [2] - bunk               [3] - chug assignment
                // [4] - department         [5] - chug rosh/leader
                // [6] - present (1/0)      [7] - attendance taken for perek (1/0)

            // each time through the loop, we will create an html table row to include in the larger table
            // it will signal if campers are present/absent by row color
            $tr = "<tr class=\"";

            if(is_null($row[6])) { // missing, but attendance not taken
                $tr .= "table-warning absent";
                $icon = "<i class=\"bi bi-exclamation-triangle\"></i>";
                $status = "Attendance not yet taken";
                $campersMissingAttendance++;
            }
            else if($row[6] == 0){//// missing, attendance taken
                $tr .= "table-danger absent";
                $icon = "<i class=\"bi bi-x-circle-fill\"></i>";
                $status = "Absent";
            }
            else if ($row[6] == 1) { // present, attendance taken
                $tr .= "present";
                $icon = "<i class=\"bi bi-check-circle\"></i>";
                $status = "Present";
            }
            $tr .= "\">";

            // status icon
            $tr .= "<td class=\"text-center align-middle\"><span data-bs-toggle=\"tooltip\" data-bs-html=\"true\" title=\"$status\" " . 
                    "style=\"border-bottom: 1px dashed #999;text-decoration: none; \">$icon</td>";

            // bunk
            $tr .= "<td>$row[2]</td>";
            // name
            $tr .= "<td>$row[1]</td>";

            // chug assignment -- if department, leader are not null, include a tooltip indicating that info
            $tooltipText = "";
            if($row[4] != NULL && $row[5] != NULL) {
                $tooltipText = "<b>Department:</b> $row[4]<br><b>Chug Leader:</b> $row[5]";
            }
            else if($row[4] != NULL) {
                $tooltipText = "<b>Department:</b> $row[4]";
            }
            else if($row[5] != NULL) {
                $tooltipText = "Chug Leader:</b> $row[5]";
            }
            if($tooltipText != "") {
                $tr .= "<td><span data-bs-toggle=\"tooltip\" data-bs-html=\"true\" title=\"$tooltipText\" " . 
                    "style=\"border-bottom: 1px dashed #999;text-decoration: none; \">$row[3]</span></td>";
            }
            else {
                $tr .= "<td>$row[3]</td>";
            }

            $tr .= "</tr>";

            $attTable .= $tr;

            // test to see if there are multiple bunks or just one
            if($bunkText == "") {
                $bunkText = $row[2];
            }
            if($row[2] != $bunkText) {
                $multipleBunks = True;
            } 
        }

        // if no campers had their attendance taken, show message saying that instead
        if($campersMissingAttendance == mysqli_num_rows($result)) {
            echo "<div class=\"card card-body bg-light mb-3 edah-attendance\"><h5 class=\"text-center\" id=\"edah$edahId\">Edah: " . $edahId2Name[$edahId] . "</h5>" . 
                "<h6>No $groupId2Name[$groupId] attendance has been taken yet for $edahId2Name[$edahId] on " . date("D F j, Y", strtotime($date)) . "; ask an administrator if you believe you are seeing this message in error.</h6>";
            break;
        }

        // if there is only one bunk for the edah, no need to include bunk info in table
        if(!$multipleBunks) {
            $attTable = str_replace("><td>$bunkText</td>", ">", $attTable);
        }

        // final section which will be returned showing the attendance for the edah
        $attendanceSection = "<div class=\"card card-body bg-light mb-3 edah-attendance\">";
        $attendanceSection .= "<h5 class=\"text-center\" id=\"edah$edahId\">Edah: " . $edahId2Name[$edahId] . "</h5>";
        $attendanceSection .= "<table class=\"table table-hover\" id=\"attendance$edahId\"><thead class=\"table-dark\"><tr>";
        $attendanceSection .= "<th scope=\"col\"></th>";
        if($multipleBunks) {
            $attendanceSection .= "<th scope=\"col\">Bunk</th>";
        }
        $attendanceSection .= "<th scope=\"col\">Name</th><th scope=\"col\">Chug</th></tr></thead>";
        $attendanceSection .= "<tbody>" . $attTable . "</tbody>";
        $attendanceSection .= "</table>";
        $attendanceSection .= "<button type=\"button\" class=\"btn btn-info mx-auto\" onclick=\"copyReport($edahId)\">Copy Missing Camper Report</button>";
        $attendanceSection .= "</div>";
        if($attTable != "") {
            echo $attendanceSection;
        }
    }

    ?>



</div>

<div class="toast-container position-fixed bottom-0 end-0">
    <div class="toast m-3 align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            Success! Missing camper report copied to clipboard
        </div>
    </div>
</div>

</body>

<?php 
function validate_form_inputs()
{
    $fullErrorMsg = "<div class=\"col-md-6 offset-md-3\"><div class=\"alert alert-danger alert-dismissible fade show m-2\" role=\"alert\">" . 
        "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button><h5><strong>Error:</strong> " . 
        "Invalid Selections</h5>Return to <a href=\"roshHome.php\" class=\"alert-link\">previous page</a> to fix following issue(s):<ul class=\"mb-0\">";
    $errors = "";

    // declare scope of variables
    global $edahIds, $rawEdahIds, $date, $groupId, $chugId;

    // Step 1: edah ids
    foreach($rawEdahIds as $i => $id) {
        $id = trim($id);
        $id = stripslashes($id);
        $id = htmlspecialchars($id);
        $rawEdahIds[$i] = $id;
    }

    // sort edahIds by proper sort_order
    $localErr = "";
    $dbc = new DbConn();
    $sql = "SELECT edah_id FROM edot ORDER BY sort_order";
    $result = $dbc->doQuery($sql, $localErr);
    if ($result == false) {
        echo dbErrorString($sql, $localErr);
        exit();
    }
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        if(in_array($row[0], $rawEdahIds)) {
            array_push($edahIds, $row[0]);
        }
    }
    if(count($edahIds) == 0) {
        $errors .= "<li>Improper edah id(s)</li>";
    }


    // Step 2: date format
    $fullDate = strtotime($date);
    $date = date('Y-m-d', $fullDate);
    if($fullDate == "") {
        $errors .= "<li>Invalid date</li>";
    }
    
    // Step 3: group id valid
    $localErr = "";
    $dbc = new DbConn();
    $sql = "SELECT g.group_id FROM chug_groups g WHERE g.group_id = " . intval($groupId) . " AND g.active_block_id IS NOT NULL";
    $result = $dbc->doQuery($sql, $localErr);
    if ($result == false) {
        echo dbErrorString($sql, $localErr);
        exit();
    }
    if($result->num_rows != 1) {
        $errors .= "<li>Perek not properly selected</li>";
    }

    // build/return final error message if necessary
    if($errors != "") {
        echo $fullErrorMsg . $errors . "</ul></div></div>";
        exit();
    }
}

?>

<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })


    function togglePresent() {
        var toggle = document.getElementById("all_absent_switch");
        var divsToHide = document.getElementsByClassName("present"); //divsToHide is an array
        var visibility = ""; // default
        if(toggle.checked) {
            // only show absent campers, so hide present ones
            visibility = "none";
        }
        for(var i = 0; i < divsToHide.length; i++){
            divsToHide[i].style.display = visibility;
        }
    }

    function copyReport(edahId) {
        var output = "";
        var table = document.getElementById("attendance"+edahId);
        var absent = table.querySelectorAll(".absent");
        output += document.getElementById("edah"+edahId).innerText.substr(5) + " Missing Campers\n";
        output += "<?php echo $groupId2Name[$groupId]; ?> -- <?php echo $date; ?>";

        // bunk not included
        if (table.rows[0].cells.length === 3) {
            absent.forEach((row) => {
                var camperName = row.cells[1].innerText;
                var camperChug = row.cells[2].innerText;
                if(row.cells[2].hasChildNodes()) {
                    // handle case where there is a tooltip -- unneeded info
                    camperChug = row.cells[2].firstChild.innerText;
                }
                // add camper info
                output += "\n * " + camperName + " (" + camperChug + ")";
            });
        }
        // bunk included
        else {
            var bunk = "";
            absent.forEach((row) => {
                var camperBunk = row.cells[1].innerText;
                var camperName = row.cells[2].innerText;
                var camperChug = row.cells[3].innerText;
                if(row.cells[3].firstChild.hasChildNodes()) {
                    // handle case where there is a tooltip -- unneeded info
                    camperChug = row.cells[3].firstChild.innerText;
                }
                // check if this camper's bunk is same as previous; if not, start new section in report
                if(bunk != camperBunk) {
                    output += "\nBunk: " + camperBunk;
                    bunk = camperBunk;
                }
                // add camper info
                output += "\n * " + camperName + " (" + camperChug + ")";
            });
        }
        setClipboard(output.trim());

        $('.toast').toast({
                    animation: false,
                    delay: 3000
                });
                $('.toast').toast('show');
    }

    async function setClipboard(text) {
        await navigator.clipboard.writeText(text);
    }

</script>