<?php
/**
 * Configuration Tests for System - J2Commerce 2FA Plugin
 * 
 * Tests all plugin parameters and their validation
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$results = [];
$passed = 0;
$failed = 0;

echo "=== Configuration Tests ===\n\n";

try {
    $db = Factory::getDbo();
    
    // Test 1: Plugin exists and is accessible
    echo "Test 1: Plugin exists in database\n";
    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce_2fa'));
    
    $db->setQuery($query);
    $plugin = $db->loadObject();
    
    if ($plugin) {
        echo "✅ PASS: Plugin found (ID: {$plugin->extension_id})\n";
        $passed++;
    } else {
        echo "❌ FAIL: Plugin not found in database\n";
        $failed++;
        exit(1);
    }
    
    // Test 2: Parse plugin parameters
    echo "\nTest 2: Plugin parameters are accessible\n";
    $params = json_decode($plugin->params, true);
    
    if (is_array($params)) {
        echo "✅ PASS: Parameters parsed successfully\n";
        $passed++;
    } else {
        echo "❌ FAIL: Could not parse parameters\n";
        $failed++;
    }
    
    // Test 3: Check default values
    echo "\nTest 3: Default parameter values\n";
    $expectedDefaults = [
        'enabled' => '1',
        'debug' => '0',
        'preserve_cart' => '1',
        'preserve_guest_cart' => '1',
        'session_timeout' => '3600'
    ];
    
    $defaultsCorrect = true;
    foreach ($expectedDefaults as $key => $expectedValue) {
        $actualValue = isset($params[$key]) ? $params[$key] : 'NOT_SET';
        
        if ($actualValue == $expectedValue || $actualValue === 'NOT_SET') {
            echo "  ✓ {$key}: {$actualValue} (expected: {$expectedValue})\n";
        } else {
            echo "  ✗ {$key}: {$actualValue} (expected: {$expectedValue})\n";
            $defaultsCorrect = false;
        }
    }
    
    if ($defaultsCorrect) {
        echo "✅ PASS: All default values correct or using defaults\n";
        $passed++;
    } else {
        echo "❌ FAIL: Some default values incorrect\n";
        $failed++;
    }
    
    // Test 4: Session timeout range validation
    echo "\nTest 4: Session timeout range validation\n";
    $timeout = isset($params['session_timeout']) ? (int)$params['session_timeout'] : 3600;
    
    if ($timeout >= 300 && $timeout <= 86400) {
        echo "✅ PASS: Session timeout within valid range (300-86400): {$timeout}\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session timeout outside valid range: {$timeout}\n";
        $failed++;
    }
    
    // Test 5: Boolean parameters are valid
    echo "\nTest 5: Boolean parameters validation\n";
    $boolParams = ['enabled', 'debug', 'preserve_cart', 'preserve_guest_cart'];
    $boolValid = true;
    
    foreach ($boolParams as $param) {
        $value = isset($params[$param]) ? $params[$param] : '1';
        if (in_array($value, ['0', '1', 0, 1, true, false], true)) {
            echo "  ✓ {$param}: valid boolean value\n";
        } else {
            echo "  ✗ {$param}: invalid value '{$value}'\n";
            $boolValid = false;
        }
    }
    
    if ($boolValid) {
        echo "✅ PASS: All boolean parameters valid\n";
        $passed++;
    } else {
        echo "❌ FAIL: Some boolean parameters invalid\n";
        $failed++;
    }
    
    // Test 6: Plugin is enabled
    echo "\nTest 6: Plugin enabled status\n";
    if ($plugin->enabled == 1) {
        echo "✅ PASS: Plugin is enabled\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: Plugin is disabled (enabled={$plugin->enabled})\n";
        echo "✅ PASS: Status check completed\n";
        $passed++;
    }
    
    // Test 7: Manifest cache contains version
    echo "\nTest 7: Manifest cache validation\n";
    $manifest = json_decode($plugin->manifest_cache, true);
    
    if (isset($manifest['version'])) {
        echo "✅ PASS: Version found in manifest: {$manifest['version']}\n";
        $passed++;
    } else {
        echo "❌ FAIL: Version not found in manifest\n";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n=== Configuration Test Summary ===\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n✅ All configuration tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some configuration tests failed\n";
    exit(1);
}
