<?php
/**
 * Test 04: Password Reset Request
 * Tests the password reset functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class ResetRequestTest
{
    private $db;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Password Reset Request Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testResetFeatureEnabled() && $allPassed;
        $allPassed = $this->testPluginClassExists() && $allPassed;
        $allPassed = $this->testHandleResetMethodExists() && $allPassed;
        $allPassed = $this->testEmailValidation() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testResetFeatureEnabled(): bool
    {
        echo "Test: Reset feature config exists... ";
        
        $xmlFile = '/var/www/html/plugins/ajax/joomlaajaxforms/joomlaajaxforms.xml';
        
        if (!file_exists($xmlFile)) {
            echo "FAIL (XML file not found)\n";
            return false;
        }
        
        $content = file_get_contents($xmlFile);
        
        if (strpos($content, 'enable_reset') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (enable_reset config not found)\n";
        return false;
    }

    private function testPluginClassExists(): bool
    {
        echo "Test: Plugin class file exists... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (file_exists($classFile)) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testHandleResetMethodExists(): bool
    {
        echo "Test: handleReset method exists in plugin... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        if (strpos($content, 'function handleReset') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (method not found)\n";
        return false;
    }

    private function testEmailValidation(): bool
    {
        echo "Test: Email validation logic present... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        // Check for email validation
        if (strpos($content, 'FILTER_VALIDATE_EMAIL') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (email validation not found)\n";
        return false;
    }

    private function printSummary(): void
    {
        echo "\n=== Password Reset Request Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new ResetRequestTest();
$result = $test->run();
exit($result ? 0 : 1);
