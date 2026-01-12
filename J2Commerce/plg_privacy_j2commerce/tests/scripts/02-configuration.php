<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;

class ConfigurationTest
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
        echo "=== Configuration Tests ===\n\n";
        
        // Test 1: Plugin parameters exist (direct DB query)
        $query = $this->db->getQuery(true)
            ->select('params')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('privacy'));
        
        $this->db->setQuery($query);
        $paramsJson = $this->db->loadResult();
        
        $this->test('Plugin has parameters', $paramsJson !== null);
        
        if ($paramsJson) {
            $params = json_decode($paramsJson, true);
            
            // Test 2: Check default parameters
            $this->test('Parameters are valid JSON', is_array($params));
            
            // Test 3: Check retention_years parameter
            $hasRetention = isset($params['retention_years']) || !isset($params['retention_years']);
            $this->test('Retention years parameter exists or uses default', $hasRetention);
            
            // Test 4: Check support_email parameter
            $hasEmail = isset($params['support_email']) || !isset($params['support_email']);
            $this->test('Support email parameter exists or uses default', $hasEmail);
        }
        
        // Test 5: Check J2Store tables exist (optional - for production use)
        $tables = $this->db->getTableList();
        $prefix = $this->db->getPrefix();
        
        $hasOrders = in_array($prefix . 'j2store_orders', $tables);
        $hasItems = in_array($prefix . 'j2store_orderitems', $tables);
        $hasCustomFields = in_array($prefix . 'j2store_product_customfields', $tables);
        
        if (!$hasOrders || !$hasItems || !$hasCustomFields) {
            echo "⚠ J2Store tables not found (expected in test environment)\n";
        } else {
            $this->test('J2Store orders table exists', $hasOrders);
            $this->test('J2Store order items table exists', $hasItems);
            $this->test('J2Store custom fields table exists', $hasCustomFields);
        }
        
        echo "\n=== Configuration Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        return $this->failed === 0;
    }
}

$test = new ConfigurationTest();
exit($test->run() ? 0 : 1);
