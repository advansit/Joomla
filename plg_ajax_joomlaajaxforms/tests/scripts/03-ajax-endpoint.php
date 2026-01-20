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

class AjaxEndpointTest
{
    private $baseUrl = 'http://localhost';

    public function run(): bool
    {
        echo "=== AJAX Endpoint Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testPluginEnabled() && $allPassed;
        $allPassed = $this->testEndpointAccessible() && $allPassed;
        $allPassed = $this->testResponseFormat() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testPluginEnabled(): bool
    {
        echo "Test: Plugin is enabled... ";
        
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('enabled')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('ajax'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('joomlaajaxforms'));
        
        $db->setQuery($query);
        $enabled = $db->loadResult();
        
        if ($enabled == 1) {
            echo "PASS\n";
            return true;
        }
        
        echo "FAIL (enabled=$enabled)\n";
        return false;
    }

    private function testEndpointAccessible(): bool
    {
        echo "Test: AJAX endpoint accessible... ";
        
        // com_ajax endpoint
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "(HTTP $httpCode) ";
        
        // HTTP 200 is success, but also check we got a response
        if ($httpCode === 200 && !empty($response)) {
            echo "PASS\n";
            echo "  Response preview: " . substr($response, 0, 100) . "\n";
            return true;
        }
        
        echo "FAIL\n";
        echo "  Response: $response\n";
        return false;
    }

    private function testResponseFormat(): bool
    {
        echo "Test: Response contains expected structure... ";
        
        $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        // com_ajax wraps plugin response, try to decode
        $data = json_decode($response, true);
        
        if ($data !== null) {
            // com_ajax returns array with plugin response or error
            echo "PASS (valid JSON)\n";
            return true;
        }
        
        // Check if it's HTML error page
        if (strpos($response, '<html') !== false || strpos($response, '<!DOCTYPE') !== false) {
            echo "FAIL (HTML response instead of JSON)\n";
            return false;
        }
        
        echo "FAIL (invalid response format)\n";
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
