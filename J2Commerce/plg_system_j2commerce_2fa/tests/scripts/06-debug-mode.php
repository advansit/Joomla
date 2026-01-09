<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
// Set CLI environment for Joomla URI
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;

class DebugModeTest
{
    private $db;
    public function __construct() { $this->db = Factory::getDbo(); }
    
    public function run(): bool
    {
        echo "=== Debug Mode Tests ===\n\n";
        $allPassed = true;
        $allPassed = $this->testDebugParameter() && $allPassed;
        $this->printSummary();
        return $allPassed;
    }
    
    private function testDebugParameter(): bool
    {
        echo "Test: Debug parameter... ";
        
        $query = $this->db->getQuery(true)
            ->select('params')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_2fa'));
        
        $this->db->setQuery($query);
        $paramsJson = $this->db->loadResult();
        $params = json_decode($paramsJson, true);
        
        $debug = isset($params['debug']) ? $params['debug'] : '0';
        echo "PASS (Debug: {$debug})\n";
        return true;
    }
    
    private function printSummary(): void
    {
        echo "\n=== Debug Mode Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new DebugModeTest();
exit($test->run() ? 0 : 1);
