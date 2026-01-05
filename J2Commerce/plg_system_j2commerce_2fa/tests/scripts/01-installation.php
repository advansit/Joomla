#!/usr/bin/env php
<?php
/**
 * Test 01: Installation
 * Tests J2Store Cleanup component installation
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class InstallationTest
{
    private $db;
    private $results = [];
    
    public function __construct()
    {
        $this->db = Factory::getDbo();
    }
    
    public function run()
    {
        echo "=== Installation Test ===\n\n";
        
        $this->testPackageExists();
        $this->testPluginRegistered();
        $this->testAdminFiles();
        $this->testLanguageFiles();
        
        $this->printResults();
        
        return $this->allTestsPassed();
    }
    
    private function testPackageExists()
    {
        $packagePath = '/tmp/extension.zip';
        
        if (file_exists($packagePath)) {
            $this->pass("Package exists: $packagePath");
            $size = filesize($packagePath);
            echo "  Package size: " . round($size / 1024, 2) . " KB\n";
        } else {
            $this->fail("Package not found: $packagePath");
        }
    }
    
    private function testPluginRegistered()
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_2fa'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'));
        
        $this->db->setQuery($query);
        $extension = $this->db->loadObject();
        
        if ($extension) {
            $this->pass("Plugin registered in database");
            echo "  Extension ID: {$extension->extension_id}\n";
            echo "  Name: {$extension->name}\n";
            echo "  Enabled: " . ($extension->enabled ? 'Yes' : 'No') . "\n";
            
            if (!$extension->enabled) {
                $this->fail("Plugin is not enabled");
            }
        } else {
            $this->fail("Plugin not found in extensions table");
        }
    }
    
    private function testAdminFiles()
    {
        $files = [
            '/var/www/html/administrator/components/j2commerce_2fa/j2store_cleanup.php',
        ];
        
        $allExist = true;
        foreach ($files as $file) {
            if (file_exists($file)) {
                echo "  ✓ $file\n";
            } else {
                echo "  ✗ $file (missing)\n";
                $allExist = false;
            }
        }
        
        if ($allExist) {
            $this->pass("All admin files exist");
        } else {
            $this->fail("Some admin files are missing");
        }
    }
    
    private function testLanguageFiles()
    {
        $languages = ['en-GB', 'de-DE', 'fr-FR'];
        $found = 0;
        
        foreach ($languages as $lang) {
            $file = "/var/www/html/administrator/language/$lang/j2commerce_2fa.ini";
            if (file_exists($file)) {
                $found++;
                echo "  ✓ Language: $lang\n";
            }
        }
        
        if ($found > 0) {
            $this->pass("Language files found: $found");
        } else {
            echo "  ⚠️  No language files found (might be expected)\n";
        }
    }
    
    private function pass($message)
    {
        $this->results[] = ['status' => 'PASS', 'message' => $message];
        echo "✅ PASS: $message\n";
    }
    
    private function fail($message)
    {
        $this->results[] = ['status' => 'FAIL', 'message' => $message];
        echo "❌ FAIL: $message\n";
    }
    
    private function printResults()
    {
        echo "\n=== Test Results ===\n";
        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'PASS'));
        $failed = count(array_filter($this->results, fn($r) => $r['status'] === 'FAIL'));
        
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        echo "Total: " . count($this->results) . "\n";
    }
    
    private function allTestsPassed()
    {
        foreach ($this->results as $result) {
            if ($result['status'] === 'FAIL') {
                return false;
            }
        }
        return true;
    }
}

try {
    $app = Factory::getApplication('site');
    $test = new InstallationTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
