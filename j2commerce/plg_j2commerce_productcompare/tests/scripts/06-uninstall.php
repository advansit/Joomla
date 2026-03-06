<?php
/**
 * Uninstall Tests for J2Commerce Product Compare Plugin
 * Verifies clean removal of the plugin.
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

class UninstallTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Uninstall Tests ===\n\n";

        // Get extension ID before uninstall
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('extension_id'))
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('plg_j2commerce_productcompare'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'));
        $this->db->setQuery($query);
        $extensionId = (int) $this->db->loadResult();

        $this->test('Extension ID found before uninstall', function () use ($extensionId) {
            return $extensionId > 0;
        });

        // Perform uninstall
        $installer = Installer::getInstance();
        $uninstalled = false;
        if ($extensionId > 0) {
            $uninstalled = $installer->uninstall('plugin', $extensionId);
        }

        $this->test('Uninstall completed without errors', function () use ($uninstalled) {
            return $uninstalled === true;
        });

        $this->test('Plugin removed from #__extensions', function () {
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__extensions'))
                ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('plg_j2commerce_productcompare'));
            $this->db->setQuery($query);
            return (int) $this->db->loadResult() === 0;
        });

        $this->test('Plugin files removed', function () {
            return !file_exists(JPATH_PLUGINS . '/j2commerce/plg_j2commerce_productcompare/src/Extension/ProductCompare.php');
        });

        echo "\n=== Uninstall Test Summary ===\n";
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

$test = new UninstallTest();
exit($test->run() ? 0 : 1);
