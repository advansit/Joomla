<?php
/**
 * Test 05: Registration
 * Tests the registration functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class RegistrationTest
{
    public function run(): bool
    {
        echo "=== Registration Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testRegistrationFeatureConfig() && $allPassed;
        $allPassed = $this->testHandleRegistrationMethodExists() && $allPassed;
        $allPassed = $this->testActivationEmailMethod() && $allPassed;
        $allPassed = $this->testRegistrationLanguageStrings() && $allPassed;
        $allPassed = $this->testJavaScriptRegistrationHandler() && $allPassed;
        $allPassed = $this->testValidationChecks() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testRegistrationFeatureConfig(): bool
    {
        echo "Test: Registration feature config exists... ";
        
        $xmlFile = '/var/www/html/plugins/ajax/joomlaajaxforms/joomlaajaxforms.xml';
        
        if (!file_exists($xmlFile)) {
            echo "FAIL (XML file not found)\n";
            return false;
        }
        
        $content = file_get_contents($xmlFile);
        
        if (strpos($content, 'enable_registration') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (enable_registration config not found)\n";
        return false;
    }

    private function testHandleRegistrationMethodExists(): bool
    {
        echo "Test: handleRegistration method exists... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        if (strpos($content, 'function handleRegistration') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (method not found)\n";
        return false;
    }

    private function testActivationEmailMethod(): bool
    {
        echo "Test: sendActivationEmail method exists... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        if (strpos($content, 'function sendActivationEmail') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (method not found)\n";
        return false;
    }

    private function testRegistrationLanguageStrings(): bool
    {
        echo "Test: Registration language strings exist... ";
        
        $langFile = '/var/www/html/plugins/ajax/joomlaajaxforms/language/en-GB/plg_ajax_joomlaajaxforms.ini';
        
        if (!file_exists($langFile)) {
            echo "FAIL (language file not found)\n";
            return false;
        }
        
        $content = file_get_contents($langFile);
        
        if (strpos($content, 'REGISTRATION_SUCCESS') !== false && 
            strpos($content, 'REGISTRATION_FAILED') !== false &&
            strpos($content, 'ACTIVATION_EMAIL') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (language strings not found)\n";
        return false;
    }

    private function testJavaScriptRegistrationHandler(): bool
    {
        echo "Test: JavaScript registration handler exists... ";
        
        $jsFile = '/var/www/html/plugins/ajax/joomlaajaxforms/media/js/joomlaajaxforms.js';
        
        if (!file_exists($jsFile)) {
            echo "FAIL (JS file not found)\n";
            return false;
        }
        
        $content = file_get_contents($jsFile);
        
        if (strpos($content, 'initRegistrationForm') !== false && 
            strpos($content, 'convertRegistrationForm') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (registration handler not found)\n";
        return false;
    }

    private function testValidationChecks(): bool
    {
        echo "Test: Validation checks in registration... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        // Check for various validation patterns
        $hasEmailValidation = strpos($content, 'FILTER_VALIDATE_EMAIL') !== false;
        $hasUsernameCheck = strpos($content, 'USERNAME_EXISTS') !== false;
        $hasEmailCheck = strpos($content, 'EMAIL_EXISTS') !== false;
        $hasPasswordCheck = strpos($content, 'PASSWORD_MISMATCH') !== false;
        
        if ($hasEmailValidation && $hasUsernameCheck && $hasEmailCheck && $hasPasswordCheck) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (validation checks incomplete)\n";
        return false;
    }

    private function printSummary(): void
    {
        echo "\n=== Registration Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new RegistrationTest();
$result = $test->run();
exit($result ? 0 : 1);
