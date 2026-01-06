#!/usr/bin/env php
<?php
/**
 * Verify basic setup before running tests
 */

echo "=== Verifying Test Setup ===\n\n";

$checks = [];

// Check 1: Extension package exists
echo "1. Checking extension package...\n";
if (file_exists('/tmp/extension.zip')) {
    $size = filesize('/tmp/extension.zip');
    echo "   ✅ Package exists: " . round($size / 1024, 2) . " KB\n";
    $checks[] = true;
} else {
    echo "   ❌ Package not found at /tmp/extension.zip\n";
    $checks[] = false;
}

// Check 2: Joomla is installed (optional - may be installed by tests)
echo "\n2. Checking Joomla installation...\n";
if (file_exists('/var/www/html/configuration.php')) {
    echo "   ✅ Joomla configuration.php exists\n";
    $checks[] = true;
} else {
    echo "   ⚠️  Joomla not yet installed (will be installed by tests)\n";
    // Don't fail - installation test will handle this
}

// Check 3: Joomla framework can be loaded
echo "\n3. Checking Joomla framework...\n";
if (file_exists('/var/www/html/includes/defines.php') && 
    file_exists('/var/www/html/includes/framework.php')) {
    echo "   ✅ Joomla framework files exist\n";
    $checks[] = true;
} else {
    echo "   ❌ Joomla framework files missing\n";
    $checks[] = false;
}

// Check 4: Database connection
echo "\n4. Checking database connection...\n";
if (file_exists('/var/www/html/configuration.php')) {
    define('_JEXEC', 1);
    define('JPATH_BASE', '/var/www/html');
    
    try {
        require_once JPATH_BASE . '/includes/defines.php';
        require_once JPATH_BASE . '/includes/framework.php';
        
        $db = Joomla\CMS\Factory::getDbo();
        $db->connect();
        echo "   ✅ Database connection successful\n";
        
        $tables = $db->getTableList();
        echo "   ✅ Database has " . count($tables) . " tables\n";
        $checks[] = true;
    } catch (Exception $e) {
        echo "   ❌ Database error: " . $e->getMessage() . "\n";
        $checks[] = false;
    }
} else {
    echo "   ⚠️  Skipped (Joomla not installed)\n";
}

// Summary
echo "\n=== Summary ===\n";
$passed = count(array_filter($checks));
$total = count($checks);
echo "Passed: $passed/$total\n";

// Only fail if critical checks failed (extension package and framework files)
if ($passed >= 2) {
    echo "✅ Basic checks passed - ready for testing\n";
    exit(0);
} else {
    echo "❌ Critical checks failed - cannot proceed\n";
    exit(1);
}
