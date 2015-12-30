<?php
    session_start();
    include 'functions.php';
    
    header("content-type:application/json");

    $mysqli = connect_db();
    if (isset($_POST["names_for_id"])) {
        $edah_id = $_POST["edah_id"];
        $block_id = $_POST["block_id"];
        $edah_name = "";
        $block_name = "";
        $sql = "SELECT name FROM edot where edah_id=$edah_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database error: can't connect to DB")));
        }
        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $edah_name = $row[0];
        }
        $sql = "SELECT name FROM blocks where block_id=$block_id";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(array("error" => "Database error: can't connect to DB")));
        }
        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $block_name = $row[0];
        }
        $retVal = array(
                        'edahName' => $edah_name,
                        'blockName' => $block_name
                        );
        $mysqli->close();
        echo json_encode($retVal);
        exit();
    }
    
    ?>
