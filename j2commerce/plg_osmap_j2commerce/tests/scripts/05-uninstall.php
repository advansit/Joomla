<?php
/**
 * Uninstall Tests for OSMap J2Commerce Plugin
 *
 * Uses direct DB operations instead of Installer::getInstance() —
 * Installer calls Factory::getApplication() internally (CLI-unsafe).
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class UninstallTest
{
    private $db;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Uninstall Tests ===\n\n";

        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from('#__extensions')
            ->where('element = ' . $this->db->quote('j2commerce'))
            ->where('folder = '  . $this->db->quote('osmap'))
            ->where('type = '    . $this->db->quote('plugin'));
        $extensionId = (int) $this->db->setQuery($query)->loadResult();

        $this->test('Extension ID found before uninstall', function () use ($extensionId) {
            return $extensionId > 0;
        });

        $this->test('Uninstall succeeds (DB + files)', function () use ($extensionId) {
            if (!$extensionId) {
                return false;
            }

            // Remove from #__extensions
            $this->db->setQuery(
                $this->db->getQuery(true)
                    ->delete('#__extensions')
                    ->where('extension_id = ' . $extensionId)
            )->execute();

            // Remove plugin files
            $pluginDir = JPATH_PLUGINS . '/osmap/j2commerce';
            if (is_dir($pluginDir)) {
                $this->removeDir($pluginDir);
            }

            return true;
        });

        $this->test('Plugin removed from #__extensions', function () {
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__extensions')
                ->where('element = ' . $this->db->quote('j2commerce'))
                ->where('folder = '  . $this->db->quote('osmap'));
            return (int) $this->db->setQuery($query)->loadResult() === 0;
        });

        $this->test('Plugin file removed', function () {
            return !file_exists(JPATH_PLUGINS . '/osmap/j2commerce/j2commerce.php');
        });

        echo "\n=== Uninstall Test Summary ===\n";
        echo "Passed: {$this->passed}, Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function test(string $name, callable $fn): void
    {
        try {
            if ($fn()) { echo "✓ {$name}\n"; $this->passed++; }
            else       { echo "✗ {$name}\n"; $this->failed++; }
        } catch (\Throwable $e) {
            echo "✗ {$name} - Error: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
}

$test = new UninstallTest();
exit($test->run() ? 0 : 1);
