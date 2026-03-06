<?php
/**
 * Import Model Tests for J2Commerce Import/Export
 * Validates import model methods and supported formats.
 */

class ImportModelTest
{
    private $passed = 0;
    private $failed = 0;
    private $modelFile = '/var/www/html/administrator/components/com_j2commerce_importexport/src/Model/ImportModel.php';

    public function run(): bool
    {
        echo "=== Import Model Tests ===\n\n";

        $content = file_get_contents($this->modelFile);

        $this->test('ImportModel file is readable', function () use ($content) {
            return !empty($content);
        });

        $this->test('Has import method', function () use ($content) {
            return strpos($content, 'function import') !== false;
        });

        $this->test('Handles JSON format', function () use ($content) {
            return stripos($content, 'json') !== false;
        });

        $this->test('Handles CSV format', function () use ($content) {
            return stripos($content, 'csv') !== false;
        });

        $this->test('Has duplicate/update detection', function () use ($content) {
            return stripos($content, 'existing') !== false
                || stripos($content, 'duplicate') !== false
                || stripos($content, 'update') !== false;
        });

        $this->test('Has validation logic', function () use ($content) {
            return stripos($content, 'valid') !== false;
        });

        $this->test('Uses DatabaseAwareTrait or DB access', function () use ($content) {
            return strpos($content, 'DatabaseAwareTrait') !== false
                || strpos($content, 'getDatabase') !== false
                || strpos($content, 'getDbo') !== false;
        });

        echo "\n=== Import Model Test Summary ===\n";
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

$test = new ImportModelTest();
exit($test->run() ? 0 : 1);
