<?php
/**
 * Export Controller Tests for J2Commerce Import/Export
 * Validates controller methods and format support.
 */

class ExportControllerTest
{
    private $passed = 0;
    private $failed = 0;
    private $controllerFile = '/var/www/html/administrator/components/com_j2commerce_importexport/src/Controller/ExportController.php';

    public function run(): bool
    {
        echo "=== Export Controller Tests ===\n\n";

        $content = file_get_contents($this->controllerFile);

        $this->test('ExportController is readable', function () use ($content) {
            return !empty($content);
        });

        $this->test('Has JSON export method', function () use ($content) {
            return stripos($content, 'json') !== false && stripos($content, 'export') !== false;
        });

        $this->test('Has CSV export method', function () use ($content) {
            return stripos($content, 'csv') !== false && stripos($content, 'export') !== false;
        });

        $this->test('Has field descriptions', function () use ($content) {
            return strpos($content, 'getFieldDescriptions') !== false
                || strpos($content, 'fieldDescriptions') !== false;
        });

        $this->test('Sets proper content-type headers', function () use ($content) {
            return stripos($content, 'content-type') !== false
                || stripos($content, 'header') !== false
                || stripos($content, 'application/json') !== false;
        });

        echo "\n=== Export Controller Test Summary ===\n";
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

$test = new ExportControllerTest();
exit($test->run() ? 0 : 1);
