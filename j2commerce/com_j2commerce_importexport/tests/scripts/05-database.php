<?php
/**
 * Database Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class DatabaseTest
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
        echo "=== Database Tests ===\n\n";

        $prefix = $this->db->getPrefix();
        $tables = $this->db->getTableList();

        // Joomla core tables
        $this->test('Joomla content table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'content', $tables);
        });

        $this->test('Joomla categories table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'categories', $tables);
        });

        $this->test('Joomla menu table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'menu', $tables);
        });

        $this->test('Joomla tags table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'tags', $tables);
        });

        $this->test('Joomla fields_values table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'fields_values', $tables);
        });

        // J2Store tables
        $this->test('J2Store products table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'j2store_products', $tables);
        });

        $this->test('J2Store variants table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'j2store_variants', $tables);
        });

        $this->test('J2Store productimages table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'j2store_productimages', $tables);
        });

        $this->test('J2Store productquantities table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'j2store_productquantities', $tables);
        });

        $this->test('J2Store product_prices table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'j2store_product_prices', $tables);
        });

        $this->test('J2Store options table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'j2store_options', $tables);
        });

        $this->test('J2Store filters table exists', function() use ($tables, $prefix) {
            return in_array($prefix . 'j2store_filters', $tables);
        });

        // Test database queries work
        $this->test('Can query content table', function() {
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__content');
            $this->db->setQuery($query);
            return is_numeric($this->db->loadResult());
        });

        $this->test('Can query j2store_products table', function() {
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__j2store_products');
            $this->db->setQuery($query);
            return is_numeric($this->db->loadResult());
        });

        echo "\n=== Database Test Summary ===\n";
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

$test = new DatabaseTest();
exit($test->run() ? 0 : 1);
