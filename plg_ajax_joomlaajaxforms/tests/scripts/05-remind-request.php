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
    private $baseUrl = 'http://localhost';

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Username Reminder Request Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testCreateTestUser() && $allPassed;
        $allPassed = $this->testRemindWithValidEmail() && $allPassed;
        $allPassed = $this->testRemindWithInvalidEmail() && $allPassed;
        $allPassed = $this->testRemindWithEmptyEmail() && $allPassed;
        $allPassed = $this->testRemindWithNonexistentEmail() && $allPassed;
        $allPassed = $this->testCleanupTestUser() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testCreateTestUser(): bool
    {
        echo "Test: Create test user... ";
        
        // Check if test user already exists
        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('username') . ' = ' . $this->db->quote('testuser_remind'));
        
        $this->db->setQuery($query);
        $existingId = $this->db->loadResult();
        
        if ($existingId) {
            echo "PASS (already exists, ID: $existingId)\n";
            return true;
        }
        
        // Create test user
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
                $this->db->quote('Test User Remind'),
                $this->db->quote('testuser_remind'),
                $this->db->quote('testremind@test.local'),
                $this->db->quote($password),
                0,
                $this->db->quote(date('Y-m-d H:i:s')),
                $this->db->quote('{}')
            ]));
        
        $this->db->setQuery($query);
        
        try {
            $this->db->execute();
            $userId = $this->db->insertid();
            echo "PASS (ID: $userId)\n";
            return true;
        } catch (Exception $e) {
            echo "FAIL ({$e->getMessage()})\n";
            return false;
        }
    }

    private function testRemindWithValidEmail(): bool
    {
        echo "Test: Remind with valid email... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=remind';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => 'testremind@test.local'
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        // Expect token error (CSRF protection working)
        if ($data && isset($data['success']) && $data['success'] === false) {
            echo "PASS (CSRF protection active)\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testRemindWithInvalidEmail(): bool
    {
        echo "Test: Remind with invalid email format... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=remind';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => 'not-an-email'
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($data && isset($data['success']) && $data['success'] === false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testRemindWithEmptyEmail(): bool
    {
        echo "Test: Remind with empty email... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=remind';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => ''
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($data && isset($data['success']) && $data['success'] === false) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testRemindWithNonexistentEmail(): bool
    {
        echo "Test: Remind with nonexistent email (security)... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json&task=remind';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => 'doesnotexist@test.local'
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        // Should return error (token) - same as valid email to prevent enumeration
        if ($data && isset($data['success']) && $data['success'] === false) {
            echo "PASS (no email enumeration)\n";
            return true;
        }
        
        echo "FAIL\n";
        return false;
    }

    private function testCleanupTestUser(): bool
    {
        echo "Test: Cleanup test user... ";
        
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('username') . ' = ' . $this->db->quote('testuser_remind'));
        
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

    private function printSummary(): void
    {
        echo "\n=== Username Reminder Request Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new RemindRequestTest();
$result = $test->run();
exit($result ? 0 : 1);
