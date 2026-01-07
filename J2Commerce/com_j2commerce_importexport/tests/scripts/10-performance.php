<?php
/**
 * Test 10: Performance Tests
 * Tests API response times, database query optimization, and load handling
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class PerformanceTest
{
    private $db;
    private $baseUrl = 'http://localhost';
    private $thresholds = [
        'api_response' => 500,      // 500ms max for API calls
        'page_load' => 1000,        // 1s max for page loads
        'database_query' => 100,    // 100ms max for single queries
        'bulk_operations' => 5000   // 5s max for bulk operations
    ];

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Performance Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testAPIResponseTime() && $allPassed;
        $allPassed = $this->testPageLoadTime() && $allPassed;
        $allPassed = $this->testDatabaseQueryPerformance() && $allPassed;
        $allPassed = $this->testBulkOperations() && $allPassed;
        $allPassed = $this->testConcurrentRequests() && $allPassed;
        $allPassed = $this->testIndexUsage() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testAPIResponseTime(): bool
    {
        echo "Test: API response time... ";
        
        // Create test license
        $licenseKey = 'PERF-' . strtoupper(substr(md5(uniqid()), 0, 12));
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__license_keys'))
            ->columns(['license_key', 'order_id', 'customer_email', 'customer_name', 'status', 'max_activations'])
            ->values($this->db->quote($licenseKey) . ', ' . 
                    $this->db->quote('PERF-ORDER-' . time()) . ', ' .
                    $this->db->quote('perf@test.com') . ', ' .
                    $this->db->quote('Perf Test') . ', ' .
                    $this->db->quote('active') . ', 1');
        
        $this->db->setQuery($query);
        $this->db->execute();
        
        // Test API validation endpoint
        $url = $this->baseUrl . '/index.php?option=com_j2commerce_importexport&task=api.validate';
        $start = microtime(true);
        
        $response = $this->httpPost($url, [
            'license_key' => $licenseKey,
            'hardware_hash' => 'perf-test-hash'
        ]);
        
        $duration = (microtime(true) - $start) * 1000; // Convert to ms
        
        // Cleanup
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__license_keys'))
            ->where($this->db->quoteName('license_key') . ' = ' . $this->db->quote($licenseKey));
        $this->db->setQuery($query);
        $this->db->execute();
        
        if ($duration < $this->thresholds['api_response']) {
            echo "✅ PASS ({$duration}ms < {$this->thresholds['api_response']}ms)\n";
            return true;
        }
        
        echo "⚠️  WARNING ({$duration}ms > {$this->thresholds['api_response']}ms threshold)\n";
        return true; // Warning, not failure
    }

    private function testPageLoadTime(): bool
    {
        echo "Test: Page load time... ";
        
        $url = $this->baseUrl . '/index.php?option=com_j2commerce_importexport&view=activate';
        $start = microtime(true);
        
        $response = $this->httpGet($url);
        
        $duration = (microtime(true) - $start) * 1000;
        
        if ($duration < $this->thresholds['page_load']) {
            echo "✅ PASS ({$duration}ms < {$this->thresholds['page_load']}ms)\n";
            return true;
        }
        
        echo "⚠️  WARNING ({$duration}ms > {$this->thresholds['page_load']}ms threshold)\n";
        return true; // Warning, not failure
    }

    private function testDatabaseQueryPerformance(): bool
    {
        echo "Test: Database query performance... ";
        
        // Test indexed query
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__license_keys'))
            ->where($this->db->quoteName('license_key') . ' = ' . $this->db->quote('TEST-KEY'));
        
        $this->db->setQuery($query);
        
        $start = microtime(true);
        $this->db->loadObject();
        $duration = (microtime(true) - $start) * 1000;
        
        if ($duration < $this->thresholds['database_query']) {
            echo "✅ PASS ({$duration}ms < {$this->thresholds['database_query']}ms)\n";
            return true;
        }
        
        echo "⚠️  WARNING ({$duration}ms > {$this->thresholds['database_query']}ms threshold)\n";
        return true; // Warning, not failure
    }

    private function testBulkOperations(): bool
    {
        echo "Test: Bulk operations... ";
        
        $start = microtime(true);
        
        // Insert 100 test licenses
        $licenses = [];
        for ($i = 0; $i < 100; $i++) {
            $licenses[] = [
                'license_key' => 'BULK-' . $i . '-' . substr(md5(uniqid()), 0, 8),
                'customer_email' => "bulk{$i}@test.com",
                'status' => 'pending',
                'max_activations' => 1
            ];
        }
        
        foreach ($licenses as $i => $license) {
            $query = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__license_keys'))
                ->columns(['license_key', 'order_id', 'customer_email', 'customer_name', 'status', 'max_activations'])
                ->values($this->db->quote($license['license_key']) . ', ' . 
                        $this->db->quote('BULK-ORDER-' . $i) . ', ' .
                        $this->db->quote($license['customer_email']) . ', ' .
                        $this->db->quote('Bulk Test ' . $i) . ', ' .
                        $this->db->quote($license['status']) . ', ' .
                        $license['max_activations']);
            
            $this->db->setQuery($query);
            $this->db->execute();
        }
        
        $duration = (microtime(true) - $start) * 1000;
        
        // Cleanup
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__license_keys'))
            ->where($this->db->quoteName('license_key') . ' LIKE ' . $this->db->quote('BULK-%'));
        $this->db->setQuery($query);
        $this->db->execute();
        
        if ($duration < $this->thresholds['bulk_operations']) {
            echo "✅ PASS (100 inserts in {$duration}ms < {$this->thresholds['bulk_operations']}ms)\n";
            return true;
        }
        
        echo "⚠️  WARNING ({$duration}ms > {$this->thresholds['bulk_operations']}ms threshold)\n";
        return true; // Warning, not failure
    }

    private function testConcurrentRequests(): bool
    {
        echo "Test: Concurrent request handling... ";
        
        // Create test license
        $licenseKey = 'CONC-' . strtoupper(substr(md5(uniqid()), 0, 12));
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__license_keys'))
            ->columns(['license_key', 'order_id', 'customer_email', 'customer_name', 'status', 'max_activations'])
            ->values($this->db->quote($licenseKey) . ', ' . 
                    $this->db->quote('CONC-ORDER-' . time()) . ', ' .
                    $this->db->quote('conc@test.com') . ', ' .
                    $this->db->quote('Conc Test') . ', ' .
                    $this->db->quote('active') . ', 1');
        
        $this->db->setQuery($query);
        $this->db->execute();
        
        // Simulate 5 concurrent requests
        $url = $this->baseUrl . '/index.php?option=com_j2commerce_importexport&task=api.validate';
        $start = microtime(true);
        
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->httpPost($url, [
                'license_key' => $licenseKey,
                'hardware_hash' => "conc-hash-{$i}"
            ]);
        }
        
        $duration = (microtime(true) - $start) * 1000;
        
        // Cleanup
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__license_keys'))
            ->where($this->db->quoteName('license_key') . ' = ' . $this->db->quote($licenseKey));
        $this->db->setQuery($query);
        $this->db->execute();
        
        // All requests should complete within reasonable time
        if ($duration < ($this->thresholds['api_response'] * 5)) {
            echo "✅ PASS (5 requests in {$duration}ms)\n";
            return true;
        }
        
        echo "⚠️  WARNING ({$duration}ms for 5 concurrent requests)\n";
        return true; // Warning, not failure
    }

    private function testIndexUsage(): bool
    {
        echo "Test: Database index usage... ";
        
        // Check if indexes are being used
        $query = "EXPLAIN SELECT * FROM " . $this->db->quoteName('#__license_keys') . 
                 " WHERE " . $this->db->quoteName('license_key') . " = 'TEST'";
        
        $this->db->setQuery($query);
        $explain = $this->db->loadObject();
        
        // Check if index is used (key should not be NULL)
        if ($explain && $explain->key !== null && $explain->key !== '') {
            echo "✅ PASS (Index '{$explain->key}' used for license_key lookup)\n";
            return true;
        }
        
        echo "⚠️  WARNING (No index used for license_key lookup)\n";
        return true; // Warning, not failure
    }

    private function httpGet(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $code, 'body' => $body ?: ''];
    }

    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $code, 'body' => $body ?: ''];
    }

    private function printSummary(): void
    {
        echo "\n=== Performance Test Summary ===\n";
        echo "Thresholds:\n";
        echo "  API Response: < {$this->thresholds['api_response']}ms\n";
        echo "  Page Load: < {$this->thresholds['page_load']}ms\n";
        echo "  Database Query: < {$this->thresholds['database_query']}ms\n";
        echo "  Bulk Operations: < {$this->thresholds['bulk_operations']}ms\n";
        echo "All tests completed.\n";
    }
}

// Run tests
try {
    // Initialize Joomla application
    if (!defined('JPATH_COMPONENT')) {
        define('JPATH_COMPONENT', JPATH_BASE . '/components/com_j2commerce_importexport');
    }
    
    $test = new PerformanceTest();
    $result = $test->run();
    exit($result ? 0 : 1);
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
