<?php
/**
 * Test 02: Configuration
 * Tests plugin parameters and configuration
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
// Set CLI environment for Joomla URI
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class ConfigurationTest
{
    private $db;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Configuration Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testPluginExists() && $allPassed;
        $allPassed = $this->testParametersAccessible() && $allPassed;
        $allPassed = $this->testDefaultValues() && $allPassed;
        $allPassed = $this->testSessionTimeout() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testPluginExists(): bool
    {
        echo "Test: Plugin exists... ";
        
        $query = $this->db->getQuery(true)
            ->select('extension_id')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_2fa'));
        
        $this->db->setQuery($query);
        $id = $this->db->loadResult();
        
        if ($id) {
            echo "PASS (ID: {$id})\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testParametersAccessible(): bool
    {
        echo "Test: Parameters accessible... ";
        
        $query = $this->db->getQuery(true)
            ->select('params')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_2fa'));
        
        $this->db->setQuery($query);
        $paramsJson = $this->db->loadResult();
        $params = json_decode($paramsJson, true);
        
        if (is_array($params)) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testDefaultValues(): bool
    {
        echo "Test: Default parameter values... ";
        
        $query = $this->db->getQuery(true)
            ->select('params')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_2fa'));
        
        $this->db->setQuery($query);
        $paramsJson = $this->db->loadResult();
        $params = json_decode($paramsJson, true);
        
        $expectedParams = ['enabled', 'debug', 'preserve_cart', 'preserve_guest_cart', 'session_timeout'];
        $allPresent = true;
        
        foreach ($expectedParams as $param) {
            if (!isset($params[$param]) && $params[$param] !== '') {
                $allPresent = false;
            }
        }
        
        if ($allPresent || empty($params)) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testSessionTimeout(): bool
    {
        echo "Test: Session timeout range... ";
        
        $query = $this->db->getQuery(true)
            ->select('params')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('j2commerce_2fa'));
        
        $this->db->setQuery($query);
        $paramsJson = $this->db->loadResult();
        $params = json_decode($paramsJson, true);
        
        $timeout = isset($params['session_timeout']) ? (int)$params['session_timeout'] : 3600;
        
        if ($timeout >= 300 && $timeout <= 86400) {
            echo "PASS (Timeout: {$timeout}s)\n";
            return true;
        }
        
        echo "FAIL (Timeout: {$timeout}s, expected 300-86400)\n";
        return false;
    }

    private function printSummary(): void
    {
        echo "\n=== Configuration Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new ConfigurationTest();
$result = $test->run();
exit($result ? 0 : 1);
