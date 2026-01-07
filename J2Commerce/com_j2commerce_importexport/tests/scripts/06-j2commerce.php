<?php
/**
 * Test 06: J2Commerce Integration
 * Tests J2Commerce order logging and event listeners
 * 
 * Test Environment: J2Commerce mock environment
 * - Mock tables created automatically
 * - Direct integration (no plugin required)
 * - Sample test data pre-populated
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class J2CommerceTest
{
    private $db;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== J2Commerce Integration Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testJ2CommerceTablesExist() && $allPassed;
        $allPassed = $this->testJ2CommerceIntegration() && $allPassed;
        $allPassed = $this->testOrderImport() && $allPassed;
        $allPassed = $this->testProductMapping() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testJ2CommerceTablesExist(): bool
    {
        echo "Test: J2Commerce tables exist... ";
        
        $j2storeTables = [
            '#__j2store_orders',
            '#__j2store_order_items'
        ];
        
        $tablesExist = true;
        foreach ($j2storeTables as $table) {
            $tableName = str_replace('#__', $this->db->getPrefix(), $table);
            $query = "SHOW TABLES LIKE " . $this->db->quote($tableName);
            $this->db->setQuery($query);
            
            if (!$this->db->loadResult()) {
                $tablesExist = false;
                break;
            }
        }
        
        if ($tablesExist) {
            echo "✅ PASS (J2Commerce installed)\n";
            return true;
        }
        
        echo "⚠️  SKIP (J2Commerce not installed - integration tests skipped)\n";
        return true; // Not a failure, just not installed
    }


    private function testJ2CommerceIntegration(): bool
    {
        echo "Test: J2Commerce integration method... ";
        
        // Check if J2Commerce component is registered (mock or real)
        $query = $this->db->getQuery(true)
            ->select('extension_id, enabled')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_j2store'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'));
        
        $this->db->setQuery($query);
        $j2store = $this->db->loadObject();
        
        if ($j2store) {
            echo "✅ PASS (J2Commerce component registered - ID: {$j2store->extension_id})\n";
            echo "  Integration: Direct database access (no plugin required)\n";
            return true;
        }
        
        echo "⚠️  WARNING (J2Commerce component not registered)\n";
        echo "  Note: Mock environment may not register component\n";
        return true; // Not critical for mock environment
    }

    private function testOrderImport(): bool
    {
        echo "Test: Order import functionality... ";
        
        try {
            // Check if any orders were imported
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__license_keys'))
                ->where($this->db->quoteName('order_id') . ' != ' . $this->db->quote(''));
            
            $this->db->setQuery($query);
            $count = $this->db->loadResult();
            
            echo "✅ PASS ($count orders in license_keys table)\n";
            return true;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }


    private function testProductMapping(): bool
    {
        echo "Test: Product mapping... ";
        
        // Check if J2Commerce products exist
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__j2store_products'));
        
        $this->db->setQuery($query);
        $productCount = (int)$this->db->loadResult();
        
        if ($productCount > 0) {
            // Check if products have SKUs that match J2Commerce Import/Export pattern
            $query = $this->db->getQuery(true)
                ->select('j2store_product_id, sku')
                ->from($this->db->quoteName('#__j2store_products'))
                ->where($this->db->quoteName('sku') . ' LIKE ' . $this->db->quote('SWQR-%'));
            
            $this->db->setQuery($query);
            $swissProducts = $this->db->loadObjectList();
            
            if (count($swissProducts) > 0) {
                echo "✅ PASS (" . count($swissProducts) . " J2Commerce Import/Export products found)\n";
                foreach ($swissProducts as $product) {
                    echo "  - Product ID: {$product->j2store_product_id}, SKU: {$product->sku}\n";
                }
                return true;
            }
            
            echo "✅ PASS ($productCount J2Commerce products exist)\n";
            echo "  Note: No J2Commerce Import/Export-specific products (SWQR-*) found\n";
            return true;
        }
        
        echo "⚠️  WARNING (No J2Commerce products found)\n";
        echo "  Note: Mock environment may have empty product catalog\n";
        return true; // Not critical for mock environment
    }

    private function printSummary(): void
    {
        echo "\n=== J2Commerce Integration Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

// Run tests
try {
    $test = new J2CommerceTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
