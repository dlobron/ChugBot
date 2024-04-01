<?php
    session_start();
    include_once 'dbConn.php';
    include_once 'functions.php';
    bounceToLogin();
    setup_camp_specific_terminology_constants();

    $edahId = test_get_input('edah');
    $blockId = test_get_input('block');
    $scheduleTemplate = html_entity_decode(test_get_input('schedule-template'));

    $blockIdsOverride = [];
    for ($i = 0; $i < count($_GET)-4; $i ++) {
        $temp = $_GET[/*'group' . */$i];
        if (is_numeric($temp)) {
            array_push($blockIdsOverride, $temp);
        }
        else {
            array_push($blockIdsOverride, $blockId);
        }
    }
    echo $scheduleTemplate;


    // ***************************************************************************
    // **************************** Build SQL Queries ****************************
    // ***************************************************************************

    // Begin by creating a SQL query to get names of all chug groups which the
    // selected edah has available
    $localErr = "";
    $dbc = new DbConn();
    $sql = "SELECT e.group_id group_id, g.name group_name FROM edot_for_group e, chug_groups g WHERE e.edah_id IN (" . 
        $edahId . ") AND e.group_id = g.group_id GROUP BY e.group_id HAVING COUNT(e.edah_id) = 1";

    $result = $dbc->doQuery($sql, $localErr);
    if ($result == false) {
        echo dbErrorString($sql, $localErr);
        exit();
    }

    // Next, build sub-query for the included groups -- notably, also saves the groups
    // (from previous query) to $chugGroups
    $groupClause = "";
    $i = 0;
    $chugGroups = []; // Array which will hold every applicable chug group
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $gn = $row[1];
        array_push($chugGroups, $gn);
        $groupClause .= " MAX(CASE WHEN g.group_name = \"" . $gn . "\" THEN g.chug_name ELSE NULL END) AS \"" . $gn . "\"";
        if ($i++ < ($result->num_rows - 1)) {
            $groupClause .= ", ";
        }
    }

    // Now moving on to the complete query, broken down into a couple parts:

    // sub-part 1: sql query returning table with info about each camper in one edah for a specific block
    $camperSqlForBlock = "SELECT CONCAT(c.last, ', ', c.first) name, c.camper_id camper_id, IFNULL(bu.name, \"-\") bunk, " .
        "bu.bunk_id, e.name edah, e.edah_id, e.rosh_name rosh, e.rosh_phone roshphone, p.block_id block_id, b.name BLOCK " .
        "FROM campers c, bunks bu, edot e, " .
            "(SELECT m.camper_id camper_id, c.name chug_name, c.chug_id chug_id, g.name group_name, b.name block_name, " .
                "b.block_id block_id " .
                    "FROM chug_groups g, blocks b, (matches m, chug_instances i, chugim c) " .
                    "WHERE i.block_id = b.block_id AND c.chug_id = i.chug_id AND c.group_id = g.group_id " .
                    " AND m.chug_instance_id = i.chug_instance_id ) p " .
        "JOIN blocks AS b ON b.block_id = p.block_id WHERE c.camper_id = p.camper_id AND c.edah_id = e.edah_id " .
        "AND c.bunk_id = bu.bunk_id AND e.edah_id = " . $edahId . " AND b.block_id = " . $blockId .
        " GROUP BY camper_id, block_id ORDER BY name";

    // sub-part 2: sql query returning table with each camper's chug assignments for every block and group name
    $assignmentSqlForBlocks = "SELECT m.camper_id camper_id, c.name chug_name, c.chug_id chug_id, g.name group_name, " .
        "b.name block_name, b.block_id block_id " .
        "FROM chug_groups g, blocks b, (matches m, chug_instances i, chugim c) " .
        "WHERE i.block_id = b.block_id AND c.chug_id = i.chug_id AND c.group_id = g.group_id " .
        "AND m.chug_instance_id = i.chug_instance_id";
    
    // Full sql query
    $sql = "SELECT p.name, p.bunk, p.edah, p.rosh, p.roshphone, " .
        $groupClause .
        " FROM (" . $camperSqlForBlock . ") p " . 
        "LEFT OUTER JOIN (" . $assignmentSqlForBlocks . ") g ON p.camper_id = g.camper_id " .
        "WHERE (";
    // to include overridden blocks
    for ($i = 0; $i < count($blockIdsOverride); $i++) {
        $sql .= " (g.block_id = " . $blockIdsOverride[$i] . " AND g.group_name = \"" . $chugGroups[$i] . "\")";
        if(count($blockIdsOverride) - $i > 1) {  // if there are more to add
            $sql .= " OR"; 
        }
        else { // last block!
            $sql .= ") ";
        }
    }
    $sql .= "GROUP BY p.name, p.bunk, p.edah, p.rosh, p.roshphone";

    echo $sql;
    $result = $dbc->doQuery($sql, $localErr);
    if ($result == false) {
        echo dbErrorString($sql, $localErr);
        exit();
    }
?>