<?php
/**
 * Multilingual Tests for J2Commerce Import/Export
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class MultilingualTest
{
    private $passed = 0;
    private $failed = 0;

    public function run(): bool
    {
        echo "=== Multilingual Tests ===\n\n";

        // Check language files exist
        $this->test('English language file exists', function() {
            return file_exists(JPATH_ADMINISTRATOR . '/language/en-GB/com_j2commerce_importexport.ini');
        });

        $this->test('English sys language file exists', function() {
            return file_exists(JPATH_ADMINISTRATOR . '/language/en-GB/com_j2commerce_importexport.sys.ini');
        });

        $this->test('German (CH) language file exists', function() {
            return file_exists(JPATH_ADMINISTRATOR . '/language/de-CH/com_j2commerce_importexport.ini');
        });

        $this->test('French language file exists', function() {
            return file_exists(JPATH_ADMINISTRATOR . '/language/fr-FR/com_j2commerce_importexport.ini');
        });

        // Check key translations exist
        $this->test('Component name translation exists', function() {
            $lang = Factory::getLanguage();
            $lang->load('com_j2commerce_importexport', JPATH_ADMINISTRATOR);
            $text = Text::_('COM_J2COMMERCE_IMPORTEXPORT');
            return $text !== 'COM_J2COMMERCE_IMPORTEXPORT';
        });

        $this->test('Export translation exists', function() {
            $text = Text::_('COM_J2COMMERCE_IMPORTEXPORT_EXPORT');
            return $text !== 'COM_J2COMMERCE_IMPORTEXPORT_EXPORT';
        });

        $this->test('Import translation exists', function() {
            $text = Text::_('COM_J2COMMERCE_IMPORTEXPORT_IMPORT');
            return $text !== 'COM_J2COMMERCE_IMPORTEXPORT_IMPORT';
        });

        $this->test('Products Full translation exists', function() {
            $text = Text::_('COM_J2COMMERCE_IMPORTEXPORT_PRODUCTS_FULL');
            return $text !== 'COM_J2COMMERCE_IMPORTEXPORT_PRODUCTS_FULL';
        });

        $this->test('Create Menu translation exists', function() {
            $text = Text::_('COM_J2COMMERCE_IMPORTEXPORT_CREATE_MENU');
            return $text !== 'COM_J2COMMERCE_IMPORTEXPORT_CREATE_MENU';
        });

        $this->test('Menu Access translation exists', function() {
            $text = Text::_('COM_J2COMMERCE_IMPORTEXPORT_MENU_ACCESS');
            return $text !== 'COM_J2COMMERCE_IMPORTEXPORT_MENU_ACCESS';
        });

        echo "\n=== Multilingual Test Summary ===\n";
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

$test = new MultilingualTest();
exit($test->run() ? 0 : 1);
