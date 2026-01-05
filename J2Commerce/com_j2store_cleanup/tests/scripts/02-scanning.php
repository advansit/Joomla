#!/usr/bin/env php
<?php
/**
 * Test 02: Scanning
 * Tests extension scanning functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class ScanningTest
{
    private $db;
    private $results = [];
    
    public function __construct()
    {
        $this->db = Factory::getDbo();
    }
    
    public function run()
    {
        echo "=== Scanning Test ===\n\n";
        
        $this->testCreateMockExtensions();
        $this->testScanForJ2StoreExtensions();
        $this->testScanForJ2CommerceExtensions();
        $this->testExtensionDetails();
        
        $this->printResults();
        
        return $this->allTestsPassed();
    }
    
    private function testCreateMockExtensions()
    {
        // Create mock J2Store extensions for testing
        $mockExtensions = [
            [
                'name' => 'J2Store - Test Plugin',
                'type' => 'plugin',
                'element' => 'j2store_test',
                'folder' => 'j2store',
                'enabled' => 1,
            ],
            [
                'name' => 'J2Store - Another Plugin',
                'type' => 'plugin',
                'element' => 'j2store_another',
                'folder' => 'j2store',
                'enabled' => 0,
            ],
        ];
        
        $created = 0;
        foreach ($mockExtensions as $ext) {
            try {
                $query = $this->db->getQuery(true)
                    ->insert($this->db->quoteName('#__extensions'))
                    ->columns($this->db->quoteName(array_keys($ext)))
                    ->values(implode(',', array_map([$this->db, 'quote'], $ext)));
                
                $this->db->setQuery($query);
                $this->db->execute();
                $created++;
            } catch (Exception $e) {
                // Extension might already exist
            }
        }
        
        if ($created > 0) {
            $this->pass("Created $created mock J2Store extensions");
        } else {
            echo "  ⚠️  No mock extensions created (might already exist)\n";
        }
    }
    
    private function testScanForJ2StoreExtensions()
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__extensions'))
            ->where("element LIKE '%j2store%'");
        
        $this->db->setQuery($query);
        $count = $this->db->loadResult();
        
        if ($count > 0) {
            $this->pass("Found $count J2Store extensions");
        } else {
            echo "  ⚠️  No J2Store extensions found\n";
        }
    }
    
    private function testScanForJ2CommerceExtensions()
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__extensions'))
            ->where("element LIKE '%j2commerce%'");
        
        $this->db->setQuery($query);
        $count = $this->db->loadResult();
        
        if ($count > 0) {
            $this->pass("Found $count J2Commerce extensions");
            echo "  (These should NOT be removed)\n";
        } else {
            echo "  ⚠️  No J2Commerce extensions found\n";
        }
    }
    
    private function testExtensionDetails()
    {
        $query = $this->db->getQuery(true)
            ->select('extension_id, name, type, element, enabled')
            ->from($this->db->quoteName('#__extensions'))
            ->where("element LIKE '%j2store%' OR element LIKE '%j2commerce%'")
            ->setLimit(5);
        
        $this->db->setQuery($query);
        $extensions = $this->db->loadObjectList();
        
        if (!empty($extensions)) {
            $this->pass("Retrieved extension details");
            echo "\n  Sample extensions:\n";
            foreach ($extensions as $ext) {
                echo "  - {$ext->name} ({$ext->type})\n";
                echo "    Element: {$ext->element}\n";
                echo "    Enabled: " . ($ext->enabled ? 'Yes' : 'No') . "\n";
            }
        } else {
            $this->fail("No extensions found to display details");
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
    $test = new ScanningTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
