<?php
    use PHPUnit\Framework\TestCase;
    include_once '../dbConn.php';
    include_once 'common.php';
    
    class DatabaseTest extends DatabaseTestBase {
        
        public function getDataSet() {
            return $this->createMySQLXMLDataSet('dbTestState1.xml');
        }
        
        public function testCamperCount() {
            $this->assertEquals(18, $this->getConnection()->getRowCount('campers'),
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
            $this->assertEmpty($err, ERRSTR . "insertion error");
            $this->assertEquals(19, $this->getConnection()->getRowCount('campers'),
                                ERRSTR . "new camper not found");            
            $db = new DbConn();
            $db->addWhereColumn("last", "Gesualdo", 's');
            $db->deleteFromTable("campers", $err);
            $this->assertEmpty($err, "Delete error");
            $this->assertEquals(18, $this->getConnection()->getRowCount('campers'),
                                ERRSTR . "new camper not deleted");
        }
    }

    ?>
