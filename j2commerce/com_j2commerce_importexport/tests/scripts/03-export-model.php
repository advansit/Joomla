<?php
/**
 * Export Model Tests for J2Commerce Import/Export
 * Validates export model methods and supported formats.
 */

class ExportModelTest
{
    private $passed = 0;
    private $failed = 0;
    private $modelFile = '/var/www/html/administrator/components/com_j2commerce_importexport/src/Model/ExportModel.php';

    public function run(): bool
    {
        echo "=== Export Model Tests ===\n\n";

        $content = file_get_contents($this->modelFile);

        $this->test('ExportModel has exportData method', function () use ($content) {
            return strpos($content, 'function exportData') !== false;
        });

        $this->test('Supports products export', function () use ($content) {
            return strpos($content, 'exportProducts') !== false;
        });

        $this->test('Supports categories export', function () use ($content) {
            return strpos($content, 'exportCategories') !== false;
        });

        $this->test('Supports variants export', function () use ($content) {
            return strpos($content, 'exportVariants') !== false;
        });

        $this->test('Supports prices export', function () use ($content) {
            return strpos($content, 'exportPrices') !== false;
        });

        $this->test('Handles product images', function () use ($content) {
            return strpos($content, 'getProductImages') !== false;
        });

        $this->test('Handles product options', function () use ($content) {
            return strpos($content, 'getProductOptions') !== false;
        });

        $this->test('Handles product filters', function () use ($content) {
            return strpos($content, 'getProductFilters') !== false;
        });

        $this->test('Handles article custom fields', function () use ($content) {
            return strpos($content, 'getArticleCustomFields') !== false;
        });

        $this->test('Uses DatabaseAwareTrait or DB access', function () use ($content) {
            return strpos($content, 'DatabaseAwareTrait') !== false
                || strpos($content, 'getDatabase') !== false
                || strpos($content, 'getDbo') !== false;
        });

        echo "\n=== Export Model Test Summary ===\n";
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

$test = new ExportModelTest();
exit($test->run() ? 0 : 1);
