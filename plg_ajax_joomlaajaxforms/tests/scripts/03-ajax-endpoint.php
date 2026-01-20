<?php
/**
 * Test 03: AJAX Endpoint
 * Tests that the AJAX endpoint is accessible
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

class AjaxEndpointTest
{
    private $baseUrl = 'http://localhost';

    public function run(): bool
    {
        echo "=== AJAX Endpoint Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testEndpointExists() && $allPassed;
        $allPassed = $this->testInvalidTask() && $allPassed;
        $allPassed = $this->testMissingToken() && $allPassed;
        $allPassed = $this->testJsonResponse() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testEndpointExists(): bool
    {
        echo "Test: AJAX endpoint accessible... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "PASS (HTTP $httpCode)\n";
            return true;
        }
        
        echo "FAIL (HTTP $httpCode)\n";
        return false;
    }

    private function testInvalidTask(): bool
    {
        echo "Test: Invalid task returns error... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=invalid';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        // Should return error for invalid task (or token error first)
        if ($data && isset($data['success']) && $data['success'] === false) {
            echo "PASS (error returned)\n";
            return true;
        }
        
        // Also acceptable: token error
        if ($data && isset($data['error'])) {
            echo "PASS (token/error returned)\n";
            return true;
        }
        
        echo "FAIL (unexpected response)\n";
        return false;
    }

    private function testMissingToken(): bool
    {
        echo "Test: Missing CSRF token returns error... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=reset';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        // Should return error for missing token
        if ($data && isset($data['success']) && $data['success'] === false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testJsonResponse(): bool
    {
        echo "Test: Response is valid JSON... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($data !== null && json_last_error() === JSON_ERROR_NONE) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (invalid JSON)\n";
        return false;
    }

    private function printSummary(): void
    {
        echo "\n=== AJAX Endpoint Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new AjaxEndpointTest();
$result = $test->run();
exit($result ? 0 : 1);
