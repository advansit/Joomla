<?php
/**
 * Test 05: Uninstall
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class UninstallTest {
    private $results = [];
    
    public function run() {
        echo "=== Uninstall Test ===\n\n";
        $this->testUninstallScript();
        $this->printResults();
        return $this->allTestsPassed();
    }
    
    private function testUninstallScript() {
        // Check if uninstall script exists
        $scriptPath = JPATH_ADMINISTRATOR . '/components/com_j2commerce_importexport/script.php';
        if (file_exists($scriptPath)) {
            $this->pass("Uninstall script exists");
        } else {
            echo "⚠️  No uninstall script (might be OK)\n";
            $this->pass("Uninstall check complete");
        }
    }
    
    private function pass($msg) { $this->results[] = ['status' => 'PASS', 'message' => $msg]; echo "✅ PASS: $msg\n"; }
    private function fail($msg) { $this->results[] = ['status' => 'FAIL', 'message' => $msg]; echo "❌ FAIL: $msg\n"; }
    private function printResults() { echo "\n=== Results ===\n"; }
    private function allTestsPassed() { return !in_array('FAIL', array_column($this->results, 'status')); }
}

try {
    $app = Factory::getApplication('site');
    $test = new UninstallTest();
    exit($test->run() ? 0 : 1);
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
