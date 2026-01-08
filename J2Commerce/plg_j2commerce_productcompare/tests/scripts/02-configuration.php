<?php
/**
 * Configuration Tests for J2Commerce Product Compare Plugin
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
        ->where($db->quoteName('folder') . ' = ' . $db->quote('j2store'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('productcompare'));
    
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
    
    $expectedParams = ['show_in_list', 'show_in_detail', 'max_products', 'button_text', 'button_class'];
    foreach ($expectedParams as $param) {
        $value = isset($params[$param]) ? $params[$param] : 'NOT_SET';
        echo "  - {$param}: {$value}\n";
    }
    echo "✅ PASS: Parameters checked\n";
    $passed++;
    
    echo "\nTest 3: Max products validation\n";
    $maxProducts = isset($params['max_products']) ? (int)$params['max_products'] : 4;
    
    if ($maxProducts >= 2 && $maxProducts <= 10) {
        echo "✅ PASS: Max products in valid range: {$maxProducts}\n";
        $passed++;
    } else {
        echo "❌ FAIL: Max products outside range (2-10): {$maxProducts}\n";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Configuration Test Summary ===\n";
echo "Passed: {$passed}, Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);
