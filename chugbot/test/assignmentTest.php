<?php
    use PHPUnit\Framework\TestCase;
    $_SESSION['camper_logged_in'] = TRUE;
    
    include_once '../dbConn.php';
    include_once '../functions.php';
    include_once '../assignment.php';
    include_once 'common.php';
    
    class AssignmentTest extends DatabaseTestBase {
        
        public function getDataSet() {
            return $this->createMySQLXMLDataSet('dbTestState1.xml');
        }
        
        // Test the assignment function.  The arguments are:
        // ($edah_ids, $block_id, $group_id, &$err)
        public function testAssignment() {
            $err = "";
            $edahId2Name = array();
            $blockId2Name = array();
            $groupId2Name = array();
            fillId2Name(NULL, $edahId2Name, $err,
                        "edah_id", "edot");
            fillId2Name(NULL, $blockId2Name, $dbErr,
                        "block_id", "blocks");
            fillId2Name(NULL, $groupId2Name, $dbErr,
                        "group_id", "groups");
            $this->assertEmpty($err, ERRSTR . "fillId2Name error");
            $edah_ids = array(1, 2);
            $edot_names = $edahId2Name['1'] . " and " . $edahId2Name['2'];
            $block_ids = array(1, 2);
            $group_ids = array(1, 2, 3);
            echo "\n10-12 \"WARNING: Preferred\" and \"WARNING: No preference\" lines are expected above due to corner case tests.";
            foreach ($block_ids as $block_id) {
                foreach ($group_ids as $group_id) {
                    echo "\nRunning assignment for block " . $blockId2Name[$block_id] .
                    ", group " . $groupId2Name[$group_id] . ", edot " .
                    $edot_names;
                    do_assignment($edah_ids, $block_id, $group_id, $err);
                }
            }
            
            $this->assertNotEmpty($err, ERRSTR . "select error");
            $err = ""; // Reset err to empty.
            
            // Verify that the assignment is optimal and correct.  In particular:
            // All chugim should be under max.
            $db = new DbConn();
            $sql = "select c.chug_id chug_id, c.max_size max_size, sum(1) num_assigned from chugim c, " .
            "matches m, chug_instances i where m.chug_instance_id = i.chug_instance_id and i.block_id = 1 " .
            "and i.chug_id = c.chug_id and c.max_size > 0 group by 1 having num_assigned > max_size";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEmpty($err, ERRSTR . "select error");
            $this->assertEquals($result->num_rows, 0, ERRSTR . "over-max assignment in block 1");
            $db = new DbConn();
            $sql = "select c.chug_id chug_id, c.max_size max_size, sum(1) num_assigned from chugim c, " .
            "matches m, chug_instances i where m.chug_instance_id = i.chug_instance_id and i.block_id = 2 " .
            "and i.chug_id = c.chug_id and c.max_size > 0 group by 1 having num_assigned > max_size";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEmpty($err, ERRSTR . "select error");
            $this->assertEquals($result->num_rows, 0, ERRSTR . "over-max assignment in block 2");
            
            // All assigned chugim should be allowed for the assigned camper's edah.
            $db = new DbConn();
            $sql = "select m.match_id bad_match_id from matches m, campers c, chug_instances i " .
            "where m.camper_id = c.camper_id and m.chug_instance_id = i.chug_instance_id and " .
            "c.edah_id not in (select edah_id from edot_for_chug where chug_id = i.chug_id)";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEquals($result->num_rows, 0, ERRSTR . "chug not allowed for camper edah");
            
            // Chugim that are only eligible for one edah should only have that
            // edah assigned to them:
            // Disallowed Krav Maga from Ilanot 1
            // Disallowed Cooking from Kochavim
            // Berlioz (Ilanot 1): first choice for aleph = Krav Maga, second = Cooking
            // Handel (Kochavim): first choice for aleph = Cooking, second = Krav Maga
            // Expect: Both should get their second (allowed) choice.
            $db = new DbConn();
            $sql = "select m.match_id from matches m, chug_instances i " .
            "where m.camper_id=4 and m.chug_instance_id = i.chug_instance_id " .
            "and i.block_id=1 and i.chug_id=3";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEmpty($err, ERRSTR . "select error");
            $this->assertEquals($result->num_rows, 1, ERRSTR . "incorrect assignment (Berlioz)");
            $db = new DbConn();
            $sql = "select m.match_id from matches m, chug_instances i " .
            "where m.camper_id=13 and m.chug_instance_id = i.chug_instance_id " .
            "and i.block_id=1 and i.chug_id=2";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEmpty($err, ERRSTR . "select error");
            $this->assertEquals($result->num_rows, 1, ERRSTR . "incorrect assignment (Handel)");
            
            // Choice levels should be as high as possible.
            // Expect: Mozart should always be assigned to Archery for aleph.
            $db = new DbConn();
            $sql = "select m.match_id from matches m, chug_instances i " .
            "where m.camper_id=1 and m.chug_instance_id = i.chug_instance_id " .
            "and i.block_id=1 and i.chug_id=6";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEmpty($err, ERRSTR . "select error");
            $this->assertEquals($result->num_rows, 1, ERRSTR . "incorrect assignment (Mozart)");
            
            // Always-first campers should have all first choices.
            // Expect: Bach should have his first choice every time.
            $db = new DbConn();
            $sql = "select m.match_id from matches m, chug_instances i " .
            "where m.camper_id=3 and m.chug_instance_id = i.chug_instance_id " .
            "and i.block_id=1 and i.chug_id in (13,7,4)";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEmpty($err, ERRSTR . "select error");
            $this->assertEquals($result->num_rows, 3, ERRSTR . "incorrect assignment (Bach)");
            
            // Chugim should have campers from both edot, if eligible.
            // Expect: R. Schumann and Scriabin should be assigned to Swimming for aleph.
            $db = new DbConn();
            $sql = "select m.match_id from matches m, chug_instances i " .
            "where m.camper_id=8 and m.chug_instance_id = i.chug_instance_id " .
            "and i.block_id=1 and i.chug_id=1";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEmpty($err, ERRSTR . "select error");
            $this->assertEquals($result->num_rows, 1, ERRSTR . "incorrect assignment (R. Schumann)");
            $db = new DbConn();
            $sql = "select m.match_id from matches m, chug_instances i " .
            "where m.camper_id=15 and m.chug_instance_id = i.chug_instance_id " .
            "and i.block_id=1 and i.chug_id=1";
            $result = $db->runQueryDirectly($sql, $err);
            $this->assertEmpty($err, ERRSTR . "select error");
            $this->assertEquals($result->num_rows, 1, ERRSTR . "incorrect assignment (Scriabin)");
    
        }
    }

    ?>
