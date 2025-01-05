<?php
include_once 'constants.php';
include_once 'functions.php';

function connect_db($archiveYear = null)
{
    $dbName = MYSQL_DB;
    if (!is_null($archiveYear)) {
        $dbName .= $archiveYear;
    }
    $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, $dbName);
    if (mysqli_connect_error()) {
        die('Connect Error: (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
    }
    return $mysqli;
}

class DbConn
{
    public function __construct($archiveYear = null)
    {
        $this->archiveYear = $archiveYear;
        $this->mysqli = connect_db($this->archiveYear);
    }

    public function __destruct()
    {
        $this->mysqli->close();
    }

    public function mysqliClient()
    {
        return $this->mysqli;
    }

    // Run a query directly, with no interpolation.  Use only for queries
    // without user-supplied parameters.
    public function runQueryDirectly($sql, &$err)
    {
        if (!$this->mysqli->ping()) {
            error_log("DbConn: runQueryDirectly: database connection was dropped: trying to reconnect");
            $this->mysqli = connect_db($this->archiveYear);
        }
        $result = $this->mysqli->query($sql);
        if ($result == false) {
            $err = dbErrorString($sql, $this->mysqli->error);
        }

        return $result;
    }

    // Add a column value, and its type.  Possible types are:
    // i => integer, d => double, s => string, b => blob
    public function addColumn($col, $val, $type)
    {
        array_push($this->colNames, $col);
        array_push($this->colVals, $val);
        $this->colTypes .= $type;
    }

    // The next four functions are variations on addColumn.  This one
    // is for SELECT clauses, where only the column name is needed.
    public function addSelectColumn($col)
    {
        array_push($this->colNames, $col);
    }

    // This function is for columns in raw queries, where we only need the
    // column value and type.
    public function addColVal($val, $type)
    {
        array_push($this->colVals, $val);
        $this->colTypes .= $type;
    }

    // This function does the opposite of addColVal - it adds the name of the column
    public function addColName($col)
    {
        array_push($this->colNames, $col);
    }

    // This is similar to addColumn, except the column appears in a WHERE
    // clause.
    public function addWhereColumn($col, $val, $type, $andOr = "AND")
    {
        array_push($this->whereColNames, $col);
        array_push($this->colVals, $val);
        // Determine if next column should be and/or for conditional - default AND
        if ($andOr != "AND" && $andOr != "OR") {
            $andOr = "AND";
        }
        array_push($this->whereColAndOr, $andOr);
        $this->colTypes .= $type;
    }

    public function addWhereBreak()
    {
        if (count($this->whereColAndOr) > 0) {
            array_push($this->whereColNames, NULL);
            $this->whereColAndOr[count($this->whereColAndOr)-1] = ") OR (";
            array_push($this->whereColAndOr, NULL);
        }
    }

    private function buildWhereClause()
    {
        if (count($this->whereColNames) == 0) {
            return;
        }
        $this->whereClause = "WHERE (";
        for ($i = 0; $i < count($this->whereColNames); $i++) {
            $colName = $this->whereColNames[$i];
            if (strlen($colName) > 0) {
                $this->whereClause .= "$colName = ? ";
            }
            if ($i < (count($this->whereColNames) - 1)) {
                $this->whereClause .= $this->whereColAndOr[$i] . " ";
            }
            /*if ($this->whereColBreaks[0] = $i) {
                //$this->whereClause .= ") OR (";
                array_shift($this->whereColBreaks);
            }*/
        }
        $this->whereClause .= ")";
    }

    // Do a simple SELECT foo, bar FROM baz WHERE blurfl query.  More
    // complicated SELECTs must be done with doQuery.
    // TODO: Add support for any kind of SELECT.
    public function simpleSelectFromTable($table, &$err)
    {
        $this->isSelect = true;
        $selCols = "";
        for ($i = 0; $i < count($this->colNames); $i++) {
            $colName = $this->colNames[$i];
            $selCols .= "$colName";
            if ($i < (count($this->colNames) - 1)) {
                $selCols .= ",";
            }
        }

        $this->buildWhereClause();
        return $this->doQuery("SELECT $selCols FROM $table $this->whereClause $this->orderByClause", $err);
    }

    public function addOrderByClause($clause)
    {
        $this->orderByClause = $clause;
    }

    public function deleteFromTable($table, &$err)
    {
        $this->buildWhereClause();
        return $this->doQuery("DELETE FROM $table $this->whereClause", $err);
    }

    public function addIgnore()
    {
        $this->ignore = "IGNORE";
    }

    public function updateTable($table, &$err)
    {
        $setClause = "SET ";
        for ($i = 0; $i < count($this->colNames); $i++) {
            $colName = $this->colNames[$i];
            $setClause .= "$colName = ?";
            if ($i < (count($this->colNames) - 1)) {
                $setClause .= ",";
            }
        }
        $this->buildWhereClause();

        return $this->doQuery("UPDATE $this->ignore $table $setClause $this->whereClause", $err);
    }

