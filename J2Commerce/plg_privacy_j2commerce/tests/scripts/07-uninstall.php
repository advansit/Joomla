<?php
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
    
    public function __construct() { 
        $this->db = Factory::getDbo(); 
    }
    
    private function test($name, $condition, $message = '') {
        if ($condition) {
            echo "✓ $name... PASS\n";
            $this->passed++;
            return true;
        } else {
            echo "✗ $name... FAIL" . ($message ? " - $message" : "") . "\n";
            $this->failed++;
            return false;
        }
    }
    
    public function run(): bool
    {
        echo "=== Uninstall Tests ===\n\n";
        
        // Test 1: Get plugin extension ID
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('privacy'));
        
        $this->db->setQuery($query);
        $extensionId = $this->db->loadResult();
        
        $this->test('Plugin extension found', $extensionId !== null);
        
        if (!$extensionId) {
            echo "Cannot proceed with uninstall tests - plugin not found\n";
            return false;
        }
        
        // Test 2: Verify plugin files exist
        $pluginPath = JPATH_BASE . '/plugins/privacy/j2commerce';
        $this->test('Plugin directory exists', is_dir($pluginPath));
        
        // Test 3: Verify plugin structure
        $this->test('Plugin manifest exists', 
            file_exists($pluginPath . '/j2commerce.xml'));
        $this->test('Plugin class exists', 
            file_exists($pluginPath . '/src/Extension/J2Commerce.php'));
        $this->test('Plugin services exists', 
            file_exists($pluginPath . '/services/provider.php'));
        
        // Test 4: Verify uninstall script exists
        $this->test('Uninstall script exists', 
            file_exists($pluginPath . '/script.php'));
        
        // Note: Actual uninstall requires Application context
        echo "\n⚠ Uninstall execution skipped (requires Application context)\n";
        echo "  Plugin can be uninstalled via Joomla admin interface\n";
        
        echo "\n=== Uninstall Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        return $this->failed === 0;
    }
}

$test = new UninstallTest();
exit($test->run() ? 0 : 1);
