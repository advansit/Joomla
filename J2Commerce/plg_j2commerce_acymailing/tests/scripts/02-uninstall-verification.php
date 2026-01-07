#!/usr/bin/env php
<?php
/**
 * Test 02: Uninstall Verification
 * Uninstalls the plugin and verifies clean removal
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

class UninstallVerificationTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Uninstall Verification Tests ===\n\n";

        $extensionId = $this->getExtensionId();
        
        if (!$extensionId) {
            echo "❌ Plugin not found - cannot test uninstall\n";
            return false;
        }

        $this->testUninstallPlugin($extensionId);
        $this->testExtensionRemoved();
        $this->testFilesRemoved();

        $this->printSummary();
        return $this->failed === 0;
    }

    private function getExtensionId(): ?int
    {
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_acymailing'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('j2commerce'));
        
        $this->db->setQuery($query);
        return $this->db->loadResult();
    }

    private function testUninstallPlugin(int $extensionId): void
    {
        echo "Test: Uninstalling plugin... ";
        
        try {
            $installer = Installer::getInstance();
            $result = $installer->uninstall('plugin', $extensionId);
            
            if ($result) {
                echo "✅ PASS\n";
                $this->passed++;
            } else {
                echo "❌ FAIL (Uninstall returned false)\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: {$e->getMessage()})\n";
            $this->failed++;
        }
    }

    private function testExtensionRemoved(): void
    {
        echo "\nTest: Extension removed from database... ";
        
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_acymailing'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('j2commerce'));
        
        $this->db->setQuery($query);
        $count = $this->db->loadResult();
        
        if ($count == 0) {
            echo "✅ PASS\n";
            $this->passed++;
        } else {
            echo "❌ FAIL (Extension still in database)\n";
            $this->failed++;
        }
    }

    private function testFilesRemoved(): void
    {
        echo "\nTest: Plugin files removed... ";
        
        $pluginDir = '/var/www/html/plugins/j2commerce/j2commerce_acymailing';
        
        if (!file_exists($pluginDir)) {
            echo "✅ PASS\n";
            $this->passed++;
        } else {
            echo "❌ FAIL (Plugin directory still exists)\n";
            $this->failed++;
        }
    }

    private function printSummary(): void
    {
        echo "\n=== Uninstall Verification Summary ===\n";
        echo "Total Tests: " . ($this->passed + $this->failed) . "\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        if ($this->failed === 0) {
            echo "✅ All uninstall tests passed\n";
        } else {
            echo "❌ {$this->failed} test(s) failed\n";
        }
    }
}

// Run tests
try {
    $app = Factory::getApplication('administrator');
    $test = new UninstallVerificationTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
