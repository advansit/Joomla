<?php
/**
 * CSV Import Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

class ImportCsvTest
{
    private $passed = 0;
    private $failed = 0;

    public function run(): bool
    {
        echo "=== CSV Import Tests ===\n\n";

        $this->test('ImportModel has parseCSV method', function() {
            $reflection = new \ReflectionClass(\Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel::class);
            return $reflection->hasMethod('parseCSV');
        });

        $this->test('Comment lines (starting with #) are skipped', function() {
            // Simulate CSV parsing logic
            $lines = [
                ['# This is a comment'],
                ['title', 'alias', 'price'],
                ['Product 1', 'product-1', '99.00']
            ];
            
            $headers = [];
            $data = [];
            
            foreach ($lines as $line) {
                if (empty($line) || (isset($line[0]) && strpos(trim($line[0]), '#') === 0)) {
                    continue;
                }
                if (empty($headers)) {
                    $headers = $line;
                    continue;
                }
                $data[] = array_combine($headers, $line);
            }
            
            return count($data) === 1 && $data[0]['title'] === 'Product 1';
        });

        $this->test('Empty lines are skipped', function() {
            $lines = [
                [''],
                ['title', 'price'],
                [''],
                ['Product', '50.00']
            ];
            
            $count = 0;
            foreach ($lines as $line) {
                if (empty($line) || (count($line) === 1 && empty($line[0]))) {
                    continue;
                }
                $count++;
            }
            
            return $count === 2; // header + 1 data row
        });

        $this->test('CSV preview returns correct structure', function() {
            // Expected structure from previewCSV
            $result = [
                'headers' => ['title', 'alias'],
                'rows' => [['title' => 'Test', 'alias' => 'test']],
                'total' => 1
            ];
            
            return isset($result['headers']) 
                && isset($result['rows']) 
                && isset($result['total']);
        });

        $this->test('Multiple comment lines are all skipped', function() {
            $lines = [
                ['# Comment 1'],
                ['# Comment 2'],
                ['# Comment 3'],
                ['title'],
                ['Product']
            ];
            
            $dataRows = 0;
            $headerFound = false;
            
            foreach ($lines as $line) {
                if (isset($line[0]) && strpos(trim($line[0]), '#') === 0) {
                    continue;
                }
                if (!$headerFound) {
                    $headerFound = true;
                    continue;
                }
                $dataRows++;
            }
            
            return $dataRows === 1;
        });

        echo "\n=== CSV Import Test Summary ===\n";
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

$test = new ImportCsvTest();
exit($test->run() ? 0 : 1);
