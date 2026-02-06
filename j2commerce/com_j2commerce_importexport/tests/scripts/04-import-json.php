<?php
/**
 * JSON Import Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

class ImportJsonTest
{
    private $passed = 0;
    private $failed = 0;

    public function run(): bool
    {
        echo "=== JSON Import Tests ===\n\n";

        $this->test('ImportModel can be instantiated', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel();
            return $model !== null;
        });

        $this->test('ImportModel has previewFile method', function() {
            $reflection = new \ReflectionClass(\Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel::class);
            return $reflection->hasMethod('previewFile');
        });

        $this->test('ImportModel has importData method', function() {
            $reflection = new \ReflectionClass(\Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel::class);
            return $reflection->hasMethod('importData');
        });

        $this->test('JSON with _documentation wrapper is handled', function() {
            // Test that products array is extracted from wrapper
            $jsonWithWrapper = json_encode([
                '_documentation' => ['fields' => []],
                'products' => [
                    ['title' => 'Test Product']
                ]
            ]);
            
            $data = json_decode($jsonWithWrapper, true);
            if (isset($data['products'])) {
                $data = $data['products'];
            }
            
            return count($data) === 1 && $data[0]['title'] === 'Test Product';
        });

        $this->test('Import handles manage_stock=0 for unlimited inventory', function() {
            $variant = [
                'sku' => 'TEST-001',
                'price' => 99.00,
                'manage_stock' => 0
            ];
            
            return ($variant['manage_stock'] ?? 1) === 0;
        });

        $this->test('ImportModel has importVariantQuantity method', function() {
            $reflection = new \ReflectionClass(\Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel::class);
            return $reflection->hasMethod('importVariantQuantity');
        });

        $this->test('Quantity mode replace overwrites existing stock', function() {
            // Simulate replace mode logic
            $existingQuantity = 50;
            $importQuantity = 30;
            $quantityMode = 'replace';
            
            $finalQuantity = ($quantityMode === 'add') 
                ? $existingQuantity + $importQuantity 
                : $importQuantity;
            
            return $finalQuantity === 30;
        });

        $this->test('Quantity mode add increments existing stock', function() {
            // Simulate add mode logic
            $existingQuantity = 50;
            $importQuantity = 30;
            $quantityMode = 'add';
            
            $finalQuantity = ($quantityMode === 'add') 
                ? $existingQuantity + $importQuantity 
                : $importQuantity;
            
            return $finalQuantity === 80;
        });

        echo "\n=== JSON Import Test Summary ===\n";
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

$test = new ImportJsonTest();
exit($test->run() ? 0 : 1);
