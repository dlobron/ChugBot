<?php
    session_start();
    include 'assignment.php';
    bounceToLogin();
    $err = $dbErr = "";
    
    // We assume we got here from a POST.  If not, go to the home page.
    if (! $_SERVER["REQUEST_METHOD"] == "POST") {
        $err = errorString("Unknown request method - please hit 'Back' and try again.");
    }
    $edah_id = intval(test_input($_POST["edah"]));
    $block_id = intval(test_input($_POST["block"]));
    $levelHomeUrl = urlIfy("levelHome.html");
    $levelHomeUrl .= "?edah=$edah_id&block=$block_id";
    
    // Check for an existing assignment set.
    $mysqli = connect_db();
    $sql = "SELECT * FROM ASSIGNMENTS WHERE edah_id = $edah_id AND block_id = $block_id";
    $result = $mysqli->query($sql);
    if ($result == FALSE) {
        $dbErr = dbErrorString($sql, $mysqli->error);
        echo genErrorPage($dbErr);
        exit;
    }
    if ($result->num_rows > 0) {
        // We have an existing assignment: redirect to the display/edit page.
        header("Location: $levelHomeUrl");
        exit;
    }
    
    // We're now ready to build our assignments.  We iterate over each activity
    // group, and make an assignment for each one.
    $sql = "SELECT group_id, name FROM groups";
    $result = $mysqli->query($sql);
    if ($result == FALSE) {
        $dbErr = dbErrorString($sql, $mysqli->error);
        echo genErrorPage($dbErr);
        exit;
    }
    // Do the actual assignments, recording results as we go.
    while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
        $group_id = intval($row[0]);
        $group_name = $row[1];
        $err = "";
        $ok = do_assignment($edah_id, $block_id, $group_id, $err);
        if (! $ok) {
            echo genErrorPage($err);
            exit;
        }
    }
    // Assignments done - redirect to the assignment page.
    error_log("Assigned edah $edah_id, block $block_id OK");
    header("Location: $levelHomeUrl");
    
    exit;
    
    ?>
    
