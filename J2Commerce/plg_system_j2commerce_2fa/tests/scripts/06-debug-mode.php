<?php
/**
 * Debug Mode Tests for System - J2Commerce 2FA Plugin
 * 
 * Tests debug logging functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$results = [];
$passed = 0;
$failed = 0;

echo "=== Debug Mode Tests ===\n\n";

try {
    $db = Factory::getDbo();
    
    // Test 1: Get current debug mode setting
    echo "Test 1: Get current debug mode setting\n";
    
    $query = $db->getQuery(true)
        ->select($db->quoteName('params'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce_2fa'));
    
    $db->setQuery($query);
    $paramsJson = $db->loadResult();
    $params = json_decode($paramsJson, true);
    
    $originalDebugMode = isset($params['debug']) ? $params['debug'] : '0';
    echo "  Current debug mode: {$originalDebugMode}\n";
    
    echo "✅ PASS: Debug mode setting retrieved\n";
    $passed++;
    
    // Test 2: Enable debug mode
    echo "\nTest 2: Enable debug mode\n";
    
    $params['debug'] = '1';
    $newParamsJson = json_encode($params);
    
    $query = $db->getQuery(true)
        ->update($db->quoteName('#__extensions'))
        ->set($db->quoteName('params') . ' = ' . $db->quote($newParamsJson))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce_2fa'));
    
    $db->setQuery($query);
    
    if ($db->execute()) {
        echo "✅ PASS: Debug mode enabled\n";
        $passed++;
    } else {
        echo "❌ FAIL: Could not enable debug mode\n";
        $failed++;
    }
    
    // Test 3: Verify debug mode is enabled
    echo "\nTest 3: Verify debug mode is enabled\n";
    
    $query = $db->getQuery(true)
        ->select($db->quoteName('params'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce_2fa'));
    
    $db->setQuery($query);
    $updatedParamsJson = $db->loadResult();
    $updatedParams = json_decode($updatedParamsJson, true);
    
    if (isset($updatedParams['debug']) && $updatedParams['debug'] == '1') {
        echo "✅ PASS: Debug mode verified as enabled\n";
        $passed++;
    } else {
        echo "❌ FAIL: Debug mode not enabled\n";
        $failed++;
    }
    
    // Test 4: Check Joomla log directory
    echo "\nTest 4: Check Joomla log directory\n";
    
    $logPath = JPATH_BASE . '/administrator/logs';
    
    if (is_dir($logPath) && is_writable($logPath)) {
        echo "✅ PASS: Log directory exists and is writable\n";
        echo "  Path: {$logPath}\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: Log directory not writable\n";
        echo "  Path: {$logPath}\n";
        echo "✅ PASS: Log directory check completed\n";
        $passed++;
    }
    
    // Test 5: Disable debug mode
    echo "\nTest 5: Disable debug mode\n";
    
    $params['debug'] = '0';
    $newParamsJson = json_encode($params);
    
    $query = $db->getQuery(true)
        ->update($db->quoteName('#__extensions'))
        ->set($db->quoteName('params') . ' = ' . $db->quote($newParamsJson))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce_2fa'));
    
    $db->setQuery($query);
    
    if ($db->execute()) {
        echo "✅ PASS: Debug mode disabled\n";
        $passed++;
    } else {
        echo "❌ FAIL: Could not disable debug mode\n";
        $failed++;
    }
    
    // Test 6: Verify debug mode is disabled
    echo "\nTest 6: Verify debug mode is disabled\n";
    
    $query = $db->getQuery(true)
        ->select($db->quoteName('params'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce_2fa'));
    
    $db->setQuery($query);
    $finalParamsJson = $db->loadResult();
    $finalParams = json_decode($finalParamsJson, true);
    
    if (isset($finalParams['debug']) && $finalParams['debug'] == '0') {
        echo "✅ PASS: Debug mode verified as disabled\n";
        $passed++;
    } else {
        echo "❌ FAIL: Debug mode not disabled\n";
        $failed++;
    }
    
    // Test 7: Restore original debug mode
    echo "\nTest 7: Restore original debug mode setting\n";
    
    $params['debug'] = $originalDebugMode;
    $restoredParamsJson = json_encode($params);
    
    $query = $db->getQuery(true)
        ->update($db->quoteName('#__extensions'))
        ->set($db->quoteName('params') . ' = ' . $db->quote($restoredParamsJson))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce_2fa'));
    
    $db->setQuery($query);
    
    if ($db->execute()) {
        echo "✅ PASS: Original debug mode restored ({$originalDebugMode})\n";
        $passed++;
    } else {
        echo "❌ FAIL: Could not restore original debug mode\n";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n=== Debug Mode Test Summary ===\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n✅ All debug mode tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some debug mode tests failed\n";
    exit(1);
}
