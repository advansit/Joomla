<?php
/**
 * Test 03: Uninstallation
 * Tests extension uninstallation and cleanup
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

class UninstallTest
{
    private $db;
    private $results = [];
    private $isComponent = true;
    private $pluginFolder = 'component';
    private $pluginElement = 'com_j2commerce_importexport';

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run()
    {
        echo "=== Uninstallation Tests ===\n\n";

        $extensionId = $this->getExtensionId();
        
        if (!$extensionId) {
            echo "❌ Extension not found\n";
            return false;
        }

        $this->uninstallExtension($extensionId);
        $this->verifyUninstallation();

        $this->printSummary();

        return empty(array_filter($this->results, function($r) { return !$r['passed']; }));
    }

    private function getExtensionId()
    {
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from('#__extensions');

        if ($this->isComponent) {
            $query->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
                  ->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->pluginElement));
        } else {
            $query->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
                  ->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->pluginElement))
                  ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($this->pluginFolder));
        }

        $this->db->setQuery($query);
        return $this->db->loadResult();
    }

    private function uninstallExtension($extensionId)
    {
        echo "Test: Uninstalling extension (ID: {$extensionId})...\n";

        $installer = Installer::getInstance();
        $result = $installer->uninstall('extension', $extensionId);

        if ($result) {
            echo "  ✅ Extension uninstalled successfully\n";
            $this->results[] = ['test' => 'Uninstallation', 'passed' => true];
        } else {
            echo "  ❌ Uninstallation failed\n";
            $this->results[] = ['test' => 'Uninstallation', 'passed' => false];
        }
    }

    private function verifyUninstallation()
    {
        echo "\nTest: Verifying cleanup...\n";

        $extensionId = $this->getExtensionId();

        if (!$extensionId) {
            echo "  ✅ Extension removed from database\n";
            $this->results[] = ['test' => 'Database Cleanup', 'passed' => true];
        } else {
            echo "  ❌ Extension still exists in database\n";
            $this->results[] = ['test' => 'Database Cleanup', 'passed' => false];
        }
    }

    private function printSummary()
    {
        echo "\n=== Uninstallation Test Summary ===\n";
        $passed = 0;
        $failed = 0;

        foreach ($this->results as $result) {
            if ($result['passed']) {
                $passed++;
                echo "✅ {$result['test']}\n";
            } else {
                $failed++;
                echo "❌ {$result['test']}\n";
            }
        }

        echo "\nTotal: " . count($this->results) . " tests\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";

        if ($failed > 0) {
            echo "\n❌ Uninstallation tests FAILED\n";
        } else {
            echo "\n✅ All uninstallation tests PASSED\n";
        }
    }
}

try {
    $app = Factory::getApplication('administrator');
    $test = new UninstallTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
