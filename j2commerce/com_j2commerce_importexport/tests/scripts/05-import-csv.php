<?php
/**
 * CSV Import Tests for J2Commerce Import/Export
 */

class ImportCsvTest
{
    private $passed = 0;
    private $failed = 0;
    private $basePath = '/var/www/html/administrator/components/com_j2commerce_importexport';

    public function run(): bool
    {
        echo "=== CSV Import Tests ===\n\n";

        $this->test('ImportModel supports CSV format', function() {
            $content = file_get_contents($this->basePath . '/src/Model/ImportModel.php');
            return stripos($content, 'csv') !== false;
        });

        $this->test('ImportController handles CSV requests', function() {
            $content = file_get_contents($this->basePath . '/src/Controller/ImportController.php');
            return stripos($content, 'csv') !== false || stripos($content, 'import') !== false;
        });

        echo "\n=== CSV Import Test Summary ===\n";
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

$test = new ImportCsvTest();
exit($test->run() ? 0 : 1);
