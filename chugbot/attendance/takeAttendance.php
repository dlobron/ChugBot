<?php
    session_start();
    include_once '../dbConn.php';
    include_once '../functions.php';
    bounceToLogin("chugLeader");
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

    echo headerText("Take Attendance");

    // Get info from the form
    $date = test_get_input('date');
    $groupId = test_get_input('group');
    $chugId = test_get_input('chug');
    $rawEdahIds = [];
    if(test_get_input('edah') != "") {
        $rawEdahIds = test_input($_GET['edah']);
    }
    $edahIds = [];

    // additional function (at bottom of code) to ensure everything was properly formatted and valid values
    // were passed in
    validate_form_inputs();

    // update saved attendance
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // 1: get chug_instance_id (block is known (based on active_block_id, using group_id), chug_id is know)
        $localErr = "";
        $dbc = new DbConn();
        $sql = "SELECT chug_instance_id FROM chug_instances i JOIN chug_groups g ON i.block_id = g.active_block_id WHERE i.chug_id = $chugId and g.group_id = $groupId";
        $result = $dbc->doQuery($sql, $localErr);
        if ($result == false) {
            echo dbErrorString($sql, $localErr);
            exit();
        }
        $chugInstanceId;
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $chugInstanceId = $row[0]; // the only thing being returned by this statement is the chug instance id
        }

        $presentCamperCount = $absentCamperCount = 0;
        if(test_post_input('absent') != "") {
            $absentCamperCount = count($_POST['absent']);
        }
        if(test_post_input('present') != "") {
            $presentCamperCount = count($_POST['present']);
        }

        // 2: build sql statement updating attendance for each camper
        if($presentCamperCount + $absentCamperCount > 0) {
            $localErr = "";
            $dbc = new DbConn();
            $sql = "REPLACE INTO attendance (camper_id, date, chug_instance_id, present) VALUES ";
            $ct = 1;
            // present campers
            if($presentCamperCount > 0) {
                foreach($_POST['present'] as $camper) {
                    $sql .= "($camper, '$date', $chugInstanceId, 1)";
                    // add a comma between every value
                    if($ct++ < $presentCamperCount + $absentCamperCount) {
                        $sql .= ",";
                    }
                }
            }
            // absent campers
            if($absentCamperCount > 0) {
                foreach($_POST['absent'] as $camper) {
                    $sql .= "($camper, '$date', $chugInstanceId, 0)";
                    // add a comma between every value
                    if($ct++ < $presentCamperCount + $absentCamperCount) {
                        $sql .= ",";
                    }
                }
            }   
            $result = $dbc->doQuery($sql, $localErr);
            if ($result == false) {
                echo dbErrorString($sql, $localErr);
                exit();
            }
        }

        // 3: update date <-> group <-> block relationship in database
        if($presentCamperCount + $absentCamperCount > 0) {
            // get active block id for current group
            $localErr = "";
            $dbc = new DbConn();
            $sql = "SELECT active_block_id FROM chug_groups WHERE group_id = $groupId";
            $result = $dbc->doQuery($sql, $localErr);
            if ($result == false) {
                echo dbErrorString($sql, $localErr);
                exit();
            }
            $activeBlockId = NULL;
            while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                $activeBlockId = $row[0]; // only thing being returned
            }
            if($activeBlockId != NULL) {
                $localErr = "";
                $dbc = new DbConn();
                $sql = "REPLACE INTO attendance_block_by_date (date, group_id, block_id) VALUES ('$date', $groupId, $activeBlockId)";
                $result = $dbc->doQuery($sql, $localErr);
                if ($result == false) {
                    echo dbErrorString($sql, $localErr);
                    exit();
                }
            }
        }

        $successMessage = "<div class=\"col-md-6 offset-md-3\"><div class=\"alert alert-success alert-dismissible fade show m-2\" role=\"alert\">" . 
        "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button><h5><strong>Congrats!</strong> " . 
        "Attendance Successfully Submitted</h5>The attendance records have been saved. You can now safely close this page, or update the attendance below.</div></div>";
        echo $successMessage;
    }
?>

