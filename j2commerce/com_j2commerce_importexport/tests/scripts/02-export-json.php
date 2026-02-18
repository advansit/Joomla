<?php
/**
 * JSON Export Tests for J2Commerce Import/Export
 * Tests file structure and code patterns without instantiating Joomla classes.
 */

class ExportJsonTest
{
    private $passed = 0;
    private $failed = 0;
    private $basePath = '/var/www/html/administrator/components/com_j2commerce_importexport';

    public function run(): bool
    {
        echo "=== JSON Export Tests ===\n\n";

        $this->test('ExportModel file exists', function() {
            return file_exists($this->basePath . '/src/Model/ExportModel.php');
        });

        $this->test('ExportController file exists', function() {
            return file_exists($this->basePath . '/src/Controller/ExportController.php');
        });

        $this->test('ExportModel contains export method', function() {
            $content = file_get_contents($this->basePath . '/src/Model/ExportModel.php');
            return strpos($content, 'function') !== false && strpos($content, 'export') !== false;
        });

        $this->test('ExportController has field descriptions', function() {
            $content = file_get_contents($this->basePath . '/src/Controller/ExportController.php');
            return strpos($content, 'getFieldDescriptions') !== false || strpos($content, 'fieldDescriptions') !== false;
        });

        echo "\n=== JSON Export Test Summary ===\n";
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

$test = new ExportJsonTest();
exit($test->run() ? 0 : 1);
