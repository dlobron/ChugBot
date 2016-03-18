<?php
    include_once 'functions.php';

    class DbConn {
        function __construct() {
            $this->mysqli = connect_db();
        }
    
        function __destruct() {
            $this->stmt->close();
            $this->mysqli->close();
        }
    
        // Add a column value, and its type.  Possible types are:
        // i => integer, d => double, s => string, b => blob
        public function addColVal ($val, $type) {
            array_push($this->colVals, $val);
            $this->colTypes .= $type;
        }
    
        public function doQuery ($paramSql, &$err) {
            $this->stmt = $this->mysqli->prepare($paramSql);
            if ($this->stmt == FALSE) {
                $err = dbErrorString("Failed to prepare $paramSql", $this->mysqli->error);
                return FALSE;
            }
            $paramsByRef[] = &$this->colTypes;
            for ($i = 0; $i < count($this->colVals); $i++) {
                $paramsByRef[] = &$this->colVals[$i];
            }
            $bindOk = call_user_func_array(array(&$this->stmt, 'bind_param'), $paramsByRef);
            if ($bindOk == FALSE) {
                $err = dbErrorString("Failed to bind $paramSql", $this->mysqli->error);
                return FALSE;
            }
            $exOk = $this->stmt->execute();
            if ($exOk == FALSE) {
                $err = dbErrorString("Failed to execute $paramSql", $this->mysqli->error);
                return FALSE;
            }
            
            return TRUE;
        }
    
    
        private $mysqli;
        private $stmt;
        private $colVals = array();
        private $colTypes = "";
    }

