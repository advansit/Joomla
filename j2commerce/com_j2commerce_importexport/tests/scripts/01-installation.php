<?php
/**
 * Installation Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class InstallationTest
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
        echo "=== Installation Tests ===\n\n";

        $this->test('Component registered in extensions table', function() {
            $query = $this->db->getQuery(true)
                ->select('extension_id')
                ->from('#__extensions')
                ->where('element = ' . $this->db->quote('com_j2commerce_importexport'))
                ->where('type = ' . $this->db->quote('component'));
            $this->db->setQuery($query);
            return (bool) $this->db->loadResult();
        });

        $this->test('Component is enabled', function() {
            $query = $this->db->getQuery(true)
                ->select('enabled')
                ->from('#__extensions')
                ->where('element = ' . $this->db->quote('com_j2commerce_importexport'));
            $this->db->setQuery($query);
            return $this->db->loadResult() == 1;
        });

        $this->test('Admin menu entry exists', function() {
            $query = $this->db->getQuery(true)
                ->select('id')
                ->from('#__menu')
                ->where('link LIKE ' . $this->db->quote('%com_j2commerce_importexport%'))
                ->where('client_id = 1');
            $this->db->setQuery($query);
            return (bool) $this->db->loadResult();
        });

        $this->test('ExportModel class exists', function() {
            return class_exists('Advans\Component\J2CommerceImportExport\Administrator\Model\ExportModel');
        });

        $this->test('ImportModel class exists', function() {
            return class_exists('Advans\Component\J2CommerceImportExport\Administrator\Model\ImportModel');
        });

        $this->test('Language files installed (en-GB)', function() {
            return file_exists(JPATH_ADMINISTRATOR . '/language/en-GB/com_j2commerce_importexport.ini');
        });

        echo "\n=== Installation Test Summary ===\n";
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

$test = new InstallationTest();
exit($test->run() ? 0 : 1);
