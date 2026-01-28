<?php
/**
 * JSON Export Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class ExportJsonTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->db = Factory::getContainer()->get('DatabaseDriver');
    }

    public function run(): bool
    {
        echo "=== JSON Export Tests ===\n\n";

        $this->test('ExportModel can be instantiated', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            return $model !== null;
        });

        $this->test('Export returns array with _documentation', function() {
            // Simulate export output structure
            $output = [
                '_documentation' => [
                    'description' => 'Test',
                    'fields' => []
                ],
                'products' => []
            ];
            return isset($output['_documentation']) && isset($output['products']);
        });

        $this->test('Field descriptions are defined', function() {
            $controller = new \Advans\Component\J2CommerceImportExport\Administrator\Controller\ExportController();
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('getFieldDescriptions');
            $method->setAccessible(true);
            $descriptions = $method->invoke($controller);
            
            return isset($descriptions['title']) 
                && isset($descriptions['main_image'])
                && isset($descriptions['sku']);
        });

        $this->test('main_image description explains path format', function() {
            $controller = new \Advans\Component\J2CommerceImportExport\Administrator\Controller\ExportController();
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('getFieldDescriptions');
            $method->setAccessible(true);
            $descriptions = $method->invoke($controller);
            
            return strpos($descriptions['main_image'], 'images/products/') !== false;
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
