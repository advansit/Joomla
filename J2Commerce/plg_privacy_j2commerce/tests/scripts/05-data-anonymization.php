<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

class DataAnonymizationTest
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
        echo "=== Data Anonymization Tests ===\n\n";
        
        // Test 1: Plugin has anonymization methods
        $privacyPluginFile = JPATH_BASE . '/administrator/components/com_privacy/src/Plugin/PrivacyPlugin.php';
        if (file_exists($privacyPluginFile)) {
            require_once $privacyPluginFile;
        }
        
        $classFile = JPATH_BASE . '/plugins/privacy/j2commerce/src/Extension/J2Commerce.php';
        if (file_exists($classFile)) {
            require_once $classFile;
        }
        
        if (class_exists('Advans\\Plugin\\Privacy\\J2Commerce\\Extension\\J2Commerce')) {
            $reflection = new \ReflectionClass('Advans\\Plugin\\Privacy\\J2Commerce\\Extension\\J2Commerce');
            $this->test('anonymizeOrders method exists', 
                $reflection->hasMethod('anonymizeOrders'));
            $this->test('onPrivacyRemoveData method exists', 
                $reflection->hasMethod('onPrivacyRemoveData'));
            $this->test('onPrivacyCanRemoveData method exists', 
                $reflection->hasMethod('onPrivacyCanRemoveData'));
            $this->test('deleteAddresses method exists', 
                $reflection->hasMethod('deleteAddresses'));
        }
        
        echo "\n=== Data Anonymization Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        return $this->failed === 0;
    }
}

$test = new DataAnonymizationTest();
exit($test->run() ? 0 : 1);
