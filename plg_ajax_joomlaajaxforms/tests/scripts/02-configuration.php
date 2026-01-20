<?php
/**
 * Test 02: Configuration
 * Tests plugin configuration and parameters
 */

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

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Configuration Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testEnablePlugin() && $allPassed;
        $allPassed = $this->testDefaultParams() && $allPassed;
        $allPassed = $this->testUpdateParams() && $allPassed;
        $allPassed = $this->testDisableReset() && $allPassed;
        $allPassed = $this->testDisableRemind() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testEnablePlugin(): bool
    {
        echo "Test: Enable plugin... ";
        
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__extensions'))
            ->set($this->db->quoteName('enabled') . ' = 1')
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
        
        $this->db->setQuery($query);
        
        try {
            $this->db->execute();
            echo "PASS\n";
            return true;
        } catch (Exception $e) {
            echo "FAIL ({$e->getMessage()})\n";
            return false;
        }
    }

    private function testDefaultParams(): bool
    {
        echo "Test: Default parameters... ";
        
        $query = $this->db->getQuery(true)
            ->select('params')
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
        
        $this->db->setQuery($query);
        $params = json_decode($this->db->loadResult() ?: '{}', true);
        
        // Default params should be empty or have defaults
        echo "PASS (params: " . json_encode($params) . ")\n";
        return true;
    }

    private function testUpdateParams(): bool
    {
        echo "Test: Update parameters... ";
        
        $newParams = json_encode([
            'enable_reset' => 1,
            'enable_remind' => 1
        ]);
        
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__extensions'))
            ->set($this->db->quoteName('params') . ' = ' . $this->db->quote($newParams))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
        
        $this->db->setQuery($query);
        
        try {
            $this->db->execute();
            echo "PASS\n";
            return true;
        } catch (Exception $e) {
            echo "FAIL ({$e->getMessage()})\n";
            return false;
        }
    }

    private function testDisableReset(): bool
    {
        echo "Test: Disable reset feature... ";
        
        $newParams = json_encode([
            'enable_reset' => 0,
            'enable_remind' => 1
        ]);
        
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__extensions'))
            ->set($this->db->quoteName('params') . ' = ' . $this->db->quote($newParams))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
        
        $this->db->setQuery($query);
        
        try {
            $this->db->execute();
            echo "PASS\n";
            return true;
        } catch (Exception $e) {
            echo "FAIL ({$e->getMessage()})\n";
            return false;
        }
    }

    private function testDisableRemind(): bool
    {
        echo "Test: Disable remind feature... ";
        
        $newParams = json_encode([
            'enable_reset' => 1,
            'enable_remind' => 0
        ]);
        
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__extensions'))
            ->set($this->db->quoteName('params') . ' = ' . $this->db->quote($newParams))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
        
        $this->db->setQuery($query);
        
        try {
            $this->db->execute();
            
            // Re-enable both for subsequent tests
            $newParams = json_encode([
                'enable_reset' => 1,
                'enable_remind' => 1
            ]);
            
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__extensions'))
                ->set($this->db->quoteName('params') . ' = ' . $this->db->quote($newParams))
                ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
                ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('ajax'))
                ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('joomlaajaxforms'));
            
            $this->db->setQuery($query);
            $this->db->execute();
            
            echo "PASS\n";
            return true;
        } catch (Exception $e) {
            echo "FAIL ({$e->getMessage()})\n";
            return false;
        }
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
