<?php
/**
 * Export/Import API Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class ApiTest
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
        echo "=== Export/Import API Tests ===\n\n";

        // Test ExportModel methods
        $this->test('ExportModel::exportProducts returns array', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $result = $model->exportData('products');
            return is_array($result);
        });

        $this->test('ExportModel::exportProductsFull returns array with expected keys', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $result = $model->exportData('products_full');
            if (!is_array($result)) return false;
            if (empty($result)) return true; // Empty is OK if no products
            $first = $result[0];
            // Check for expected keys from full export
            $expectedKeys = ['j2store_product_id', 'title', 'variants'];
            foreach ($expectedKeys as $key) {
                if (!array_key_exists($key, $first)) return false;
            }
            return true;
        });

        $this->test('ExportModel::exportCategories returns array', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $result = $model->exportData('categories');
            return is_array($result);
        });

        $this->test('ExportModel::exportVariants returns array', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $result = $model->exportData('variants');
            return is_array($result);
        });

        $this->test('ExportModel::exportPrices returns array', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $result = $model->exportData('prices');
            return is_array($result);
        });

        $this->test('ExportModel throws exception for invalid type', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            try {
                $model->exportData('invalid_type');
                return false;
            } catch (\Exception $e) {
                return true;
            }
        });

        // Test ImportModel
        $this->test('ImportModel can be instantiated', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel();
            return $model !== null;
        });

        $this->test('ImportModel::previewFile handles JSON', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel();
            $testFile = '/tmp/test_import.json';
            file_put_contents($testFile, json_encode([
                ['title' => 'Test Product', 'sku' => 'TEST-001']
            ]));
            $result = $model->previewFile($testFile, 10);
            unlink($testFile);
            return isset($result['headers']) && isset($result['rows']) && isset($result['total']);
        });

        echo "\n=== Export/Import API Test Summary ===\n";
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

$test = new ApiTest();
exit($test->run() ? 0 : 1);
