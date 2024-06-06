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
    <h1>Rosh Edah/Yoetzet Home</h1>
    <div class="page-header"><h2>Rosh Edah/Yoetzet Home</h2>
    <p>In the form below, select the date, edah, and perek you wish to review attendance records for.
    Just click "View Attendance" below the form to see the latest record.</p>
    
    <form id="attendance_chug_select_form" class="justify-content-center" method="GET" action="viewAttendance.php" onsubmit="return validateForm()"><ul>
        <li style="margin:auto;" class="ps-0">
            <label class="description" for="date"><span style="color:red;">*</span>Date</label>
            <div id="date_pick" class="pb-2">
                <input type="date" id="date" name="date" class="form-control medium">
            </div>
        </li>
        <li style="margin:auto;" class="ps-0">
            <label class="description" for="edah"><span style="color:red;">*</span>Edah/Edot</label>
            <div id="edah_select" class="pb-2">
                <select class="form-select bg-info choices-js" id="edah_list" name="edah[]" onchange="fillConstraintsPickList();" multiple>
                    <?php echo genPickList($edahId2Name, array(), "edah"); ?>
                </select>
            </div>
        </li>
        <li style="margin:auto;" class="ps-0"> 
            <label class="description" for="group" id="group_desc"><span style="color:red;">*</span>Perek</label>
            <div id="group_select" class="pb-2">
                <?php echo genConstrainedPickListScript("group_select", "edah", "group_desc", "group", true); ?>
            </div>
        </li>
        <li style="margin:auto;">
            <div class="row justify-content-center">
                <div class="col-6" style="text-align:center;"><button class="btn btn-primary" type="submit" id="submit_btn">View Attendance</button></div>
            </div>
        </li>
    </ul></form>

</div>

<script>
// automatically set date to "today"
document.getElementById('date').valueAsDate = new Date();


function validateForm() {
    var error = "<div class=\"alert alert-danger alert-dismissible fade show mb-0 ms-2 me-2\" role=\"alert\">";
    error += "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
    error += "<h5><strong>Error:</strong> Not all required fields are complete</h5><ul class=\"mb-0\">";

    let valid = true;

    // ensure each required field is filled out - first check field exists, then that it has a value
    fields = ["date", "edah[]", "group"];
    fields.forEach((field) => {
        let x = document.forms["attendance_chug_select_form"][field];
        if(field === "group") { field = "perek"; } // small override to keep backend and UI consistent with each other
        if(field === "edah[]") { field = "edah"; } // add'l change so user just sees "Edah," not "Edah[]"
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
</script>

</body>