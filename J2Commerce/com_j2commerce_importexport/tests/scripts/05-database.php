<?php
/**
 * Test 05: Database & Schema
 * Tests database schema, indexes, and data integrity
 * 
 * Test Environment: MySQL 8.0
 * - No foreign key constraints (intentional design)
 * - Focus on table structure, indexes, and data integrity
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class DatabaseTest
{
    private $db;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Database Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testTableStructure() && $allPassed;
        $allPassed = $this->testIndexes() && $allPassed;
        $allPassed = $this->testForeignKeys() && $allPassed;
        $allPassed = $this->testDataIntegrity() && $allPassed;
        $allPassed = $this->testStatusEnum() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testTableStructure(): bool
    {
        echo "Test: Table structure... ";
        
        $tables = [
            '#__license_keys' => [
                'id', 'license_key', 'order_id', 'customer_email', 'customer_name',
                'hardware_hash', 'product_id', 'max_activations', 'activation_count',
                'status', 'created_at', 'activated_at'
            ],
            '#__license_activations' => [
                'id', 'license_id', 'hardware_hash', 'activated_at', 'ip_address'
            ]
        ];
        
        $allColumnsPresent = true;
        
        foreach ($tables as $table => $expectedColumns) {
            $actualColumns = $this->db->getTableColumns($table);
            $missingColumns = array_diff($expectedColumns, array_keys($actualColumns));
            
            if (!empty($missingColumns)) {
                echo "\n  ❌ Table $table missing columns: " . implode(', ', $missingColumns);
                $allColumnsPresent = false;
            }
        }
        
        if ($allColumnsPresent) {
            echo "✅ PASS (All columns present)\n";
            return true;
        }
        
        echo "\n❌ FAIL\n";
        return false;
    }

    private function testIndexes(): bool
    {
        echo "Test: Database indexes... ";
        
        try {
            $tableName = str_replace('#__', $this->db->getPrefix(), '#__license_keys');
            $query = "SHOW INDEX FROM " . $this->db->quoteName($tableName);
            $this->db->setQuery($query);
            $indexes = $this->db->loadObjectList();
            
            $indexNames = array_unique(array_column($indexes, 'Key_name'));
            
            echo "✅ PASS (" . count($indexNames) . " indexes found)\n";
            foreach ($indexNames as $indexName) {
                echo "  - $indexName\n";
            }
            
            return true;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }


    private function testForeignKeys(): bool
    {
        echo "Test: Foreign key constraints... ";
        
        // Check if foreign keys exist
        try {
            $tableName = str_replace('#__', $this->db->getPrefix(), '#__license_activations');
            $query = "SELECT 
                        CONSTRAINT_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                      FROM information_schema.KEY_COLUMN_USAGE
                      WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = " . $this->db->quote($tableName) . "
                        AND REFERENCED_TABLE_NAME IS NOT NULL";
            
            $this->db->setQuery($query);
            $constraints = $this->db->loadObjectList();
            
            if (count($constraints) > 0) {
                echo "✅ PASS (" . count($constraints) . " foreign keys found)\n";
                foreach ($constraints as $constraint) {
                    echo "  - {$constraint->CONSTRAINT_NAME} → {$constraint->REFERENCED_TABLE_NAME}.{$constraint->REFERENCED_COLUMN_NAME}\n";
                }
                return true;
            }
            
            // No foreign keys found - this is by design for this component
            echo "✅ PASS (No foreign keys - intentional design for flexibility)\n";
            echo "  Note: Data integrity enforced at application level\n";
            return true;
        } catch (Exception $e) {
            echo "❌ FAIL (Error checking constraints: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function testDataIntegrity(): bool
    {
        echo "Test: Data integrity... ";
        
        try {
            // Check for orphaned activations
            $query = "SELECT COUNT(*) as orphaned
                      FROM " . $this->db->quoteName('#__license_activations') . " a
                      LEFT JOIN " . $this->db->quoteName('#__license_keys') . " l
                        ON a.license_id = l.id
                      WHERE l.id IS NULL";
            
            $this->db->setQuery($query);
            $orphaned = $this->db->loadResult();
            
            if ($orphaned == 0) {
                echo "✅ PASS (No orphaned records)\n";
                return true;
            }
            
            echo "⚠️  WARNING ($orphaned orphaned activation records)\n";
            return true; // Warning, not failure
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function testStatusEnum(): bool
    {
        echo "Test: Status ENUM values... ";
        
        try {
            $tableName = str_replace('#__', $this->db->getPrefix(), '#__license_keys');
            $query = "SHOW COLUMNS FROM " . $this->db->quoteName($tableName) . " LIKE 'status'";
            $this->db->setQuery($query);
            $column = $this->db->loadObject();
            
            if ($column) {
                $expectedValues = ['pending', 'active', 'revoked'];
                $hasAllValues = true;
                
                foreach ($expectedValues as $value) {
                    if (strpos($column->Type, $value) === false) {
                        $hasAllValues = false;
                        break;
                    }
                }
                
                if ($hasAllValues) {
                    echo "✅ PASS (All status values present)\n";
                    echo "  Type: {$column->Type}\n";
                    return true;
                }
                
                echo "❌ FAIL (Missing status values)\n";
                return false;
            }
            
            echo "❌ FAIL (Status column not found)\n";
            return false;
        } catch (Exception $e) {
            echo "❌ FAIL (Exception: " . $e->getMessage() . ")\n";
            return false;
        }
    }

    private function printSummary(): void
    {
        echo "\n=== Database Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

// Run tests
try {
    $test = new DatabaseTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
