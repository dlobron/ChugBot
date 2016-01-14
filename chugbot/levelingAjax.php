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
    
    if (isset($_POST["reassign"]) ||
        isset($_POST["get_current_stats"])) {
        $edah_id = $_POST["edah"];
        $block_id = $_POST["block"];
    
        $sql = "SELECT group_id, name FROM groups";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $dbErr = dbErrorString($sql, $mysqli->error);
            echo genErrorPage($dbErr);
            exit;
        }
        // Loop through groups.  Do each assignment (if requested), and grab assignment
        // stats.
        $err = "";
        $choiceCounts = array();
        $stats = array();
        $sKeys = array("under_min_list", "over_max_list");
        $choiceKeys = array("first_choice_ct", "second_choice_ct", "third_choice_ct", "fourth_choice_or_worse_ct");
        while ($row = mysqli_fetch_array($result, MYSQL_NUM)) {
            $group_id = intval($row[0]);
            $group_name = $row[1];
            if (isset($_POST["reassign"])) {
                $ok = do_assignment($edah_id, $block_id, $group_id, $err);
                if (! $ok) {
                    header('HTTP/1.1 500 Internal Server Error');
                    die(json_encode(array("error" => $err)));
                }
            }
            $sql = "SELECT * FROM assignments WHERE edah_id = $edah_id AND " .
            "group_id = $group_id AND block_id = $block_id";
            $result2 = $mysqli->query($sql);
            if ($result2 == FALSE) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array("error" => "Database error: can't connect to DB")));
            }
            $row = mysqli_fetch_assoc($result2);
            // Increment choice counts
            foreach ($choiceKeys as $choiceKey) {
                if ($row["$choiceKey"] != NULL) {
                    if (! array_key_exists($choiceKey, $choiceCounts)) {
                        $choiceCounts[$choiceKey] = intval($row["$choiceKey"]);
                    } else {
                        $choiceCounts[$choiceKey] += intval($row["$choiceKey"]);
                    }
                }
            }
            // Note under-min and over-max chugim.
            foreach ($sKeys as $key) {
                if (! empty($row[$key]) ) {
                    if (array_key_exists($key, $stats)) {
                        $stats[$key] .= ", " . $row[$key];
                    } else {
                        $stats[$key] = $row[$key];
                    }
                }
            }
        }
        $statstxt = "";
        for ($i = 0; $i < count($choiceKeys); $i++) {
            $choice = $i + 1;
            if ($choice == 4) {
                $choice .= " or worse";
            }
            $cKey = $choiceKeys[$i];
            $statstxt .= "Choice $choice count: <b>" . $choiceCounts[$cKey] . "</b><br>";
        }
        foreach ($sKeys as $key) {
            if (! array_key_exists($key, $stats)) {
                $stats[$key] = "none";
            }
        }
        $stats["statstxt"] = $statstxt;
        
        $mysqli->close();
        echo json_encode($stats);
        
        exit;
    }
    
    
    ?>
