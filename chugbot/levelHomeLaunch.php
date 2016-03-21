<?php
    session_start();
    include_once 'assignment.php';
    include_once 'dbConn.php';
    bounceToLogin();
    $err = $dbErr = "";
    
    // We assume we got here from a POST.  If not, go to the home page.
    if (! $_SERVER["REQUEST_METHOD"] == "POST") {
        $err = errorString("Unknown request method.");
    }
    $edah_id = intval(test_input($_POST["edah"]));
    $block_id = intval(test_input($_POST["block"]));
    if ($edah_id == NULL || $block_id == NULL) {
        $err = errorString("Block and edah must be specified.");
    }
    if ($err) {
        echo genErrorPage($err);
        exit;
    }
    
    $levelHomeUrl = urlIfy("levelHome.html");
    $levelHomeUrl .= "?edah=$edah_id&block=$block_id";
    
    // Check for an existing assignment set.
    $db = new DbConn();
    $db->addSelectColumn("*");
    $db->addWhereColumn("edah_id", $edah_id, 'i');
    $db->addWhereColumn("block_id", $block_id, 'i');
    $db->isSelect = TRUE;
    $result = $db->simpleSelectFromTable("assignments", $dbErr);
    if ($result == FALSE) {
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
    $mysqli = connect_db();
    $sql = "SELECT group_id, name FROM groups";
    $result = $mysqli->query($sql);
    if ($result == FALSE) {
        $dbErr = dbErrorString($sql, $mysqli->error);
        echo genErrorPage($dbErr);
        exit;
    }
    // Do the actual assignments, recording results as we go.
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $group_id = intval($row[0]);
        $group_name = $row[1];
        $err = "";
        $ok = do_assignment($edah_id, $block_id, $group_id, $err);
        if (! $ok) {
            error_log("Assignment for edah $edah_id, block $block_id, group $group_id failed");
            if (empty($err)) {
                $err = "Unknown assignment error";
            }
            echo genErrorPage($err);
            exit;
        }
    }
    // Assignments done - redirect to the assignment page.
    error_log("Assigned edah $edah_id, block $block_id OK");
    header("Location: $levelHomeUrl");
    
    exit;
    
    ?>
    
