<?php
/**
 * Component Structure Tests for J2Commerce Import/Export
 * Verifies all required files are deployed correctly.
 */

class ComponentStructureTest
{
    private $passed = 0;
    private $failed = 0;
    private $basePath = '/var/www/html/administrator/components/com_j2commerce_importexport';

    public function run(): bool
    {
        echo "=== Component Structure Tests ===\n\n";

        $this->test('ExportModel exists', function () {
            return file_exists($this->basePath . '/src/Model/ExportModel.php');
        });

        $this->test('ImportModel exists', function () {
            return file_exists($this->basePath . '/src/Model/ImportModel.php');
        });

        $this->test('ExportController exists', function () {
            return file_exists($this->basePath . '/src/Controller/ExportController.php');
        });

        $this->test('ImportController exists', function () {
            return file_exists($this->basePath . '/src/Controller/ImportController.php');
        });

        $this->test('DisplayController exists', function () {
            return file_exists($this->basePath . '/src/Controller/DisplayController.php');
        });

        $this->test('Dashboard view exists', function () {
            return file_exists($this->basePath . '/src/View/Dashboard/HtmlView.php');
        });

        $this->test('Dashboard template exists', function () {
            return file_exists($this->basePath . '/tmpl/dashboard/default.php');
        });

        $this->test('Services provider exists', function () {
            return file_exists($this->basePath . '/services/provider.php');
        });

        $this->test('Component extension class exists', function () {
            return file_exists($this->basePath . '/src/Extension/J2CommerceImportExportComponent.php');
        });

        echo "\n=== Component Structure Test Summary ===\n";
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

$test = new ComponentStructureTest();
exit($test->run() ? 0 : 1);
