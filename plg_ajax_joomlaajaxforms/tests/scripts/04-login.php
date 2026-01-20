<?php
/**
 * Test 04: Login
 * Tests the login functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class LoginTest
{
    public function run(): bool
    {
        echo "=== Login Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testLoginFeatureConfig() && $allPassed;
        $allPassed = $this->testHandleLoginMethodExists() && $allPassed;
        $allPassed = $this->testHandleLogoutMethodExists() && $allPassed;
        $allPassed = $this->testMfaValidateMethodExists() && $allPassed;
        $allPassed = $this->testMfaHelperMethods() && $allPassed;
        $allPassed = $this->testLoginLanguageStrings() && $allPassed;
        $allPassed = $this->testMfaLanguageStrings() && $allPassed;
        $allPassed = $this->testJavaScriptLoginHandler() && $allPassed;
        $allPassed = $this->testJavaScriptMfaHandler() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testLoginFeatureConfig(): bool
    {
        echo "Test: Login feature config exists... ";
        
        $xmlFile = '/var/www/html/plugins/ajax/joomlaajaxforms/joomlaajaxforms.xml';
        
        if (!file_exists($xmlFile)) {
            echo "FAIL (XML file not found)\n";
            return false;
        }
        
        $content = file_get_contents($xmlFile);
        
        if (strpos($content, 'enable_login') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (enable_login config not found)\n";
        return false;
    }

    private function testHandleLoginMethodExists(): bool
    {
        echo "Test: handleLogin method exists... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        if (strpos($content, 'function handleLogin') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (method not found)\n";
        return false;
    }

    private function testHandleLogoutMethodExists(): bool
    {
        echo "Test: handleLogout method exists... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        if (strpos($content, 'function handleLogout') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (method not found)\n";
        return false;
    }

    private function testLoginLanguageStrings(): bool
    {
        echo "Test: Login language strings exist... ";
        
        $langFile = '/var/www/html/plugins/ajax/joomlaajaxforms/language/en-GB/plg_ajax_joomlaajaxforms.ini';
        
        if (!file_exists($langFile)) {
            echo "FAIL (language file not found)\n";
            return false;
        }
        
        $content = file_get_contents($langFile);
        
        if (strpos($content, 'LOGIN_SUCCESS') !== false && 
            strpos($content, 'LOGIN_FAILED') !== false &&
            strpos($content, 'LOGOUT_SUCCESS') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (language strings not found)\n";
        return false;
    }

    private function testMfaValidateMethodExists(): bool
    {
        echo "Test: handleMfaValidate method exists... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        if (strpos($content, 'function handleMfaValidate') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (method not found)\n";
        return false;
    }

    private function testMfaHelperMethods(): bool
    {
        echo "Test: MFA helper methods exist... ";
        
        $classFile = '/var/www/html/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        
        if (!file_exists($classFile)) {
            echo "FAIL (class file not found)\n";
            return false;
        }
        
        $content = file_get_contents($classFile);
        
        $hasGetUserMfaRecords = strpos($content, 'function getUserMfaRecords') !== false;
        $hasValidateMfaCode = strpos($content, 'function validateMfaCode') !== false;
        $hasValidateTotpCode = strpos($content, 'function validateTotpCode') !== false;
        
        if ($hasGetUserMfaRecords && $hasValidateMfaCode && $hasValidateTotpCode) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (MFA helper methods incomplete)\n";
        return false;
    }

    private function testMfaLanguageStrings(): bool
    {
        echo "Test: MFA language strings exist... ";
        
        $langFile = '/var/www/html/plugins/ajax/joomlaajaxforms/language/en-GB/plg_ajax_joomlaajaxforms.ini';
        
        if (!file_exists($langFile)) {
            echo "FAIL (language file not found)\n";
            return false;
        }
        
        $content = file_get_contents($langFile);
        
        if (strpos($content, 'MFA_REQUIRED') !== false && 
            strpos($content, 'MFA_CODE_INVALID') !== false &&
            strpos($content, 'MFA_SESSION_EXPIRED') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (MFA language strings not found)\n";
        return false;
    }

    private function testJavaScriptLoginHandler(): bool
    {
        echo "Test: JavaScript login handler exists... ";
        
        $jsFile = '/var/www/html/plugins/ajax/joomlaajaxforms/media/js/joomlaajaxforms.js';
        
        if (!file_exists($jsFile)) {
            echo "FAIL (JS file not found)\n";
            return false;
        }
        
        $content = file_get_contents($jsFile);
        
        if (strpos($content, 'initLoginForm') !== false && 
            strpos($content, 'convertLoginForm') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (login handler not found)\n";
        return false;
    }

    private function testJavaScriptMfaHandler(): bool
    {
        echo "Test: JavaScript MFA handler exists... ";
        
        $jsFile = '/var/www/html/plugins/ajax/joomlaajaxforms/media/js/joomlaajaxforms.js';
        
        if (!file_exists($jsFile)) {
            echo "FAIL (JS file not found)\n";
            return false;
        }
        
        $content = file_get_contents($jsFile);
        
        if (strpos($content, 'showMfaForm') !== false && 
            strpos($content, 'submitMfaCode') !== false &&
            strpos($content, 'mfa_required') !== false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (MFA handler not found)\n";
        return false;
    }

    private function printSummary(): void
    {
        echo "\n=== Login Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new LoginTest();
$result = $test->run();
exit($result ? 0 : 1);
