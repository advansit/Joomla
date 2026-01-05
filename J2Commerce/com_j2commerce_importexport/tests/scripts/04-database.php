<?php
/**
 * Test 04: Database Integrity
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class DatabaseTest {
    private $db;
    private $results = [];
    
    public function __construct() { $this->db = Factory::getDbo(); }
    
    public function run() {
        echo "=== Database Integrity Test ===\n\n";
        $this->testExtensionEntry();
        $this->printResults();
        return $this->allTestsPassed();
    }
    
    private function testExtensionEntry() {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__extensions')
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_j2commerce_importexport'));
        $this->db->setQuery($query);
        $count = $this->db->loadResult();
        
        if ($count > 0) {
            $this->pass("Extension entry exists in database");
        } else {
            $this->fail("Extension entry not found");
        }
    }
    
    private function pass($msg) { $this->results[] = ['status' => 'PASS', 'message' => $msg]; echo "✅ PASS: $msg\n"; }
    private function fail($msg) { $this->results[] = ['status' => 'FAIL', 'message' => $msg]; echo "❌ FAIL: $msg\n"; }
    private function printResults() { echo "\n=== Results ===\n"; }
    private function allTestsPassed() { return !in_array('FAIL', array_column($this->results, 'status')); }
}

try {
    $app = Factory::getApplication('site');
    $test = new DatabaseTest();
    exit($test->run() ? 0 : 1);
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
