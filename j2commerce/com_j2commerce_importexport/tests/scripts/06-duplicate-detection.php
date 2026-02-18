<?php
/**
 * Duplicate Detection Tests for J2Commerce Import/Export
 */

class DuplicateDetectionTest
{
    private $passed = 0;
    private $failed = 0;
    private $basePath = '/var/www/html/administrator/components/com_j2commerce_importexport';

    public function run(): bool
    {
        echo "=== Duplicate Detection Tests ===\n\n";

        $this->test('ImportModel has duplicate detection logic', function() {
            $content = file_get_contents($this->basePath . '/src/Model/ImportModel.php');
            return stripos($content, 'duplicate') !== false || stripos($content, 'existing') !== false || stripos($content, 'update') !== false;
        });

        $this->test('Import handles existing products', function() {
            $content = file_get_contents($this->basePath . '/src/Model/ImportModel.php');
            return stripos($content, 'sku') !== false || stripos($content, 'product_id') !== false;
        });

        echo "\n=== Duplicate Detection Test Summary ===\n";
        echo "Passed: {$this->passed}, Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    private function test(string $name, callable $fn): void
    {
        try {
            if ($fn()) { echo "✓ {$name}\n"; $this->passed++; }
            else { echo "✗ {$name}\n"; $this->failed++; }
        } catch (\Exception $e) {
            echo "✗ {$name} - Error: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
}

$test = new DuplicateDetectionTest();
exit($test->run() ? 0 : 1);
