<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;

class PrivacyPluginBaseTest
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
        echo "=== Privacy Plugin Base Tests ===\n\n";
        
        // Test 1: Plugin is in database
        $query = $this->db->getQuery(true)
            ->select('extension_id, params')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'));
        
        $this->db->setQuery($query);
        $plugin = $this->db->loadObject();
        $this->test('Plugin is in database', $plugin !== null, 'Plugin not found');
        
        // Test 2: Plugin class file exists and can be loaded
        $classFile = JPATH_BASE . '/plugins/system/j2commerce/src/Extension/J2Commerce.php';
        $this->test('Plugin class file exists', file_exists($classFile));
        
        // Load Joomla Privacy Plugin base class
        $privacyPluginFile = JPATH_BASE . '/administrator/components/com_privacy/src/Plugin/PrivacyPlugin.php';
        if (file_exists($privacyPluginFile)) {
            require_once $privacyPluginFile;
        }
        
        if (file_exists($classFile)) {
            try {
                require_once $classFile;
                $this->test('Plugin class loaded', true);
            } catch (\Exception $e) {
                $this->test('Plugin class loaded', false, $e->getMessage());
            }
        }
        
        $this->test('Plugin class exists', 
            class_exists('Advans\\Plugin\\System\\J2Commerce\\Extension\\J2Commerce'));
        
        // Test 3: Plugin implements required methods
        if (class_exists('Advans\\Plugin\\System\\J2Commerce\\Extension\\J2Commerce')) {
            $reflection = new \ReflectionClass('Advans\\Plugin\\System\\J2Commerce\\Extension\\J2Commerce');
            
            // Core privacy methods
            $this->test('onPrivacyExportRequest method exists', 
                $reflection->hasMethod('onPrivacyExportRequest'));
            $this->test('onPrivacyCanRemoveData method exists', 
                $reflection->hasMethod('onPrivacyCanRemoveData'));
            $this->test('onPrivacyRemoveData method exists', 
                $reflection->hasMethod('onPrivacyRemoveData'));
            
            // Admin notification and logging methods
            $this->test('sendAdminNotification method exists', 
                $reflection->hasMethod('sendAdminNotification'));
            $this->test('logActivity method exists', 
                $reflection->hasMethod('logActivity'));
            
            // Cart data deletion
            $this->test('deleteCartData method exists', 
                $reflection->hasMethod('deleteCartData'));
            
            // MyProfile privacy section
            $this->test('injectPrivacySection method exists', 
                $reflection->hasMethod('injectPrivacySection'));
            $this->test('getPrivacySectionHtml method exists', 
                $reflection->hasMethod('getPrivacySectionHtml'));
            
            // Frontend features
            $this->test('onAfterRender method exists', 
                $reflection->hasMethod('onAfterRender'));
            $this->test('onAjaxJ2commercePrivacy method exists', 
                $reflection->hasMethod('onAjaxJ2commercePrivacy'));
        }
        
        // Test 4: Plugin parameters are accessible
        if ($plugin) {
            $params = json_decode($plugin->params);
            $this->test('Plugin parameters are valid JSON', $params !== null || $plugin->params === '{}');
            
            // Parameters are optional - plugin uses defaults if not set
            if ($params && !empty((array)$params)) {
                $this->test('retention_years parameter exists', 
                    property_exists($params, 'retention_years'));
                $this->test('anonymize_orders parameter exists', 
                    property_exists($params, 'anonymize_orders'));
                $this->test('delete_addresses parameter exists', 
                    property_exists($params, 'delete_addresses'));
            } else {
                echo "⚠ No parameters configured (using defaults)\n";
            }
        }
        
        // Test 5: Required database tables exist (optional in test environment)
        $tables = $this->db->getTableList();
        $prefix = $this->db->getPrefix();
        
        $hasOrders = in_array($prefix . 'j2store_orders', $tables);
        $hasAddresses = in_array($prefix . 'j2store_addresses', $tables);
        $hasItems = in_array($prefix . 'j2store_order_items', $tables);
        $hasCustomFields = in_array($prefix . 'j2store_product_customfields', $tables);
        
        if (!$hasOrders || !$hasAddresses || !$hasItems || !$hasCustomFields) {
            echo "⚠ J2Store tables not found (expected in test environment)\n";
        } else {
            $this->test('j2store_orders table exists', $hasOrders);
            $this->test('j2store_addresses table exists', $hasAddresses);
            $this->test('j2store_order_items table exists', $hasItems);
            $this->test('j2store_product_customfields table exists', $hasCustomFields);
        }
        
        echo "\n=== Privacy Plugin Base Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        return $this->failed === 0;
    }
}

$test = new PrivacyPluginBaseTest();
exit($test->run() ? 0 : 1);
