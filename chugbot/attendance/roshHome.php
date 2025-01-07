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

    echo headerText("Rosh Home");

    if(!attendanceActive()) {
        $fullErrorMsg = "<div class=\"col-md-6 offset-md-3\"><div class=\"alert alert-danger alert-dismissible fade show m-2\" role=\"alert\">" . 
        "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button><h5><strong>Error:</strong> " . 
        "Attendance Disabled</h5>Nothing is available to take attendance for; attendance has been disabled. " .
        "Contact an administrator if you believe to be receiving this message in error.</div></div>";
        echo $fullErrorMsg;
        exit();
    }
?>
    <!-- Uses choices-js, more info at https://github.com/Choices-js/Choices -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {

            const elements = document.querySelectorAll('.choices-js');
            elements.forEach((element) =>  {
                const choices = new Choices(element, {
                    shouldSort: false,
                    allowHTML: true,
                    searchChoices: false,
                    removeItemButton: true
                });
            });
        });
    </script>

<div class="container row justify-content-center" style="margin:auto;"><div id="errors" class="mt-2"></div></div>

<div class="card card-body mt-2 p-3 mb-3 container">
    <h1>Rosh <?php echo ucfirst(edah_term_singular)?>/Yoetzet Home</h1>
    <div class="page-header mb-3"><h2>Rosh Edah/Yoetzet Home</h2>
        Below, choose to review detailed attendance records for one particular perek on a given day, or view the attendance matrix over a specified time frame.
        <ul>
            <li>If quickly checking attendance for your <?php echo edah_term_singular?>, utilize <strong>Attendance by Date</strong></li>
            <li>If exploring attendance trends over time, look at <strong>Attendance Matrix</strong></li>
        </ul>
    </div>


<?php

// body of date section of accordion
$dateBody = "<p>In the form below, select the date, " . edah_term_singular . ", and perek you wish to review attendance records for.";
$dateBody .= <<<EOM
    Just click "View Attendance" below the form to see the latest record.</p>
    <form id="attendance_chug_select_form" class="justify-content-center" method="GET" action="viewAttendance.php" onsubmit="return validateAttendanceForm()"><ul>
        <li style="margin:auto;" class="ps-0">
        <label class="description" for="date"><span style="color:red;">*</span>Date</label>
            <div id="date_pick" class="pb-2">
                <input type="date" id="date" name="date" class="form-control medium" required>
            </div>
        </li>
        <li style="margin:auto;" class="ps-0">
EOM;
//$dateBody .= $eom1;
$dateBody .= "<label class=\"description\" for=\"edah\"><span style=\"color:red;\">*</span>" . ucfirst(edah_term_singular) . "/" . ucfirst(edah_term_plural) . "</label>" .
    "<div id=\"edah_select\" class=\"pb-2\">" . 
    "<select class=\"form-select bg-info choices-js\" id=\"edah_list\" name=\"edah[]\" onchange=\"fillConstraintsPickList();\" multiple>" . 
        genPickList($edahId2Name, array(), "edah") . 
    "</select></div>" . 
    "</li><li style=\"margin:auto;\" class=\"ps-0\">" . 
    "<label class=\"description\" for=\"group\" id=\"group_desc\"><span style=\"color:red;\">*</span>Perek</label>" . 
    "<div id=\"group_select\" class=\"pb-2\">" . 
        genConstrainedPickListScript("group_select", "edah", "group_desc", "group", true);
$dateBody .= <<<EOM
</div></li>
    <li style="margin:auto;">
        <div class="row justify-content-center">
            <div class="col-6" style="text-align:center;"><button class="btn btn-primary" type="submit" id="submit_btn">View Attendance</button></div>
        </div>
    </li>
</ul></form>
EOM;

// body of matrix section of accordion
$matrixBody = "Select the beginning and ending dates (inclusive) you wish to review the attendance records for, and select the " . edah_term_singular . " you are checking. Then, press \"View Attendance Matrix\" below the form to see the attendance trends.<br>";
$matrixBody .= <<<EOM
<strong>Note:</strong> it is NOT recommended to view this page on a mobile device - computers or tablets are highly recommended for viewing the attendance matrix
    <form id="attendance_matrix_form" class="justify-content-center mt-4" method="GET" action="viewAttendanceMatrix.php" onsubmit="return validateMatrixForm()"><ul>
        <li style="margin:auto;" class="ps-0">
            <div class="row">
                <div class="col">
                    <label class="description" for="start-date"><span style="color:red;">*</span>Start Date (inclusive)</label>
                    <div id="date_pick" class="pb-2 ps-2">
                        <input type="date" id="start-date" name="start-date" class="form-control">
                    </div>
                </div>
                <div class="col">
                    <label class="description" for="end-date"><span style="color:red;">*</span>End Date (inclusive)</label>
                    <div id="date_pick" class="pb-2">
                        <input type="date" id="end-date" name="end-date" class="form-control">
                    </div>
                </div>
            </div>
        </li>
        <li style="margin:auto;" class="ps-0">
EOM;
$matrixBody .= "<label class=\"description\" for=\"edah\"><span style=\"color:red;\">*</span>" . ucfirst(edah_term_singular) . "/" . ucfirst(edah_term_plural) . "</label>" .
    "<div id=\"edah_select\" class=\"pb-2\">" . 
    "<select class=\"form-select bg-info choices-js\" id=\"edah_list\" name=\"edah\">" . 
        genPickList($edahId2Name, array(), "edah");
