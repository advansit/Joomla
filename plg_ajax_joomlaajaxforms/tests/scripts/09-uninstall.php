<?php
/**
 * Test 07: Uninstall
 * Tests plugin uninstallation
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class UninstallTest
{
    private $db;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Uninstall Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testPluginCanBeDisabled() && $allPassed;
        $allPassed = $this->testPluginCanBeRemoved() && $allPassed;
        $allPassed = $this->testFilesRemoved() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testPluginCanBeDisabled(): bool
    {
        echo "Test: Plugin can be disabled... ";
        
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__extensions'))
            ->set($this->db->quoteName('enabled') . ' = 0')
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
        
        $this->db->setQuery($query);
        
        try {
            $this->db->execute();
            
            // Verify it's disabled
            $query = $this->db->getQuery(true)
                ->select('enabled')
                ->from($this->db->quoteName('#__extensions'))
                ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
                ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
                ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
            
            $this->db->setQuery($query);
            $enabled = $this->db->loadResult();
            
            if ($enabled == 0) {
                echo "PASS\n";
                return true;
            }
            
            echo "FAIL (still enabled)\n";
            return false;
        } catch (Exception $e) {
            echo "FAIL ({$e->getMessage()})\n";
            return false;
        }
    }

    private function testPluginCanBeRemoved(): bool
    {
        echo "Test: Plugin can be removed from database... ";
        
        // Get extension ID first
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
        
        $this->db->setQuery($query);
        $extensionId = $this->db->loadResult();
        
        if (!$extensionId) {
            echo "SKIP (already removed)\n";
            return true;
        }
        
        // Remove from extensions table
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('extension_id') . ' = ' . (int) $extensionId);
        
        $this->db->setQuery($query);
        
        try {
            $this->db->execute();
            echo "PASS (ID: $extensionId removed)\n";
            return true;
        } catch (Exception $e) {
            echo "FAIL ({$e->getMessage()})\n";
            return false;
        }
    }

    private function testFilesRemoved(): bool
    {
        echo "Test: Plugin files can be removed... ";
        
        $pluginPath = '/var/www/html/plugins/ajax/joomlaajaxforms';
        
        if (!is_dir($pluginPath)) {
            echo "SKIP (directory already removed)\n";
            return true;
        }
        
        // Remove plugin directory
        $this->removeDirectory($pluginPath);
        
        if (!is_dir($pluginPath)) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (directory still exists)\n";
        return false;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    private function printSummary(): void
    {
        echo "\n=== Uninstall Test Summary ===\n";
        echo "All tests completed.\n";
        echo "Note: Plugin has been uninstalled during testing.\n";
    }
}

$test = new UninstallTest();
$result = $test->run();
exit($result ? 0 : 1);
