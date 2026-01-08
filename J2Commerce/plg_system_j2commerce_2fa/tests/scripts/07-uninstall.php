<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;

class UninstallTest
{
    private $db;
    public function __construct() { $this->db = Factory::getDbo(); }
    
    public function run(): bool
    {
        echo "=== Uninstall Tests ===\n\n";
        $allPassed = true;
        $allPassed = $this->testPluginStillExists() && $allPassed;
        $this->printSummary();
        return $allPassed;
    }
    
    private function testPluginStillExists(): bool
    {
        echo "Test: Plugin still registered (before uninstall)... ";
        
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_2fa'));
        
        $this->db->setQuery($query);
        $id = $this->db->loadResult();
        
        if ($id) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }
    
    private function printSummary(): void
    {
        echo "\n=== Uninstall Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new UninstallTest();
exit($test->run() ? 0 : 1);
