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

    // The next three functions are variations on addColumn.  This one
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

    // This is similar to addColumn, except the column appears in a WHERE
    // clause.
    public function addWhereColumn($col, $val, $type)
    {
        array_push($this->whereColNames, $col);
        array_push($this->colVals, $val);
        $this->colTypes .= $type;
    }

    private function buildWhereClause()
    {
        if (count($this->whereColNames) == 0) {
            return;
        }
        $this->whereClause = "WHERE ";
        for ($i = 0; $i < count($this->whereColNames); $i++) {
            $colName = $this->whereColNames[$i];
            $this->whereClause .= "$colName = ? ";
            if ($i < (count($this->whereColNames) - 1)) {
                $this->whereClause .= "AND ";
            }
        }
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

        $action = "INSERT";
        if ($replace) {
            $action = "REPLACE";
        }
        $insertOk = $this->doQuery("$action $this->ignore INTO $table ($colCsv) VALUES ($qmCsv)",
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
