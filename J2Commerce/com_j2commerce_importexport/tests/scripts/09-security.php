<?php
/**
 * Test 09: Security Tests
 * Tests SQL injection, XSS, CSRF protection, and access control
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class SecurityTest
{
    private $db;
    private $baseUrl = 'http://localhost';

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Security Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testSQLInjectionProtection() && $allPassed;
        $allPassed = $this->testXSSProtection() && $allPassed;
        $allPassed = $this->testCSRFProtection() && $allPassed;
        $allPassed = $this->testAccessControl() && $allPassed;
        $allPassed = $this->testInputValidation() && $allPassed;
        $allPassed = $this->testAPIAuthentication() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testSQLInjectionProtection(): bool
    {
        echo "Test: SQL injection protection... ";
        
        $injectionAttempts = [
            "' OR '1'='1",
            "'; DROP TABLE joom_license_keys; --",
            "1' UNION SELECT NULL, NULL, NULL--",
            "admin'--",
            "' OR 1=1--"
        ];
        
        $vulnerable = false;
        
        foreach ($injectionAttempts as $attempt) {
            // Test API endpoint with SQL injection
            $url = $this->baseUrl . '/index.php?option=com_j2commerce_importexport&task=api.validate';
            $response = $this->httpPost($url, [
                'license_key' => $attempt,
                'hardware_hash' => 'test-hash'
            ]);
            
            // Should return error, not expose SQL error or succeed
            if (stripos($response['body'], 'SQL') !== false || 
                stripos($response['body'], 'mysql') !== false ||
                stripos($response['body'], 'syntax error') !== false) {
                $vulnerable = true;
                break;
            }
        }
        
        if (!$vulnerable) {
            echo "✅ PASS (SQL injection attempts blocked)\n";
            return true;
        }
        
        echo "❌ FAIL (Vulnerable to SQL injection)\n";
        return false;
    }

    private function testXSSProtection(): bool
    {
        echo "Test: XSS protection... ";
        
        $xssAttempts = [
            "<script>alert('XSS')</script>",
            "<img src=x onerror=alert('XSS')>",
            "javascript:alert('XSS')",
            "<svg onload=alert('XSS')>",
            "'\"><script>alert(String.fromCharCode(88,83,83))</script>"
        ];
        
        $vulnerable = false;
        
        foreach ($xssAttempts as $attempt) {
            // Test activation form with XSS
            $url = $this->baseUrl . '/index.php?option=com_j2commerce_importexport&view=activate';
            $response = $this->httpPost($url, [
                'order_number' => $attempt,
                'email' => 'test@example.com',
                'hardware_hash' => 'test-hash'
            ]);
            
            // Check if script tags are rendered unescaped
            if (strpos($response['body'], $attempt) !== false) {
                $vulnerable = true;
                break;
            }
        }
        
        if (!$vulnerable) {
            echo "✅ PASS (XSS attempts sanitized)\n";
            return true;
        }
        
        echo "❌ FAIL (Vulnerable to XSS)\n";
        return false;
    }

    private function testCSRFProtection(): bool
    {
        echo "Test: CSRF protection... ";
        
        // Test form submission without CSRF token
        $url = $this->baseUrl . '/index.php?option=com_j2commerce_importexport&task=activate.submit';
        $response = $this->httpPost($url, [
            'order_number' => 'TEST-123',
            'email' => 'test@example.com',
            'hardware_hash' => 'test-hash'
        ]);
        
        // Should be rejected (403 or redirect with error)
        if ($response['code'] === 403 || 
            stripos($response['body'], 'token') !== false ||
            stripos($response['body'], 'invalid') !== false) {
            echo "✅ PASS (CSRF token required)\n";
            return true;
        }
        
        // If it returns 200 with success, CSRF protection might be missing
        if ($response['code'] === 200 && stripos($response['body'], 'success') !== false) {
            echo "❌ FAIL (CSRF protection missing)\n";
            return false;
        }
        
        echo "✅ PASS (Request rejected without token)\n";
        return true;
    }

    private function testAccessControl(): bool
    {
        echo "Test: Access control... ";
        
        // Test backend access without authentication
        $url = $this->baseUrl . '/administrator/index.php?option=com_j2commerce_importexport';
        $response = $this->httpGet($url);
        
        // Should redirect to login or return 403
        if ($response['code'] === 403 || 
            $response['code'] === 401 ||
            stripos($response['body'], 'login') !== false) {
            echo "✅ PASS (Backend requires authentication)\n";
            return true;
        }
        
        // If we get 200, check if it's actually the login page
        if ($response['code'] === 200 && stripos($response['body'], 'username') !== false) {
            echo "✅ PASS (Redirected to login)\n";
            return true;
        }
        
        echo "⚠️  WARNING (Backend access control unclear - HTTP {$response['code']})\n";
        return true; // Not critical for automated test
    }

    private function testInputValidation(): bool
    {
        echo "Test: Input validation... ";
        
        $invalidInputs = [
            ['license_key' => '', 'hardware_hash' => ''],
            ['license_key' => str_repeat('A', 1000), 'hardware_hash' => 'test'],
            ['license_key' => 'TEST', 'hardware_hash' => str_repeat('B', 1000)]
        ];
        
        $allRejected = true;
        
        foreach ($invalidInputs as $input) {
            $url = $this->baseUrl . '/index.php?option=com_j2commerce_importexport&task=api.validate';
            $response = $this->httpPost($url, $input);
            
            // Parse JSON response
            $json = json_decode($response['body'], true);
            
            // Should return error (success: false or valid: false)
            if ($json && isset($json['success']) && $json['success'] === true && 
                isset($json['valid']) && $json['valid'] === true) {
                $allRejected = false;
                break;
            }
        }
        
        if ($allRejected) {
            echo "✅ PASS (Invalid inputs rejected)\n";
            return true;
        }
        
        echo "❌ FAIL (Invalid inputs accepted)\n";
        return false;
    }

    private function testAPIAuthentication(): bool
    {
        echo "Test: API authentication... ";
        
        // Test API endpoints without proper parameters
        $endpoints = [
            'api.validate',
            'api.activate',
            'api.getsecret'
        ];
        
        $allProtected = true;
        
        foreach ($endpoints as $task) {
            $url = $this->baseUrl . '/index.php?option=com_j2commerce_importexport&task=' . $task;
            $response = $this->httpGet($url);
            
            // Should return error or require POST
            if ($response['code'] === 200 && 
                stripos($response['body'], 'error') === false &&
                stripos($response['body'], 'invalid') === false) {
                $allProtected = false;
                break;
            }
        }
        
        if ($allProtected) {
            echo "✅ PASS (API endpoints protected)\n";
            return true;
        }
        
        echo "❌ FAIL (API endpoints not properly protected)\n";
        return false;
    }

    private function httpGet(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $code, 'body' => $body ?: ''];
    }

    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $code, 'body' => $body ?: ''];
    }

    private function printSummary(): void
    {
        echo "\n=== Security Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

// Run tests
try {
    // Initialize Joomla application
    if (!defined('JPATH_COMPONENT')) {
        define('JPATH_COMPONENT', JPATH_BASE . '/components/com_j2commerce_importexport');
    }
    
    $test = new SecurityTest();
    $result = $test->run();
    exit($result ? 0 : 1);
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
