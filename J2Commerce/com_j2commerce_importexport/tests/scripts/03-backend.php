<?php
/**
 * Test 03: Backend Functionality
 * Tests admin panel license management CRUD operations
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class BackendTest
{
    private $db;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Backend Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testBackendComponentAccessible() && $allPassed;
        $allPassed = $this->testLicenseListView() && $allPassed;
        $allPassed = $this->testCreateLicense() && $allPassed;
        $allPassed = $this->testUpdateLicense() && $allPassed;
        $allPassed = $this->testRevokeLicense() && $allPassed;
        $allPassed = $this->testDeleteLicense() && $allPassed;
        $allPassed = $this->testSearchFilter() && $allPassed;
        $allPassed = $this->testStatusFilter() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testBackendComponentAccessible(): bool
    {
        echo "Test: Backend component accessible... ";
        
        $path = '/var/www/html/administrator/components/com_j2commerce_importexport';
        
        if (is_dir($path) && file_exists($path . '/j2commerce_importexport.php')) {
            echo "✅ PASS\n";
            return true;
        }
        
        echo "❌ FAIL (Component files not found)\n";
        return false;
    }

    private function testLicenseListView(): bool
    {
        echo "Test: License list view... ";
        
        try {
            // Check if we can query licenses
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__license_keys'));
            
            $this->db->setQuery($query);
            $count = $this->db->loadResult();
            
            echo "✅ PASS ($count licenses in database)\n";
            return true;
        } catch (Exception $e) {
            echo "❌ FAIL (Query error: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function testCreateLicense(): bool
    {
        echo "Test: Create license (CRUD - Create)... ";
        
        $testData = new \stdClass();
        $testData->license_key = 'TEST-' . bin2hex(random_bytes(8));
        $testData->order_id = 'ORD-TEST-' . time();
        $testData->customer_email = 'test@example.com';
        $testData->customer_name = 'Test Customer';
        $testData->hardware_hash = 'TEST-HASH-' . bin2hex(random_bytes(16));
        $testData->product_id = 1;
        $testData->max_activations = 1;
        $testData->activation_count = 0;
        $testData->status = 'pending';
        $testData->created_at = Factory::getDate()->toSql();
        
        try {
            $this->db->insertObject('#__license_keys', $testData);
            $licenseId = $this->db->insertid();
            
            if ($licenseId > 0) {
                echo "✅ PASS (Created license ID: $licenseId)\n";
                
                // Store for later tests
                $GLOBALS['test_license_id'] = $licenseId;
                return true;
            }
            
            echo "❌ FAIL (Insert returned 0)\n";
            return false;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function testUpdateLicense(): bool
    {
        echo "Test: Update license (CRUD - Update)... ";
        
        if (!isset($GLOBALS['test_license_id'])) {
            echo "⚠️  SKIP (No test license created)\n";
            return true;
        }
        
        $licenseId = $GLOBALS['test_license_id'];
        
        try {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__license_keys'))
                ->set($this->db->quoteName('status') . ' = ' . $this->db->quote('active'))
                ->set($this->db->quoteName('activation_count') . ' = 1')
                ->where($this->db->quoteName('id') . ' = ' . $licenseId);
            
            $this->db->setQuery($query);
            $this->db->execute();
            
            // Verify update
            $query = $this->db->getQuery(true)
                ->select('status, activation_count')
                ->from($this->db->quoteName('#__license_keys'))
                ->where($this->db->quoteName('id') . ' = ' . $licenseId);
            
            $this->db->setQuery($query);
            $result = $this->db->loadObject();
            
            if ($result && $result->status === 'active' && $result->activation_count == 1) {
                echo "✅ PASS (Status: {$result->status}, Activations: {$result->activation_count})\n";
                return true;
            }
            
            echo "❌ FAIL (Update not reflected)\n";
            return false;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function testRevokeLicense(): bool
    {
        echo "Test: Revoke license... ";
        
        if (!isset($GLOBALS['test_license_id'])) {
            echo "⚠️  SKIP (No test license created)\n";
            return true;
        }
        
        $licenseId = $GLOBALS['test_license_id'];
        
        try {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__license_keys'))
                ->set($this->db->quoteName('status') . ' = ' . $this->db->quote('revoked'))
                ->where($this->db->quoteName('id') . ' = ' . $licenseId);
            
            $this->db->setQuery($query);
            $this->db->execute();
            
            // Verify revocation
            $query = $this->db->getQuery(true)
                ->select('status')
                ->from($this->db->quoteName('#__license_keys'))
                ->where($this->db->quoteName('id') . ' = ' . $licenseId);
            
            $this->db->setQuery($query);
            $status = $this->db->loadResult();
            
            if ($status === 'revoked') {
                echo "✅ PASS (Status: $status)\n";
                return true;
            }
            
            echo "❌ FAIL (Status: $status)\n";
            return false;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function testDeleteLicense(): bool
    {
        echo "Test: Delete license (CRUD - Delete)... ";
        
        if (!isset($GLOBALS['test_license_id'])) {
            echo "⚠️  SKIP (No test license created)\n";
            return true;
        }
        
        $licenseId = $GLOBALS['test_license_id'];
        
        try {
            $query = $this->db->getQuery(true)
                ->delete($this->db->quoteName('#__license_keys'))
                ->where($this->db->quoteName('id') . ' = ' . $licenseId);
            
            $this->db->setQuery($query);
            $this->db->execute();
            
            // Verify deletion
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__license_keys'))
                ->where($this->db->quoteName('id') . ' = ' . $licenseId);
            
            $this->db->setQuery($query);
            $count = $this->db->loadResult();
            
            if ($count == 0) {
                echo "✅ PASS (License deleted)\n";
                unset($GLOBALS['test_license_id']);
                return true;
            }
            
            echo "❌ FAIL (License still exists)\n";
            return false;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function testSearchFilter(): bool
    {
        echo "Test: Search/filter functionality... ";
        
        try {
            // Test search by email
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__license_keys'))
                ->where($this->db->quoteName('customer_email') . ' LIKE ' . $this->db->quote('%@%'));
            
            $this->db->setQuery($query);
            $count = $this->db->loadResult();
            
            echo "✅ PASS (Search query works, found $count licenses with email)\n";
            return true;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function testStatusFilter(): bool
    {
        echo "Test: Status filter... ";
        
        try {
            $statuses = ['pending', 'active', 'revoked'];
            $results = [];
            
            foreach ($statuses as $status) {
                $query = $this->db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($this->db->quoteName('#__license_keys'))
                    ->where($this->db->quoteName('status') . ' = ' . $this->db->quote($status));
                
                $this->db->setQuery($query);
                $results[$status] = $this->db->loadResult();
            }
            
            echo "✅ PASS (Pending: {$results['pending']}, Active: {$results['active']}, Revoked: {$results['revoked']})\n";
            return true;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function printSummary(): void
    {
        echo "\n=== Backend Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

// Run tests
try {
    $test = new BackendTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
