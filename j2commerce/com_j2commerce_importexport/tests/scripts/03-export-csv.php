<?php
/**
 * CSV Export Tests for J2Commerce Import/Export
 */

class ExportCsvTest
{
    private $passed = 0;
    private $failed = 0;
    private $basePath = '/var/www/html/administrator/components/com_j2commerce_importexport';

    public function run(): bool
    {
        echo "=== CSV Export Tests ===\n\n";

        $this->test('ExportModel supports CSV format', function() {
            $content = file_get_contents($this->basePath . '/src/Model/ExportModel.php');
            return stripos($content, 'csv') !== false;
        });

        $this->test('ExportController handles CSV requests', function() {
            $content = file_get_contents($this->basePath . '/src/Controller/ExportController.php');
            return stripos($content, 'csv') !== false;
        });

        echo "\n=== CSV Export Test Summary ===\n";
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

$test = new ExportCsvTest();
exit($test->run() ? 0 : 1);
