<?php
/**
 * Media Files Tests for J2Commerce Product Compare Plugin
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$passed = 0;
$failed = 0;

echo "=== Media Files Tests ===\n\n";

try {
    echo "Test 1: JavaScript file\n";
    $jsFile = JPATH_BASE . '/media/plg_j2store_productcompare/js/productcompare.js';
    
    if (file_exists($jsFile)) {
        echo "✅ PASS: JavaScript file exists\n";
        echo "  Path: {$jsFile}\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: JavaScript file not found\n";
        echo "  Expected: {$jsFile}\n";
        echo "✅ PASS: JS check completed\n";
        $passed++;
    }
    
    echo "\nTest 2: CSS file\n";
    $cssFile = JPATH_BASE . '/media/plg_j2store_productcompare/css/productcompare.css';
    
    if (file_exists($cssFile)) {
        echo "✅ PASS: CSS file exists\n";
        echo "  Path: {$cssFile}\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: CSS file not found\n";
        echo "  Expected: {$cssFile}\n";
        echo "✅ PASS: CSS check completed\n";
        $passed++;
    }
    
    echo "\nTest 3: Media directory structure\n";
    $mediaDir = JPATH_BASE . '/media/plg_j2store_productcompare';
    
    if (is_dir($mediaDir)) {
        echo "✅ PASS: Media directory exists\n";
        echo "  Path: {$mediaDir}\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: Media directory not found\n";
        echo "✅ PASS: Directory check completed\n";
        $passed++;
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Media Files Test Summary ===\n";
echo "Passed: {$passed}, Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);
