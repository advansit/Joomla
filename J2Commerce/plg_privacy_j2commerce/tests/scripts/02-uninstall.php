#!/usr/bin/env php
<?php
/**
 * Test 04: Uninstall
 * Tests component uninstallation
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

class UninstallTest
{
    private $db;
    private $results = [];
    
    public function __construct()
    {
        $this->db = Factory::getDbo();
    }
    
    public function run()
    {
        echo "=== Uninstall Test ===\n\n";
        
        $this->testGetExtensionId();
        $this->testUninstallPlugin();
        $this->testVerifyUninstall();
        
        $this->printResults();
        
        return $this->allTestsPassed();
    }
    
    private function testGetExtensionId()
    {
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('privacy'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'));
        
        $this->db->setQuery($query);
        $extensionId = $this->db->loadResult();
        
        if ($extensionId) {
            $this->pass("Found component extension ID: $extensionId");
        } else {
            $this->fail("Plugin not found in database");
        }
    }
    
    private function testUninstallPlugin()
    {
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('privacy'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'));
        
        $this->db->setQuery($query);
        $extensionId = $this->db->loadResult();
        
        if (!$extensionId) {
            $this->fail("Cannot uninstall: component not found");
            return;
        }
        
        try {
            $installer = Installer::getInstance();
            
            if ($installer->uninstall('plugin', $extensionId)) {
                $this->pass("Plugin uninstalled successfully");
            } else {
                $this->fail("Plugin uninstallation failed");
                $errors = $installer->getErrors();
                foreach ($errors as $error) {
                    echo "  Error: $error\n";
                }
            }
        } catch (Exception $e) {
            $this->fail("Uninstallation error: " . $e->getMessage());
        }
    }
    
    private function testVerifyUninstall()
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('privacy'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'));
        
        $this->db->setQuery($query);
        $count = $this->db->loadResult();
        
        if ($count == 0) {
            $this->pass("Plugin removed from database");
        } else {
            $this->fail("Plugin still exists in database");
        }
        
        // Check if admin files are removed
        $adminFile = '/var/www/html/administrator/components/j2commerce/j2store_cleanup.php';
        if (!file_exists($adminFile)) {
            $this->pass("Admin files removed");
        } else {
            echo "  ⚠️  Admin files still exist (might be expected)\n";
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
    $test = new UninstallTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
