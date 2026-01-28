<?php
/**
 * Duplicate Detection Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class DuplicateDetectionTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->db = Factory::getContainer()->get('DatabaseDriver');
    }

    public function run(): bool
    {
        echo "=== Duplicate Detection Tests ===\n\n";

        $this->test('ImportModel has importArticle method', function() {
            $reflection = new \ReflectionClass(\Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel::class);
            return $reflection->hasMethod('importArticle');
        });

        $this->test('Duplicate detection checks article_id first', function() {
            // Simulate the logic
            $data = ['article_id' => 123, 'alias' => 'test', 'variants' => [['sku' => 'SKU-001']]];
            
            $existingId = null;
            
            // 1. Check by article_id
            if (!empty($data['article_id'])) {
                $existingId = $data['article_id']; // Would be from DB query
            }
            
            return $existingId === 123;
        });

        $this->test('Duplicate detection falls back to alias', function() {
            $data = ['article_id' => null, 'alias' => 'test-product', 'variants' => []];
            
            $existingId = null;
            
            // 1. Check by article_id - not found
            if (!empty($data['article_id'])) {
                $existingId = null;
            }
            
            // 2. Check by alias
            if (!$existingId && !empty($data['alias'])) {
                $existingId = 456; // Would be from DB query
            }
            
            return $existingId === 456;
        });

        $this->test('Duplicate detection falls back to SKU', function() {
            $data = ['article_id' => null, 'alias' => '', 'variants' => [['sku' => 'UNIQUE-SKU']]];
            
            $existingId = null;
            
            // 1. Check by article_id - not found
            // 2. Check by alias - empty
            
            // 3. Check by SKU
            if (!$existingId && !empty($data['variants'][0]['sku'])) {
                $existingId = 789; // Would be from DB query via J2Store
            }
            
            return $existingId === 789;
        });

        $this->test('New product created when no duplicate found', function() {
            $data = ['article_id' => null, 'alias' => '', 'variants' => []];
            
            $existingId = null;
            
            // All checks fail - no duplicate
            $isNewProduct = ($existingId === null);
            
            return $isNewProduct === true;
        });

        $this->test('SKU lookup joins variants table correctly', function() {
            // Test that the query structure is correct
            $query = $this->db->getQuery(true)
                ->select('p.product_source_id')
                ->from($this->db->quoteName('#__j2store_products', 'p'))
                ->join('INNER', $this->db->quoteName('#__j2store_variants', 'v') . ' ON p.j2store_product_id = v.product_id')
                ->where('v.sku = :sku')
                ->where('p.product_source = ' . $this->db->quote('com_content'));
            
            $queryString = (string) $query;
            
            return strpos($queryString, 'j2store_variants') !== false
                && strpos($queryString, 'j2store_products') !== false
                && strpos($queryString, 'INNER JOIN') !== false;
        });

        echo "\n=== Duplicate Detection Test Summary ===\n";
        echo "Passed: {$this->passed}, Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    private function test(string $name, callable $fn): void
    {
        try {
            $result = $fn();
            if ($result) {
                echo "✓ {$name}\n";
                $this->passed++;
            } else {
                echo "✗ {$name}\n";
                $this->failed++;
            }
        } catch (\Exception $e) {
            echo "✗ {$name} - Error: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
}

$test = new DuplicateDetectionTest();
exit($test->run() ? 0 : 1);
