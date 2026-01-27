<?php
/**
 * J2Commerce Integration Tests for Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class J2CommerceTest
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
        echo "=== J2Commerce Integration Tests ===\n\n";

        // Check J2Store is installed
        $this->test('J2Store/J2Commerce is installed', function() {
            $query = $this->db->getQuery(true)
                ->select('extension_id')
                ->from('#__extensions')
                ->where('element = ' . $this->db->quote('com_j2store'));
            $this->db->setQuery($query);
            return (bool) $this->db->loadResult();
        });

        $this->test('J2Store products table exists', function() {
            $tables = $this->db->getTableList();
            $prefix = $this->db->getPrefix();
            return in_array($prefix . 'j2store_products', $tables);
        });

        $this->test('J2Store variants table exists', function() {
            $tables = $this->db->getTableList();
            $prefix = $this->db->getPrefix();
            return in_array($prefix . 'j2store_variants', $tables);
        });

        // Test Export Model
        $this->test('ExportModel can export products', function() {
            BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_j2commerce_importexport/src/Model');
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $data = $model->exportData('products');
            return is_array($data);
        });

        $this->test('ExportModel can export full products', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $data = $model->exportData('products_full');
            return is_array($data);
        });

        $this->test('ExportModel can export categories', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $data = $model->exportData('categories');
            return is_array($data);
        });

        $this->test('ExportModel can export variants', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel();
            $data = $model->exportData('variants');
            return is_array($data);
        });

        // Test Import Model
        $this->test('ImportModel can be instantiated', function() {
            $model = new \Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel();
            return $model instanceof BaseDatabaseModel;
        });

        echo "\n=== J2Commerce Test Summary ===\n";
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

$test = new J2CommerceTest();
exit($test->run() ? 0 : 1);
