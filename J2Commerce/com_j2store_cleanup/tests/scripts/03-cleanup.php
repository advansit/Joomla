#!/usr/bin/env php
<?php
/**
 * Test 03: Cleanup
 * Tests extension removal functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class CleanupTest
{
    private $db;
    private $results = [];
    
    public function __construct()
    {
        $this->db = Factory::getDbo();
    }
    
    public function run()
    {
        echo "=== Cleanup Test ===\n\n";
        
        $this->testGetJ2StoreExtensions();
        $this->testRemoveJ2StoreExtension();
        $this->testVerifyRemoval();
        $this->testJ2CommerceNotRemoved();
        
        $this->printResults();
        
        return $this->allTestsPassed();
    }
    
    private function testGetJ2StoreExtensions()
    {
        $query = $this->db->getQuery(true)
            ->select('extension_id, name, element')
            ->from($this->db->quoteName('#__extensions'))
            ->where("element LIKE '%j2store%'")
            ->where("element != 'com_j2store_cleanup'"); // Don't remove cleanup component itself
        
        $this->db->setQuery($query);
        $extensions = $this->db->loadObjectList();
        
        if (!empty($extensions)) {
            $this->pass("Found " . count($extensions) . " J2Store extensions to test");
            foreach ($extensions as $ext) {
                echo "  - {$ext->name} (ID: {$ext->extension_id})\n";
            }
        } else {
            echo "  ⚠️  No J2Store extensions found to remove\n";
        }
    }
    
    private function testRemoveJ2StoreExtension()
    {
        // Find a test J2Store extension to remove
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where("element LIKE '%j2store_test%'")
            ->setLimit(1);
        
        $this->db->setQuery($query);
        $extensionId = $this->db->loadResult();
        
        if ($extensionId) {
            try {
                $query = $this->db->getQuery(true)
                    ->delete($this->db->quoteName('#__extensions'))
                    ->where($this->db->quoteName('extension_id') . ' = ' . (int)$extensionId);
                
                $this->db->setQuery($query);
                $this->db->execute();
                
                $this->pass("Successfully removed test extension (ID: $extensionId)");
            } catch (Exception $e) {
                $this->fail("Failed to remove extension: " . $e->getMessage());
            }
        } else {
            echo "  ⚠️  No test extension found to remove\n";
        }
    }
    
    private function testVerifyRemoval()
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__extensions'))
            ->where("element LIKE '%j2store_test%'");
        
        $this->db->setQuery($query);
        $count = $this->db->loadResult();
        
        if ($count == 0) {
            $this->pass("Test extension successfully removed from database");
        } else {
            $this->fail("Test extension still exists in database");
        }
    }
    
    private function testJ2CommerceNotRemoved()
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__extensions'))
            ->where("element LIKE '%j2commerce%'");
        
        $this->db->setQuery($query);
        $count = $this->db->loadResult();
        
        if ($count > 0) {
            $this->pass("J2Commerce extensions preserved (not removed)");
            echo "  Found $count J2Commerce extensions still installed\n";
        } else {
            echo "  ⚠️  No J2Commerce extensions found\n";
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
    $test = new CleanupTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
