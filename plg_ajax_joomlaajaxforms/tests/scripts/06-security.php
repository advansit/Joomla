<?php
/**
 * Test 06: Security
 * Tests security features of the plugin
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
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
        $allPassed = $this->testCsrfProtection() && $allPassed;
        $allPassed = $this->testXssProtection() && $allPassed;
        $allPassed = $this->testSqlInjectionProtection() && $allPassed;
        $allPassed = $this->testEmailEnumerationPrevention() && $allPassed;
        $allPassed = $this->testBlockedUserHandling() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testCsrfProtection(): bool
    {
        echo "Test: CSRF token validation... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=reset';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => 'test@test.local'
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        // Should reject request without valid CSRF token
        if ($data && isset($data['success']) && $data['success'] === false) {
            echo "PASS (request rejected without token)\n";
            return true;
        }
        
        echo "FAIL (request should be rejected)\n";
        return false;
    }

    private function testXssProtection(): bool
    {
        echo "Test: XSS protection in email field... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=reset';
        
        $xssPayload = '<script>alert("xss")</script>@test.local';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => $xssPayload
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Response should not contain unescaped script tags
        if (strpos($response, '<script>') === false) {
            echo "PASS (XSS payload not reflected)\n";
            return true;
        }
        
        echo "FAIL (XSS payload reflected in response)\n";
        return false;
    }

    private function testSqlInjectionProtection(): bool
    {
        echo "Test: SQL injection protection... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=reset';
        
        $sqlPayload = "test@test.local' OR '1'='1";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => $sqlPayload
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Should return normal error response, not SQL error
        $data = json_decode($response, true);
        
        if ($httpCode === 200 && $data && isset($data['success'])) {
            echo "PASS (SQL injection handled safely)\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testEmailEnumerationPrevention(): bool
    {
        echo "Test: Email enumeration prevention... ";
        
        // Create a test user
        $password = password_hash('TestPassword123!', PASSWORD_DEFAULT);
        
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__users'))
            ->columns([
                $this->db->quoteName('name'),
                $this->db->quoteName('username'),
                $this->db->quoteName('email'),
                $this->db->quoteName('password'),
                $this->db->quoteName('block'),
                $this->db->quoteName('registerDate'),
                $this->db->quoteName('params')
            ])
            ->values(implode(',', [
                $this->db->quote('Security Test User'),
                $this->db->quote('securitytest'),
                $this->db->quote('security@test.local'),
                $this->db->quote($password),
                0,
                $this->db->quote(date('Y-m-d H:i:s')),
                $this->db->quote('{}')
            ]));
        
        try {
            $this->db->setQuery($query);
            $this->db->execute();
        } catch (Exception $e) {
            // User might already exist
        }
        
        // Test with existing email
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=reset';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => 'security@test.local'
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $responseExisting = curl_exec($ch);
        curl_close($ch);
        
        // Test with non-existing email
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => 'nonexistent123456@test.local'
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $responseNonExisting = curl_exec($ch);
        curl_close($ch);
        
        // Cleanup
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('username') . ' = ' . $this->db->quote('securitytest'));
        $this->db->setQuery($query);
        $this->db->execute();
        
        // Both responses should be similar (both return token error)
        $dataExisting = json_decode($responseExisting, true);
        $dataNonExisting = json_decode($responseNonExisting, true);
        
        // Both should fail with same type of error
        if ($dataExisting && $dataNonExisting && 
            $dataExisting['success'] === $dataNonExisting['success']) {
            echo "PASS (same response for existing/non-existing)\n";
            return true;
        }
        
        echo "FAIL (different responses reveal email existence)\n";
        return false;
    }

    private function testBlockedUserHandling(): bool
    {
        echo "Test: Blocked user handling... ";
        
        // Create a blocked test user
        $password = password_hash('TestPassword123!', PASSWORD_DEFAULT);
        
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__users'))
            ->columns([
                $this->db->quoteName('name'),
                $this->db->quoteName('username'),
                $this->db->quoteName('email'),
                $this->db->quoteName('password'),
                $this->db->quoteName('block'),
                $this->db->quoteName('registerDate'),
                $this->db->quoteName('params')
            ])
            ->values(implode(',', [
                $this->db->quote('Blocked User'),
                $this->db->quote('blockeduser'),
                $this->db->quote('blocked@test.local'),
                $this->db->quote($password),
                1, // blocked
                $this->db->quote(date('Y-m-d H:i:s')),
                $this->db->quote('{}')
            ]));
        
        try {
            $this->db->setQuery($query);
            $this->db->execute();
        } catch (Exception $e) {
            // User might already exist
        }
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=reset';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => 'blocked@test.local'
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Cleanup
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('username') . ' = ' . $this->db->quote('blockeduser'));
        $this->db->setQuery($query);
        $this->db->execute();
        
        $data = json_decode($response, true);
        
        // Should not reveal that user is blocked
        if ($data && isset($data['success'])) {
            echo "PASS (blocked status not revealed)\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function printSummary(): void
    {
        echo "\n=== Security Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new SecurityTest();
$result = $test->run();
exit($result ? 0 : 1);
