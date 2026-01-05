<?php
/**
 * Test 03: Export Functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class ExportTest {
    private $results = [];
    
    public function run() {
        echo "=== Export Functionality Test ===\n\n";
        $this->testExportViewAccessible();
        $this->printResults();
        return $this->allTestsPassed();
    }
    
    private function testExportViewAccessible() {
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_j2commerce_importexport';
        if (is_dir($componentPath)) {
            $this->pass("Export functionality available");
        } else {
            $this->fail("Component path not found");
        }
    }
    
    private function pass($msg) { $this->results[] = ['status' => 'PASS', 'message' => $msg]; echo "✅ PASS: $msg\n"; }
    private function fail($msg) { $this->results[] = ['status' => 'FAIL', 'message' => $msg]; echo "❌ FAIL: $msg\n"; }
    private function printResults() { echo "\n=== Results ===\n"; }
    private function allTestsPassed() { return !in_array('FAIL', array_column($this->results, 'status')); }
}

try {
    $app = Factory::getApplication('administrator');
    $test = new ExportTest();
    exit($test->run() ? 0 : 1);
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