$matrixBody .= <<<EOM
            </select>
        </div>
    </li>
    <li style="margin:auto;">
        <div class="row justify-content-center mt-2">
            <div class="col-6" style="text-align:center;"><button class="btn btn-primary" type="submit" id="submit_btn">View Attendance Matrix</button></div>
        </div>
    </li>
</ul></form>
EOM;

$attendanceAccordion = new bootstrapAccordion($name="detail", $flush=false, $alwaysOpen=false);
$attendanceAccordion->addAccordionElement($id="Date", $title="Attendance by Date", $body=$dateBody, $open=true);
$attendanceAccordion->addAccordionElement($id="Matrix", $title="Attendance Matrix", $body=$matrixBody, $open=false);
echo $attendanceAccordion->renderHtml();

?>

</div>

<script>
// automatically set date to "today"
const today = new Date();
const yyyy = today.getFullYear();
const mm = String(today.getMonth() + 1).padStart(2, '0'); // Months start at 0!
const dd = String(today.getDate()).padStart(2, '0');

const formattedToday = `${yyyy}-${mm}-${dd}`;
document.getElementById('date').value = formattedToday;


function validateAttendanceForm() {
    var error = "<div class=\"alert alert-danger alert-dismissible fade show mb-0 ms-2 me-2\" role=\"alert\">";
    error += "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
    error += "<h5><strong>Error:</strong> Not all required fields are complete</h5><ul class=\"mb-0\">";

    let valid = true;

    // ensure each required field is filled out - first check field exists, then that it has a value
    fields = ["date", "edah[]", "group"];
    fields.forEach((field) => {
        let x = document.forms["attendance_chug_select_form"][field];
        if(field === "group") { field = "perek"; } // small override to keep backend and UI consistent with each other
        if(field === "edah[]") { field = "<?php echo edah_term_singular ?>"; } // add'l change so user just sees "Edah," not "Edah[]"
        // check field exists
        if(x === undefined) {
            error += "<li><strong>"+field[0].toUpperCase() + field.slice(1)+"</strong> missing</li>";
            valid = false;
        }
        else {
            // check field has value
            x = x.value;
            if (x == "") {
                error += "<li><strong>"+field[0].toUpperCase() + field.slice(1)+"</strong> missing</li>";
                valid = false;
            }
        }
    });

    error += "</ul></div>";    

    if(!valid) {
        // if form is not ready to submit, show errors and cancel submission
        var errorDiv = document.getElementById("errors");
        errorDiv.innerHTML = error;
        return false;
    }
    else {
        // an extra field (a "search term") is being submitted because of the dropdown; this disables that
        const cloned = document.getElementsByClassName("choices__input--cloned");
        cloned[0].setAttribute('disabled', 'true');
        cloned[1].setAttribute('disabled', 'true');
        // then just automatically submits!
    }
}

function validateMatrixForm() {
    var error = "<div class=\"alert alert-danger alert-dismissible fade show mb-0 ms-2 me-2\" role=\"alert\">";
    error += "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
    error += "<h5><strong>Error:</strong> Not all required fields are complete</h5><ul class=\"mb-0\">";

    let valid = true;

    // ensure each required field is filled out - first check field exists, then that it has a value
    fields = ["start-date", "end-date", "edah"];
    fields.forEach((field) => {
        console.log(field);
        let x = document.forms["attendance_matrix_form"][field];
        field = field.replace("-", " "); // small change to remove hyphen from field name for UI
        if(field === "edah") { field = "<?php echo edah_term_singular ?>"; } // add'l change so user just sees "Edah," not "Edah[]"
        // check field exists
        if(x === undefined) {
            error += "<li><strong>"+field[0].toUpperCase() + field.slice(1)+"</strong> missing</li>";
            valid = false;
        }
        else {
            // check field has value
            x = x.value;
            if (x == "") {
                error += "<li><strong>"+field[0].toUpperCase() + field.slice(1)+"</strong> missing</li>";
                valid = false;
            }
        }
    });

    // ensure end date is after start date
    if (new Date(document.forms["attendance_matrix_form"]['start-date'].value) > new Date(document.forms["attendance_matrix_form"]['end-date'].value)) {
        error += "<li><strong>Start Date</strong> is after <strong>End Date</strong> -- start date must be earlier</li>";
        valid = false;
    }

    error += "</ul></div>";    

    if(!valid) {
        // if form is not ready to submit, show errors and cancel submission
        var errorDiv = document.getElementById("errors");
        errorDiv.innerHTML = error;
        return false;
    }
    else {
        // an extra field (a "search term") is being submitted because of the dropdown; this disables that
        const cloned = document.getElementsByClassName("choices__input--cloned");
        cloned[0].setAttribute('disabled', 'true');
        cloned[1].setAttribute('disabled', 'true');
        // then just automatically submits!
    }
}
</script>

</body>

<?php
function attendanceActive() {
    $db = new dbConn();
    $dbErr = "";
    $db->addSelectColumn("count(active_block_id)");
    $result = $db->simpleSelectFromTable("chug_groups", $dbErr);
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        // just gets the count of active blocks
        return $row[0] > 0;
    }
    return false;
}

?>