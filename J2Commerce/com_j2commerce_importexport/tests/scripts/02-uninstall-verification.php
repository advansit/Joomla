#!/usr/bin/env php
<?php
// Write directly to a log file since Joomla suppresses all output
$logFile = '/tmp/uninstall-test.log';
file_put_contents($logFile, ''); // Clear log file

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

// Helper function to write output
function write_log($message) {
    global $logFile;
    file_put_contents($logFile, $message, FILE_APPEND);
}

class UninstallVerificationTest {
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $type = 'plugin';
    private $folder = 'FOLDER_PLACEHOLDER';
    private $element = 'ELEMENT_PLACEHOLDER';

    public function __construct() {
        $this->db = Factory::getDbo();
    }

    public function run(): bool {
        write_log("=== Uninstall Verification Tests ===\n\n");
        $extId = $this->getExtensionId();
        if (!$extId) {
            write_log("❌ Extension not found\n");
            return false;
        }
        $this->testUninstall($extId);
        $this->testExtensionRemoved();
        $this->testFilesRemoved();
        $this->printSummary();
        return $this->failed === 0;
    }

    private function getExtensionId(): ?int {
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->element));
        
        if ($this->type === 'plugin') {
            $query->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($this->folder));
        }
        
        $this->db->setQuery($query);
        return $this->db->loadResult();
    }

    private function testUninstall(int $extId): void {
        write_log("Test: Uninstalling extension... ");
        try {
            $installer = Installer::getInstance();
            $result = $installer->uninstall($this->type, $extId);
            if ($result) {
                write_log("✅ PASS\n");
                $this->passed++;
            } else {
                write_log("❌ FAIL\n");
                $this->failed++;
            }
        } catch (Exception $e) {
            write_log("❌ FAIL: {$e->getMessage()}\n");
            $this->failed++;
        }
    }

    private function testExtensionRemoved(): void {
        write_log("\nTest: Extension removed from database... ");
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->element));
        
        if ($this->type === 'plugin') {
            $query->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($this->folder));
        }
        
        $this->db->setQuery($query);
        $count = $this->db->loadResult();
        
        if ($count == 0) {
            write_log("✅ PASS\n");
            $this->passed++;
        } else {
            write_log("❌ FAIL\n");
            $this->failed++;
        }
    }

    private function testFilesRemoved(): void {
        write_log("\nTest: Extension files removed... ");
        
        if ($this->type === 'plugin') {
            $path = "/var/www/html/plugins/{$this->folder}/{$this->element}";
        } else {
            $path = "/var/www/html/administrator/components/com_{$this->element}";
        }
        
        if (!file_exists($path)) {
            write_log("✅ PASS\n");
            $this->passed++;
        } else {
            write_log("❌ FAIL\n");
            $this->failed++;
        }
    }

    private function printSummary(): void {
        write_log("\n=== Summary ===\n");
        write_log("Passed: {$this->passed}\n");
        write_log("Failed: {$this->failed}\n");
        if ($this->failed === 0) write_log("✅ All tests passed\n");
        else write_log("❌ {$this->failed} test(s) failed\n");
    }
}

// Ensure output is not buffered
ob_implicit_flush(true);
ob_end_flush();

try {
    $app = Factory::getApplication('administrator');
    $test = new UninstallVerificationTest();
    $result = $test->run();
    
    // Force flush output
    if (ob_get_level()) ob_end_flush();
    
    // Copy log file to STDOUT so it gets captured
    if (file_exists($logFile)) {
        echo file_get_contents($logFile);
    }
    
    exit($result ? 0 : 1);
} catch (Exception $e) {
    write_log("\n❌ FATAL ERROR: " . $e->getMessage() . "\n");
    if (file_exists($logFile)) {
        echo file_get_contents($logFile);
    }
    exit(1);
}
