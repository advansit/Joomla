#!/usr/bin/env php
<?php
// Write directly to a log file since Joomla suppresses all output
$logFile = '/tmp/installation-test.log';
file_put_contents($logFile, ''); // Clear log file

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

// Helper function to write output
function write_output($message) {
    global $logFile;
    file_put_contents($logFile, $message, FILE_APPEND);
    // Also try to write to STDOUT
    echo $message;
}

class InstallationVerificationTest {
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $component = 'j2commerce_importexport';

    public function __construct() {
        $this->db = Factory::getDbo();
    }

    public function run(): bool {
        write_output("=== Installation Verification Tests ===\n\n");
        $this->testExtensionRegistered();
        $this->testFilesExist();
        $this->printSummary();
        return $this->failed === 0;
    }

    private function testExtensionRegistered(): void {
        write_output("Test: Component registered in database... ");
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_' . $this->component));
        
        $this->db->setQuery($query);
        $ext = $this->db->loadObject();
        
        if ($ext) {
            write_output("✅ PASS\n");
            write_output("  Extension ID: {$ext->extension_id}\n");
            write_output("  Name: {$ext->name}\n");
            $this->passed++;
        } else {
            write_output("❌ FAIL\n");
            $this->failed++;
        }
    }

    private function testFilesExist(): void {
        write_output("\nTest: Component files exist... ");
        $path = "/var/www/html/administrator/components/com_{$this->component}";
        
        if (is_dir($path)) {
            write_output("✅ PASS\n");
            write_output("  Path: $path\n");
            $this->passed++;
        } else {
            write_output("❌ FAIL\n");
            $this->failed++;
        }
    }

    private function printSummary(): void {
        write_output("\n=== Summary ===\n");
        write_output("Passed: {$this->passed}\n");
        write_output("Failed: {$this->failed}\n");
        if ($this->failed === 0) write_output("✅ All tests passed\n");
        else write_output("❌ {$this->failed} test(s) failed\n");
    }
}

try {
    $app = Factory::getApplication('administrator');
    $test = new InstallationVerificationTest();
    $result = $test->run();
    
    // Copy log file to STDOUT so it gets captured
    if (file_exists($logFile)) {
        echo file_get_contents($logFile);
    }
    
    exit($result ? 0 : 1);
} catch (Exception $e) {
    write_output("\n❌ FATAL ERROR: " . $e->getMessage() . "\n");
    if (file_exists($logFile)) {
        echo file_get_contents($logFile);
    }
    exit(1);
}
