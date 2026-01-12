<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

class DataExportTest
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
        echo "=== Data Export Tests ===\n\n";
        
        // Test 1: Plugin class has export method
        $privacyPluginFile = JPATH_BASE . '/administrator/components/com_privacy/src/Plugin/PrivacyPlugin.php';
        if (file_exists($privacyPluginFile)) {
            require_once $privacyPluginFile;
        }
        
        $classFile = JPATH_BASE . '/plugins/privacy/j2commerce/src/Extension/J2Commerce.php';
        if (file_exists($classFile)) {
            require_once $classFile;
        }
        
        $this->test('Plugin class exists', 
            class_exists('Advans\\Plugin\\Privacy\\J2Commerce\\Extension\\J2Commerce'));
        
        if (class_exists('Advans\\Plugin\\Privacy\\J2Commerce\\Extension\\J2Commerce')) {
            $reflection = new \ReflectionClass('Advans\\Plugin\\Privacy\\J2Commerce\\Extension\\J2Commerce');
            $this->test('onPrivacyExportRequest method exists', 
                $reflection->hasMethod('onPrivacyExportRequest'));
            
            // Test 2: Method returns array
            $method = $reflection->getMethod('onPrivacyExportRequest');
            $returnType = $method->getReturnType();
            $this->test('Export method returns array', 
                $returnType && $returnType->getName() === 'array');
            
            // Test 3: Plugin has createOrdersDomain method
            $this->test('createOrdersDomain method exists', 
                $reflection->hasMethod('createOrdersDomain'));
            $this->test('createAddressesDomain method exists', 
                $reflection->hasMethod('createAddressesDomain'));
            $this->test('createJoomlaUserDomain method exists', 
                $reflection->hasMethod('createJoomlaUserDomain'));
        }
        
        echo "\n=== Data Export Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        return $this->failed === 0;
    }
}

$test = new DataExportTest();
exit($test->run() ? 0 : 1);
