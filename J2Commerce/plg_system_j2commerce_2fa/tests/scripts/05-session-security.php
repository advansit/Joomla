<?php
/**
 * Session Security Tests for System - J2Commerce 2FA Plugin
 * 
 * Tests session security features (regeneration, timeout)
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$results = [];
$passed = 0;
$failed = 0;

echo "=== Session Security Tests ===\n\n";

try {
    $db = Factory::getDbo();
    $session = Factory::getSession();
    
    // Test 1: Session ID regeneration
    echo "Test 1: Session ID regeneration\n";
    
    $originalId = $session->getId();
    echo "  Original session ID: " . substr($originalId, 0, 16) . "...\n";
    
    // Regenerate session
    $session->restart();
    
    $newId = $session->getId();
    echo "  New session ID: " . substr($newId, 0, 16) . "...\n";
    
    if ($originalId !== $newId) {
        echo "✅ PASS: Session ID successfully regenerated\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session ID did not change\n";
        $failed++;
    }
    
    // Test 2: Session data preservation after regeneration
    echo "\nTest 2: Session data preservation after regeneration\n";
    
    $testData = [
        'test_key' => 'test_value_' . time(),
        'test_array' => ['item1', 'item2', 'item3']
    ];
    
    // Store test data
    $session->set('test_data', $testData);
    
    // Regenerate
    $session->restart();
    
    // Retrieve data
    $retrievedData = $session->get('test_data');
    
    if ($retrievedData && 
        $retrievedData['test_key'] === $testData['test_key'] &&
        count($retrievedData['test_array']) === count($testData['test_array'])) {
        echo "✅ PASS: Session data preserved after regeneration\n";
        echo "  - Key preserved: " . $retrievedData['test_key'] . "\n";
        echo "  - Array preserved: " . count($retrievedData['test_array']) . " items\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session data not preserved\n";
        $failed++;
    }
    
    // Test 3: Get plugin session timeout configuration
    echo "\nTest 3: Plugin session timeout configuration\n";
    
    $query = $db->getQuery(true)
        ->select($db->quoteName('params'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce_2fa'));
    
    $db->setQuery($query);
    $paramsJson = $db->loadResult();
    $params = json_decode($paramsJson, true);
    
    $sessionTimeout = isset($params['session_timeout']) ? (int)$params['session_timeout'] : 3600;
    
    echo "  Configured timeout: {$sessionTimeout} seconds\n";
    
    if ($sessionTimeout >= 300 && $sessionTimeout <= 86400) {
        echo "✅ PASS: Session timeout within valid range (300-86400)\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session timeout outside valid range\n";
        $failed++;
    }
    
    // Test 4: Session expiry time
    echo "\nTest 4: Session expiry time\n";
    
    $sessionExpire = $session->getExpire();
    $currentTime = time();
    $timeUntilExpiry = $sessionExpire - $currentTime;
    
    echo "  Current time: " . date('Y-m-d H:i:s', $currentTime) . "\n";
    echo "  Session expires: " . date('Y-m-d H:i:s', $sessionExpire) . "\n";
    echo "  Time until expiry: {$timeUntilExpiry} seconds\n";
    
    if ($timeUntilExpiry > 0) {
        echo "✅ PASS: Session has valid expiry time\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session expiry time invalid\n";
        $failed++;
    }
    
    // Test 5: Session token exists
    echo "\nTest 5: Session token (CSRF protection)\n";
    
    $token = $session->getToken();
    
    if (!empty($token) && strlen($token) >= 32) {
        echo "✅ PASS: Session token exists and is valid length\n";
        echo "  Token: " . substr($token, 0, 16) . "...\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session token invalid or missing\n";
        $failed++;
    }
    
    // Test 6: Session state
    echo "\nTest 6: Session state\n";
    
    $sessionState = $session->getState();
    $validStates = ['active', 'expired', 'destroyed'];
    
    echo "  Session state: {$sessionState}\n";
    
    if (in_array($sessionState, $validStates)) {
        echo "✅ PASS: Session state is valid\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session state invalid\n";
        $failed++;
    }
    
    // Test 7: Session namespace isolation
    echo "\nTest 7: Session namespace isolation\n";
    
    // Store data in different namespaces
    $session->set('test', 'default_namespace');
    $session->set('test', 'j2store_namespace', 'j2store');
    $session->set('test', 'custom_namespace', 'custom');
    
    // Retrieve from each namespace
    $defaultValue = $session->get('test');
    $j2storeValue = $session->get('test', null, 'j2store');
    $customValue = $session->get('test', null, 'custom');
    
    if ($defaultValue === 'default_namespace' &&
        $j2storeValue === 'j2store_namespace' &&
        $customValue === 'custom_namespace') {
        echo "✅ PASS: Session namespaces properly isolated\n";
        echo "  - Default: {$defaultValue}\n";
        echo "  - J2Store: {$j2storeValue}\n";
        echo "  - Custom: {$customValue}\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session namespace isolation failed\n";
        $failed++;
    }
    
    // Cleanup
    echo "\nCleaning up test data...\n";
    $session->clear('test_data');
    $session->clear('test');
    $session->clear('test', 'j2store');
    $session->clear('test', 'custom');
    echo "✓ Session data cleared\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n=== Session Security Test Summary ===\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n✅ All session security tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some session security tests failed\n";
    exit(1);
}
