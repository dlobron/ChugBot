<?php
    use PHPUnit\Framework\TestCase;
    include_once '../dbConn.php';
    include_once 'common.php';

    abstract class DatabaseTestBase extends PHPUnit_Extensions_Database_TestCase
    {
        // only instantiate pdo once for test clean-up/fixture load
        static private $pdo = null;
        
        // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
        private $conn = null;
        
        final public function getConnection()
        {
            if ($this->conn === null) {
                if (self::$pdo == null) {
                    self::$pdo = new PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'] );
                }
                $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
            }
            
            return $this->conn;
        }
    }
    
    class DatabaseTest extends DatabaseTestBase {
        
        public function getDataSet() {
            return $this->createMySQLXMLDataSet('dbTestState1.xml');
        }
        
        public function testCamperCount() {
            $this->assertEquals(10, $this->getConnection()->getRowCount('campers'),
                                ERRSTR . "bad camper row count: db setup incorrect");
        }
        
        public function testInsertAndDelete() {
            $err = "";
            $db = new DbConn();
            $db->addColumn("edah_id", 1, 'i');
            $db->addColumn("session_id", 1, 'i');
            $db->addColumn("first", "Carlo", 's');
            $db->addColumn("last", "Gesualdo", 's');
            $db->addColumn("email", "baroque@music.org", 's');
            $db->addColumn("bunk_id", 1, 'i');
            $db->insertIntoTable("campers", $err);
            $this->assertEmpty($err, "Insertion error");
            $this->assertEquals(11, $this->getConnection()->getRowCount('campers'),
                                ERRSTR . "new camper not found");            
            $db = new DbConn();
            $db->addWhereColumn("last", "Gesualdo", 's');
            $db->deleteFromTable("campers", $err);
            $this->assertEmpty($err, "Delete error");
            $this->assertEquals(10, $this->getConnection()->getRowCount('campers'),
                                ERRSTR . "new camper not deleted");
        }
        
        private $conn = null;
    }

    
