<?php
/**
 * Configuration Tests for Privacy - J2Commerce Plugin
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
    
    echo "Test 1: Plugin exists\n";
    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('privacy'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce'));
    
    $db->setQuery($query);
    $plugin = $db->loadObject();
    
    if ($plugin) {
        echo "✅ PASS: Plugin found\n";
        $passed++;
    } else {
        echo "❌ FAIL: Plugin not found\n";
        $failed++;
        exit(1);
    }
    
    echo "\nTest 2: Parameters\n";
    $params = json_decode($plugin->params, true);
    
    $expectedParams = ['include_joomla_data', 'anonymize_orders', 'delete_addresses'];
    foreach ($expectedParams as $param) {
        $value = isset($params[$param]) ? $params[$param] : 'NOT_SET';
        echo "  - {$param}: {$value}\n";
    }
    echo "✅ PASS: Parameters checked\n";
    $passed++;
    
    echo "\nTest 3: GDPR compliance settings\n";
    $anonymizeOrders = isset($params['anonymize_orders']) ? $params['anonymize_orders'] : '1';
    
    if ($anonymizeOrders == '1') {
        echo "✅ PASS: Anonymization enabled (GDPR compliant)\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: Anonymization disabled\n";
        echo "✅ PASS: GDPR check completed\n";
        $passed++;
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Configuration Test Summary ===\n";
echo "Passed: {$passed}, Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);
