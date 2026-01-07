#!/usr/bin/env php
<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class InstallationVerificationTest {
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $type = 'plugin';
    private $folder = 'j2commerce';
    private $element = 'productcompare';

    public function __construct() {
        $this->db = Factory::getDbo();
    }

    public function run(): bool {
        echo "=== Installation Verification Tests ===\n\n";
        $this->testExtensionRegistered();
        $this->testFilesExist();
        $this->printSummary();
        return $this->failed === 0;
    }

    private function testExtensionRegistered(): void {
        echo "Test: Extension registered in database... ";
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->element));
        
        if ($this->type === 'plugin') {
            $query->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($this->folder));
        }
        
        $this->db->setQuery($query);
        $ext = $this->db->loadObject();
        
        if ($ext) {
            echo "✅ PASS\n";
            echo "  Extension ID: {$ext->extension_id}\n";
            echo "  Name: {$ext->name}\n";
            $this->passed++;
        } else {
            echo "❌ FAIL\n";
            $this->failed++;
        }
    }

    private function testFilesExist(): void {
        echo "\nTest: Extension files exist... ";
        
        if ($this->type === 'plugin') {
            $path = "/var/www/html/plugins/{$this->folder}/{$this->element}";
            $mainFile = "$path/{$this->element}.php";
        } else {
            $path = "/var/www/html/administrator/components/com_{$this->element}";
            $mainFile = "$path/{$this->element}.xml";
        }
        
        if (file_exists($mainFile)) {
            echo "✅ PASS\n";
            echo "  Path: $path\n";
            $this->passed++;
        } else {
            echo "❌ FAIL\n";
            $this->failed++;
        }
    }

    private function printSummary(): void {
        echo "\n=== Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        if ($this->failed === 0) echo "✅ All tests passed\n";
        else echo "❌ {$this->failed} test(s) failed\n";
    }
}

try {
    $app = Factory::getApplication('administrator');
    $test = new InstallationVerificationTest();
    exit($test->run() ? 0 : 1);
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
