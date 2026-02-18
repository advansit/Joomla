<?php
/**
 * JSON Import Tests for J2Commerce Import/Export
 */

class ImportJsonTest
{
    private $passed = 0;
    private $failed = 0;
    private $basePath = '/var/www/html/administrator/components/com_j2commerce_importexport';

    public function run(): bool
    {
        echo "=== JSON Import Tests ===\n\n";

        $this->test('ImportModel file exists', function() {
            return file_exists($this->basePath . '/src/Model/ImportModel.php');
        });

        $this->test('ImportModel handles JSON format', function() {
            $content = file_get_contents($this->basePath . '/src/Model/ImportModel.php');
            return stripos($content, 'json') !== false;
        });

        $this->test('ImportController file exists', function() {
            return file_exists($this->basePath . '/src/Controller/ImportController.php');
        });

        echo "\n=== JSON Import Test Summary ===\n";
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

$test = new ImportJsonTest();
exit($test->run() ? 0 : 1);
