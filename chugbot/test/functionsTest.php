<?php
    // To run: phpunit functionsTest.php
    
    use PHPUnit\Framework\TestCase;
    include_once '../constants.php';
    include_once '../functions.php';
    
    class FunctionsTest extends TestCase {
        public function testStringFunctions() {
            $haystack = "California Clam Chowda";
            $this->assertTrue(startsWith($haystack, "California"));
            $this->assertFalse(startsWith($haystack, "New England")); // East Coast?  No way!
            $this->assertTrue(endsWith($haystack, "Chowda"));
            $this->assertFalse(endsWith($haystack, "Chowder")); // R.U. kidding me?
            
            $_SERVER['HTTPS'] = "on";
            $_SERVER['PHP_SELF'] = "/editChug.php";
            $_SERVER['HTTP_HOST'] = "127.0.0.1";
            $this->assertEquals(urlBaseText(), "https://127.0.0.1/");
        }
        public function testHashFunctions() {
            $_GET['block_ids'] = array('1', '2');
            $idHash = array();
            populateActiveIds($idHash, 'block_ids');
            $this->assertArrayHasKey('1', $idHash);
            $this->assertArrayHasKey('2', $idHash);
            $_POST['chug_ids'] = array('3', '4');
            $idHash2 = array();
            populateActiveIds($idHash2, 'chug_ids');
            $this->assertArrayHasKey('3', $idHash2);
            $this->assertArrayHasKey('4', $idHash2);
        }
    }
?>
