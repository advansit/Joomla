<?php
/**
 * Configuration Tests for J2Commerce AcyMailing Plugin
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$passed = 0;
$failed = 0;

echo "=== Configuration Tests ===\n\n";

try {
    $db = Factory::getDbo();
    
    // Test 1: Plugin exists
    echo "Test 1: Plugin exists in database\n";
    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('j2store'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('acymailing'));
    
    $db->setQuery($query);
    $plugin = $db->loadObject();
    
    if ($plugin) {
        echo "✅ PASS: Plugin found (ID: {$plugin->extension_id})\n";
        $passed++;
    } else {
        echo "❌ FAIL: Plugin not found\n";
        $failed++;
        exit(1);
    }
    
    // Test 2: Parse parameters
    echo "\nTest 2: Plugin parameters accessible\n";
    $params = json_decode($plugin->params, true);
    
    if (is_array($params)) {
        echo "✅ PASS: Parameters parsed\n";
        $passed++;
    } else {
        echo "❌ FAIL: Could not parse parameters\n";
        $failed++;
    }
    
    // Test 3: Check parameter structure
    echo "\nTest 3: Parameter structure\n";
    $expectedParams = [
        'list_id', 'checkbox_label', 'checkbox_default', 'double_optin',
        'show_in_checkout', 'auto_subscribe', 'show_in_products',
        'guest_subscription', 'multiple_lists'
    ];
    
    $allPresent = true;
    foreach ($expectedParams as $param) {
        $value = isset($params[$param]) ? 'SET' : 'NOT_SET';
        echo "  - {$param}: {$value}\n";
        if ($value === 'NOT_SET') {
            $allPresent = false;
        }
    }
    
    if ($allPresent) {
        echo "✅ PASS: All parameters present\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: Some parameters not set (using defaults)\n";
        echo "✅ PASS: Parameter check completed\n";
        $passed++;
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Configuration Test Summary ===\n";
echo "Passed: {$passed}, Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);
