#!/usr/bin/env php
<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

class UninstallVerificationTest {
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $type = 'plugin';
    private $folder = 'j2commerce';
    private $element = 'productcompare';

    public function __construct() {
        $this->db = Factory::getDbo();
    }

    public function run(): bool {
        echo "=== Uninstall Verification Tests ===\n\n";
        $extId = $this->getExtensionId();
        if (!$extId) {
            echo "❌ Extension not found\n";
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
        echo "Test: Uninstalling extension... ";
        try {
            $installer = Installer::getInstance();
            $result = $installer->uninstall($this->type, $extId);
            if ($result) {
                echo "✅ PASS\n";
                $this->passed++;
            } else {
                echo "❌ FAIL\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "❌ FAIL: {$e->getMessage()}\n";
            $this->failed++;
        }
    }

    private function testExtensionRemoved(): void {
        echo "\nTest: Extension removed from database... ";
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
            echo "✅ PASS\n";
            $this->passed++;
        } else {
            echo "❌ FAIL\n";
            $this->failed++;
        }
    }

    private function testFilesRemoved(): void {
        echo "\nTest: Extension files removed... ";
        
        if ($this->type === 'plugin') {
            $path = "/var/www/html/plugins/{$this->folder}/{$this->element}";
        } else {
            $path = "/var/www/html/administrator/components/com_{$this->element}";
        }
        
        if (!file_exists($path)) {
            echo "✅ PASS\n";
            $this->passed++;
        } else {
            echo "❌ FAIL\n";
            $this->failed++;
        }
    }

    private function printSummary(): void {
        echo "\n=== Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        if ($this->failed === 0) echo "✅ All tests passed\n";
        else echo "❌ {$this->failed} test(s) failed\n";
    }
}

try {
    $app = Factory::getApplication('administrator');
    $test = new UninstallVerificationTest();
    exit($test->run() ? 0 : 1);
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