    public function insertIntoTable($table, &$err, $replace = false)
    {
        $qmCsv = "";
        $colCsv = "";
        foreach ($this->colNames as $colName) {
            // Build the column and parameter strings.
            if (empty($qmCsv)) {
                $qmCsv = "?";
            } else {
                $qmCsv .= ", ?";
            }
            if (empty($colCsv)) {
                $colCsv = $colName;
            } else {
                $colCsv .= ", $colName";
            }
        }
        $qmCsv = "(" . $qmCsv . ")";

        // If multiple sets of values are being inserted:
        if (count($this->colNames) != count($this->colVals)) {
            // Note: assumes other values were added using addColVar in same order as columns
            // e.g. (a, b, c), (d, e, f), (g, h, i) are added in the following order:
            //      addColumn(a), addColumn(b), addColumn(c)
            //      addColVal(d), addColVal(e), addColVal(f)
            //      addColVal(g), addColVal(h), addColVal(i)
            // Here, repeate $qmCsv the number of entries being added
            $numEntries = intdiv(count($this->colVals), count($this->colNames));

            $tempQmCsv = str_repeat($qmCsv . ", ", $numEntries - 1);
            $tempQmCsv .= $qmCsv;
            $qmCsv = $tempQmCsv;
        }

        $action = "INSERT";
        if ($replace) {
            $action = "REPLACE";
        }
        $insertOk = $this->doQuery("$action $this->ignore INTO $table ($colCsv) VALUES $qmCsv",
            $err);
        $this->insert_id = $this->mysqli->insert_id;

        return $insertOk;
    }

    public function doQuery($paramSql, &$err)
    {
        if (!$this->mysqli->ping()) {
            error_log("DbConn: doQuery: database connection was dropped: trying to reconnect");
            $this->mysqli = connect_db($this->archiveYear);
        }
        if (startsWith($paramSql, "SELECT") ||
            startsWith($paramSql, "select")) {
            $this->isSelect = true;
        }
        $this->stmt = $this->mysqli->prepare($paramSql);
        if ($this->stmt == false) {
            $err = dbErrorString("Failed to prepare $paramSql", $this->mysqli->error);
            error_log($err);
            return false;
        }
        if ($this->colTypes) {
            $paramsByRef[] = &$this->colTypes;
            for ($i = 0; $i < count($this->colVals); $i++) {
                $paramsByRef[] = &$this->colVals[$i];
            }
            $bindOk = call_user_func_array(array(&$this->stmt, 'bind_param'), $paramsByRef);
            if ($bindOk == false) {
                $err = dbErrorString("Failed to bind $paramSql", $this->mysqli->error);
                error_log($err);
                $this->stmt->close();
                return false;
            }
        }
        $exOk = $this->stmt->execute();
        if ($exOk == false) {
            $err = dbErrorString("Failed to execute $paramSql", $this->mysqli->error);
            error_log($err);
            $this->stmt->close();
            return false;
        }
        $retVal = true;
        if ($this->isSelect) {
            $retVal = $this->stmt->get_result();
        }
        $this->stmt->close();

        return $retVal;
    }

    public function insertId()
    {
        return $this->insert_id;
    }

    private $archiveYear;
    private $insert_id;
    private $mysqli;
    private $stmt;
    private $colNames = array();
    private $whereColNames = array();
    private $whereColAndOr = array();
    private $whereColBreaks = array();
    private $colVals = array();
    private $colTypes = "";
    private $whereClause = "";
    private $orderByClause = "";
    private $ignore = "";
    public $isSelect = false;
}

// Utility function.  Pass NULL for the $archiveYear argument to use
// the current database.
function fillId2Name($archiveYear,
    &$id2Name, &$dbErr,
    $idColumn, $table, $secondIdColumn = null,
    $secondTable = null) {
    $db = new DbConn($archiveYear);
    $db->isSelect = true;
    $db->addSelectColumn($idColumn);
    if ($secondIdColumn) {
        $db->addSelectColumn($secondIdColumn);
    }
    $db->addSelectColumn("name");
    if ($table == "edot") {
        $db->addSelectColumn("sort_order");
        $db->addOrderByClause(" ORDER BY sort_order");
    }
    if ($table == "bunks") {
        $db->addOrderByClause(" ORDER BY name+0>0 DESC, name+0, LENGTH( name ), name");
    }
    $result = $db->simpleSelectFromTable($table, $dbErr);
    if ($result == false) {
        error_log($dbErr);
        return;
    }
    $secondId2Name = array();
    if ($secondTable) {
        $db = new DbConn();
        $db->isSelect = true;
        $db->addSelectColumn($secondIdColumn);
        $db->addSelectColumn("name");
        $result2 = $db->simpleSelectFromTable($secondTable, $dbErr);
        if ($result2 == false) {
            error_log($dbErr);
            return;
        }
        while ($row = $result2->fetch_array(MYSQLI_NUM)) {
            $secondId2Name[$row[0]] = $row[1];
        }
    }
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        if ($secondIdColumn !== null &&
            array_key_exists($secondIdColumn, $row) &&
            array_key_exists($row[$secondIdColumn], $secondId2Name)) {
            $id2Name[$row[$idColumn]] = $row["name"] . " - " . $secondId2Name[$row[$secondIdColumn]];
        } else {
            $id2Name[$row[$idColumn]] = $row["name"];
        }
    }
}
