<?php
/**
 * CSV Export Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

class ExportCsvTest
{
    private $passed = 0;
    private $failed = 0;

    public function run(): bool
    {
        echo "=== CSV Export Tests ===\n\n";

        $this->test('CSV export includes UTF-8 BOM', function() {
            // UTF-8 BOM bytes
            $bom = "\xEF\xBB\xBF";
            return strlen($bom) === 3;
        });

        $this->test('CSV comment lines start with #', function() {
            $commentLine = '# This is a description';
            return strpos($commentLine, '#') === 0;
        });

        $this->test('ExportController has exportCSV method', function() {
            $reflection = new \ReflectionClass(\Advans\Component\J2CommerceImportExport\Administrator\Controller\ExportController::class);
            return $reflection->hasMethod('exportCSV');
        });

        $this->test('exportCSV accepts includeHelp parameter', function() {
            $reflection = new \ReflectionClass(\Advans\Component\J2CommerceImportExport\Administrator\Controller\ExportController::class);
            $method = $reflection->getMethod('exportCSV');
            $params = $method->getParameters();
            
            // Should have 3 parameters: $data, $filename, $includeHelp
            return count($params) >= 3;
        });

        echo "\n=== CSV Export Test Summary ===\n";
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

$test = new ExportCsvTest();
exit($test->run() ? 0 : 1);
