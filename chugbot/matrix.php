<?php
    session_start();
    include_once 'functions.php';
    include_once 'dbConn.php';
    
    // Require admin-level access to use any functions.
    if (! adminLoggedIn()) {
        $err = "ERROR: Admin user not logged in: matrix.php forbidden";
        error_log($err);
        header('HTTP/1.1 403 Forbidden');
        die(json_encode(array("error" => $err)));
    }
    
    // Get chugim and IDs, and exclusion status.
    if (isset($_POST["get_chug_map"])) {
        $db = new DbConn();
        $db->addSelectColumn("chug_id");
        $db->addSelectColumn("name");
        $db->addOrderByClause("ORDER BY name");
        $err = "";
        $result = $db->simpleSelectFromTable("chugim", $err);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => $err)));
        }
        $chugMap = array();
        while ($row = $result->fetch_assoc()) {
            $chugMap[$row["chug_id"]] = $row["name"];
        }
        
        echo json_encode($chugMap);
        exit();
    }

    // Update the exclusion table.


?>