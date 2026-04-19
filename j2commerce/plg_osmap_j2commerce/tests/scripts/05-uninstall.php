<?php
/**
 * Uninstall Tests for OSMap J2Commerce Plugin
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
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

        // Get extension ID
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from('#__extensions')
            ->where('element = ' . $this->db->quote('j2commerce'))
            ->where('folder = ' . $this->db->quote('osmap'))
            ->where('type = ' . $this->db->quote('plugin'));
        $extensionId = (int) $this->db->setQuery($query)->loadResult();

        $this->test('Extension ID found before uninstall', function () use ($extensionId) {
            return $extensionId > 0;
        });

        $this->test('Uninstall succeeds', function () use ($extensionId) {
            if (!$extensionId) return false;
            $installer = Installer::getInstance();
            return $installer->uninstall('plugin', $extensionId);
        });

        $this->test('Plugin removed from #__extensions', function () {
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__extensions')
                ->where('element = ' . $this->db->quote('j2commerce'))
                ->where('folder = ' . $this->db->quote('osmap'));
            return (int) $this->db->setQuery($query)->loadResult() === 0;
        });

        $this->test('Plugin file removed', function () {
            return !file_exists(JPATH_PLUGINS . '/osmap/j2commerce/j2commerce.php');
        });

        echo "\n=== Uninstall Test Summary ===\n";
        echo "Passed: {$this->passed}, Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    private function test(string $name, callable $fn): void
    {
        try {
            if ($fn()) { echo "✓ {$name}\n"; $this->passed++; }
            else       { echo "✗ {$name}\n"; $this->failed++; }
        } catch (\Exception $e) {
            echo "✗ {$name} - Error: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
}

$test = new UninstallTest();
exit($test->run() ? 0 : 1);
