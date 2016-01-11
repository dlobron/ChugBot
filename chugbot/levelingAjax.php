<?php
    session_start();
    include 'assignment.php';
    
    header("content-type:application/json");

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
                        );
        $mysqli->close();
        echo json_encode($retVal);
        exit();
    }
    
    if (isset($_POST["reassign"])) {
        $edah_id = $_POST["edah"];
        $block_id = $_POST["block"];
    
        $sql = "SELECT group_id, name FROM groups";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $dbErr = dbErrorString($sql, $mysqli->error);
            echo genErrorPage($dbErr);
            exit;
        }
        // Do the actual assignments, recording results as we go.
        $err = "";
        $stats = array();
        $undermin = "";
        $overmax = "";
        $choiceKeys = array("first_choice_ct", "second_choice_ct", "third_choice_ct", "fourth_choice_or_worse_ct");
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $group_id = intval($row[0]);
            $group_name = $row[1];
            $ok = do_assignment($edah_id, $block_id, $group_id, $err);
            if (! $ok) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => $err)));
            }
            $sql = "SELECT * FROM assignments WHERE edah_id = $edah_id AND " .
            "group_id = $group_id AND block_id = $block_id";
            $result = $mysqli->query($sql);
            if ($result == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => "Database error: can't connect to DB")));
            }
            $row = $mysqli_fetch_assoc();
            foreach ($choiceKeys as $choiceKey) {
                if ($row["$choiceKey"] != NULL) {
                    if (! array_key_exists($choiceKey, $stats)) {
                        $stats[choiceKey] = 1;
                    } else {
                        $stats[choiceKey]++;
                    }
                }
            }
            
            
        }
        
        $mysqli->close();
        echo json_encode($nameMap);
        
        exit;
    }
    
    
    ?>
