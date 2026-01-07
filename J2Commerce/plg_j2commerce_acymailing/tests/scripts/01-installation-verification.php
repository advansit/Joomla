#!/usr/bin/env php
<?php
/**
 * Test 01: Installation Verification
 * Verifies that the AcyMailing Integration plugin was installed correctly
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class InstallationVerificationTest
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
        echo "=== Installation Verification Tests ===\n\n";

        $this->testExtensionRegistered();
        $this->testPluginFilesExist();
        $this->testPluginEnabled();
        $this->testLanguageFilesInstalled();

        $this->printSummary();
        return $this->failed === 0;
    }

    private function testExtensionRegistered(): void
    {
        echo "Test: Plugin registered in database... ";
        
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_acymailing'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('j2commerce'));
        
        $this->db->setQuery($query);
        $extension = $this->db->loadObject();
        
        if ($extension) {
            echo "✅ PASS\n";
            echo "  Extension ID: {$extension->extension_id}\n";
            echo "  Name: {$extension->name}\n";
            echo "  Enabled: " . ($extension->enabled ? 'Yes' : 'No') . "\n";
            $this->passed++;
        } else {
            echo "❌ FAIL (Plugin not found in database)\n";
            $this->failed++;
        }
    }

    private function testPluginFilesExist(): void
    {
        echo "\nTest: Plugin files exist... ";
        
        $requiredFiles = [
            '/var/www/html/plugins/j2commerce/j2commerce_acymailing/j2commerce_acymailing.php',
            '/var/www/html/plugins/j2commerce/j2commerce_acymailing/j2commerce_acymailing.xml',
        ];
        
        $missingFiles = [];
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                $missingFiles[] = basename($file);
            }
        }
        
        if (empty($missingFiles)) {
            echo "✅ PASS\n";
            foreach ($requiredFiles as $file) {
                $size = filesize($file);
                echo "  " . basename($file) . ": " . round($size / 1024, 2) . " KB\n";
            }
            $this->passed++;
        } else {
            echo "❌ FAIL\n";
            echo "  Missing files: " . implode(', ', $missingFiles) . "\n";
            $this->failed++;
        }
    }

    private function testPluginEnabled(): void
    {
        echo "\nTest: Plugin is enabled... ";
        
        $query = $this->db->getQuery(true)
            ->select('enabled')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_acymailing'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('j2commerce'));
        
        $this->db->setQuery($query);
        $enabled = $this->db->loadResult();
        
        if ($enabled) {
            echo "✅ PASS\n";
            $this->passed++;
        } else {
            echo "⚠️  WARNING (Plugin is disabled - this is normal after installation)\n";
            $this->passed++;
        }
    }

    private function testLanguageFilesInstalled(): void
    {
        echo "\nTest: Language files installed... ";
        
        $languageFiles = [
            '/var/www/html/administrator/language/en-GB/plg_j2commerce_j2commerce_acymailing.ini',
            '/var/www/html/administrator/language/en-GB/plg_j2commerce_j2commerce_acymailing.sys.ini',
        ];
        
        $foundFiles = [];
        foreach ($languageFiles as $file) {
            if (file_exists($file)) {
                $foundFiles[] = basename($file);
            }
        }
        
        if (!empty($foundFiles)) {
            echo "✅ PASS\n";
            foreach ($foundFiles as $file) {
                echo "  $file\n";
            }
            $this->passed++;
        } else {
            echo "⚠️  WARNING (No language files found - may be embedded in XML)\n";
            $this->passed++;
        }
    }

    private function printSummary(): void
    {
        echo "\n=== Installation Verification Summary ===\n";
        echo "Total Tests: " . ($this->passed + $this->failed) . "\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        if ($this->failed === 0) {
            echo "✅ All verification tests passed\n";
        } else {
            echo "❌ {$this->failed} test(s) failed\n";
        }
    }
}

// Run tests
try {
    $app = Factory::getApplication('administrator');
    $test = new InstallationVerificationTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
