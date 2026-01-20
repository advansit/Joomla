<?php
/**
 * Test 05: Username Reminder Request
 * Tests the username reminder functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class RemindRequestTest
{
    private $db;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Username Reminder Request Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testRemindFeatureEnabled() && $allPassed;
        $allPassed = $this->testHandleRemindMethodExists() && $allPassed;
        $allPassed = $this->testSendRemindEmailMethodExists() && $allPassed;
        $allPassed = $this->testLanguageStringsExist() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testRemindFeatureEnabled(): bool
    {
        echo "Test: Remind feature config exists... ";
        
        $xmlFile = '/var/www/html/plugins/ajax/joomlaajaxforms/joomlaajaxforms.xml';
        
        if (!file_exists($xmlFile)) {
            echo "FAIL (XML file not found)\n";
            return false;
        }
        
        $content = file_get_contents($xmlFile);
        
        if (strpos($content, 'enable_remind') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (enable_remind config not found)\n";
        return false;
    }

    private function testHandleRemindMethodExists(): bool
    {
        echo "Test: handleRemind method exists in plugin... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        if (strpos($content, 'function handleRemind') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (method not found)\n";
        return false;
    }

    private function testSendRemindEmailMethodExists(): bool
    {
        echo "Test: sendRemindEmail method exists... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        if (strpos($content, 'function sendRemindEmail') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (method not found)\n";
        return false;
    }

    private function testLanguageStringsExist(): bool
    {
        echo "Test: Remind language strings exist... ";
        
        $langFile = '/var/www/html/plugins/ajax/joomlaajaxforms/language/en-GB/plg_ajax_joomlaajaxforms.ini';
        
        if (!file_exists($langFile)) {
            echo "FAIL (language file not found)\n";
            return false;
        }
        
        $content = file_get_contents($langFile);
        
        if (strpos($content, 'REMIND_SUCCESS') !== false && 
            strpos($content, 'REMIND_EMAIL_SUBJECT') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (language strings not found)\n";
        return false;
    }

    private function printSummary(): void
    {
        echo "\n=== Username Reminder Request Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new RemindRequestTest();
$result = $test->run();
exit($result ? 0 : 1);
