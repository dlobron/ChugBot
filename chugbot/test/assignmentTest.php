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
            foreach ($block_ids as $block_id) {
                foreach ($group_ids as $group_id) {
                    echo "\nRunning assignment for block " . $blockId2Name[$block_id] .
                    ", group " . $groupId2Name[$group_id] . ", edot " .
                    $edot_names . " (some warnings are expected)";
                    do_assignment($edah_ids, $block_id, $group_id, $err);
                    $this->assertEmpty($err, ERRSTR . "assignment error");
                }
            }
            // Verify that the assignment is optimal and correct.  In particular:
            // All chugim should be under max.
            // Always-first campers should have all first choices.
            // Chugim should have campers from both edot, if eligible.
            // Chugim that are only eligible for one edah should only have that
            // edah assigned to them (TODO: we might need to edit edot_for_chug
            // so that some chugim are disallowed for edah 1 or 2 or both).  We
            // might also want to tweak prefs to be different, to exercise more
            // pref possibilities.
            // Choice levels should be as high as possible.
            // TODO
    
        }
    }

    ?>
