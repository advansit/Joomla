<?php
/**
 * Backend Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class BackendTest
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
        echo "=== Backend Tests ===\n\n";

        $this->test('Dashboard view file exists', function() {
            return file_exists(JPATH_ADMINISTRATOR . '/components/com_j2commerce_importexport/tmpl/dashboard/default.php');
        });

        $this->test('HtmlView class exists', function() {
            return class_exists('Advans\Component\J2CommerceImportExport\Administrator\View\Dashboard\HtmlView');
        });

        $this->test('ExportController exists', function() {
            return class_exists('Advans\Component\J2CommerceImportExport\Administrator\Controller\ExportController');
        });

        $this->test('ImportController exists', function() {
            return class_exists('Advans\Component\J2CommerceImportExport\Administrator\Controller\ImportController');
        });

        $this->test('Service provider exists', function() {
            return file_exists(JPATH_ADMINISTRATOR . '/components/com_j2commerce_importexport/services/provider.php');
        });

        // Test HtmlView can load data
        $this->test('HtmlView loads menu types', function() {
            $view = new \Advans\Component\J2CommerceImportExport\Administrator\View\Dashboard\HtmlView();
            $reflection = new ReflectionClass($view);
            $method = $reflection->getMethod('getMenuTypes');
            $method->setAccessible(true);
            $menutypes = $method->invoke($view);
            return is_array($menutypes);
        });

        $this->test('HtmlView loads view levels', function() {
            $view = new \Advans\Component\J2CommerceImportExport\Administrator\View\Dashboard\HtmlView();
            $reflection = new ReflectionClass($view);
            $method = $reflection->getMethod('getViewLevels');
            $method->setAccessible(true);
            $levels = $method->invoke($view);
            return is_array($levels) && count($levels) > 0;
        });

        $this->test('HtmlView loads categories', function() {
            $view = new \Advans\Component\J2CommerceImportExport\Administrator\View\Dashboard\HtmlView();
            $reflection = new ReflectionClass($view);
            $method = $reflection->getMethod('getCategories');
            $method->setAccessible(true);
            $categories = $method->invoke($view);
            return is_array($categories);
        });

        echo "\n=== Backend Test Summary ===\n";
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

$test = new BackendTest();
exit($test->run() ? 0 : 1);