<div class="card card-body mt-2 p-3 mb-3 container">
    <h1>Take Attendance</h1>
    <div class="page-header"><h2>Attendance for <?php echo $chugId2Name[$chugId]; ?></h2>
    <h4> Date: <?php echo $date; ?> </h4>
    Select all campers who are <strong>present</strong>, and then press "Submit Attendance" at the bottom of the page to save.
    </div>

    <form id="attendance_chug_select_form" class="justify-content-center" method="POST" action="">

    <?php 
    foreach($edahIds as $edahId) {
        // Step 1: SQL to determine which campers (in specified edah) are in the chug
        $localErr = "";
        $dbc = new DbConn();
        // this sql statement gets all matches, camper names, ids, and bunks
        $sql = "SELECT c.camper_id, CONCAT(c.last, ', ', c.first) AS name, b.name AS bunk, a.present AS is_present FROM campers c " .
            "JOIN matches m ON c.camper_id = m.camper_id JOIN chug_instances i ON m.chug_instance_id = i.chug_instance_id " . 
            "JOIN chugim ch ON i.chug_id = ch.chug_id JOIN bunks b ON c.bunk_id = b.bunk_id " . 
            "JOIN chug_groups g on ch.group_id = g.group_id ";
        // include attendance record
        $sql .= "LEFT OUTER JOIN (SELECT * FROM attendance WHERE date = '" . $date . "') a ON c.camper_id = a.camper_id AND i.chug_instance_id = a.chug_instance_id ";
        // narrow it down by edah, chug, block, and only show "active" campers
        $sql .= "WHERE c.edah_id = " . $edahId . " AND c.inactive = 0 AND ch.chug_id = " . $chugId . " AND i.block_id = g.active_block_id ";
        // sort by bunk, then last name
        $sql .= " ORDER BY bunk, name ";
        $result = $dbc->doQuery($sql, $localErr);
        if ($result == false) {
            echo dbErrorString($sql, $localErr);
            exit();
        }
        
        $att = "";
        $bunk = "";
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            // breakdown of $row:
                // [0] - camper id
                // [1] - name 
                // [2] - bunk
                // [3] - is_present (already recorded as being present)
            if($row[2] != $bunk) {
                $att .= "<p class=\"mb-0\"><strong>Bunk:</strong> $row[2]</p>";
                $bunk = $row[2];
            }
            $selected = "";
            if($row[3]) { // if camper has already been marked as present, pre-check that box
                $selected = "checked=checked";
            }
            $att .= "<label class=\"form-check-label\"><input class=\"form-check-input ms-2 me-1 att-child\" type=\"checkbox\"" .  
                    "name=\"present[]\" value=\"${row[0]}\" id=\"\" $selected>$row[1]</label>";
        }

        $attendanceSection = "<div class=\"card card-body bg-light mt-3 mb-3 edah-attendance\">";
        $attendanceSection .= "<h5>" . ucfirst(edah_term_singular) . ": " . $edahId2Name[$edahId] . "</h5>";
        $attendanceSection .= "<label><input class=\"form-check-input me-1 mb-2\" type=\"checkbox\" id=\"toggle-all\" onclick=\"toggleCheckboxes(this)\">Toggle All</label>";
        $attendanceSection .= $att;
        $attendanceSection .= "</div>";
        if($att != "") {
            echo $attendanceSection;
        }
    }

    ?>
    
    <div class="row justify-content-center"><div class="col-6" style="text-align:center;">
        <button class="btn btn-success" type="submit" id="submit_btn">Submit Attendance</button>
    </div></div>

    </form>


    <script>
        function toggleCheckboxes(source) {
            const container = source.closest('.edah-attendance');
            const checkboxes = container.querySelectorAll('.att-child');
            for (let checkbox of checkboxes) {
                checkbox.checked = source.checked;
            }
        }

        // create additional form item for all campers who are NOT present
        document.getElementById('attendance_chug_select_form').addEventListener('submit', function(event) {
            // Get all checkboxes
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="present[]"]');
            // Create an array to store values of unchecked checkboxes
            let uncheckedValues = [];

            // Loop through each checkbox
            checkboxes.forEach(checkbox => {
                // If the checkbox is not checked, add its value to the array
                if (!checkbox.checked) {
                    uncheckedValues.push(checkbox.value);
                }
            });

            // Create hidden inputs for unchecked values
            uncheckedValues.forEach(value => {
                let hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'absent[]';
                hiddenInput.value = value;
                this.appendChild(hiddenInput);
            });
        });
    </script>
</div>
</body>

<?php 
function validate_form_inputs()
{
    $fullErrorMsg = "<div class=\"col-md-6 offset-md-3\"><div class=\"alert alert-danger alert-dismissible fade show m-2\" role=\"alert\">" . 
        "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button><h5><strong>Error:</strong> " . 
        "Invalid Selections</h5>Return to <a href=\"chugLeaderHome.php\" class=\"alert-link\">previous page</a> to fix following issue(s):<ul class=\"mb-0\">";
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
    
    // Step 3: group id, chug id valid
    $localErr = "";
    $dbc = new DbConn();
    $sql = "SELECT g.group_id, c.chug_id FROM chug_groups g, chugim c WHERE g.group_id = " . intval($groupId) . " AND c.chug_id = " . intval($chugId) . " AND g.active_block_id IS NOT NULL";
    $result = $dbc->doQuery($sql, $localErr);
    if ($result == false) {
        echo dbErrorString($sql, $localErr);
        exit();
    }
    if($result->num_rows != 1) {
        $errors .= "<li>Chug and/or perek not properly selected</li>";
    }

    // build/return final error message if necessary
    if($errors != "") {
        echo $fullErrorMsg . $errors . "</ul></div></div>";
        exit();
    }
}

?>