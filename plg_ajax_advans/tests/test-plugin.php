<?php
/**
 * Basic test script for plg_ajax_advans
 * 
 * Tests:
 * 1. Plugin file structure is correct
 * 2. PHP syntax is valid
 * 3. Required classes exist
 */

echo "=== Testing plg_ajax_advans ===\n\n";

$errors = [];
$warnings = [];
$passed = 0;

// Test 1: Check required files exist
echo "Test 1: Checking required files...\n";
$requiredFiles = [
    'advans.xml',
    'services/provider.php',
    'src/Extension/Advans.php',
    'media/js/advans-ajax.js',
    'language/en-GB/plg_ajax_advans.ini',
    'language/de-DE/plg_ajax_advans.ini',
];

$pluginDir = dirname(__DIR__);

foreach ($requiredFiles as $file) {
    $fullPath = $pluginDir . '/' . $file;
    if (file_exists($fullPath)) {
        echo "  ✓ $file exists\n";
        $passed++;
    } else {
        echo "  ✗ $file MISSING\n";
        $errors[] = "Missing file: $file";
    }
}

// Test 2: Check PHP syntax
echo "\nTest 2: Checking PHP syntax...\n";
$phpFiles = [
    'services/provider.php',
    'src/Extension/Advans.php',
];

foreach ($phpFiles as $file) {
    $fullPath = $pluginDir . '/' . $file;
    if (file_exists($fullPath)) {
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "  ✓ $file syntax OK\n";
            $passed++;
        } else {
            echo "  ✗ $file syntax ERROR\n";
            $errors[] = "Syntax error in $file: " . implode("\n", $output);
        }
    }
}

// Test 3: Check XML manifest
echo "\nTest 3: Checking XML manifest...\n";
$xmlPath = $pluginDir . '/advans.xml';
if (file_exists($xmlPath)) {
    $xml = simplexml_load_file($xmlPath);
    if ($xml !== false) {
        echo "  ✓ XML is valid\n";
        $passed++;
        
        // Check required elements
        $requiredElements = ['name', 'version', 'namespace', 'files'];
        foreach ($requiredElements as $element) {
            if (isset($xml->$element) || $xml->attributes()->$element) {
                echo "  ✓ Element '$element' present\n";
                $passed++;
            } else {
                echo "  ✗ Element '$element' missing\n";
                $errors[] = "Missing XML element: $element";
            }
        }
    } else {
        echo "  ✗ XML parse error\n";
        $errors[] = "Could not parse advans.xml";
    }
}

// Test 4: Check JavaScript syntax (basic)
echo "\nTest 4: Checking JavaScript...\n";
$jsPath = $pluginDir . '/media/js/advans-ajax.js';
if (file_exists($jsPath)) {
    $jsContent = file_get_contents($jsPath);
    
    // Check for required functions
    $requiredFunctions = ['advansRemoveCartItem', 'advansSaveProfile'];
    foreach ($requiredFunctions as $func) {
        if (strpos($jsContent, "window.$func") !== false) {
            echo "  ✓ Function '$func' defined\n";
            $passed++;
        } else {
            echo "  ✗ Function '$func' not found\n";
            $errors[] = "Missing JavaScript function: $func";
        }
    }
}

// Test 5: Check language files
echo "\nTest 5: Checking language files...\n";
$langFiles = [
    'language/en-GB/plg_ajax_advans.ini',
    'language/de-DE/plg_ajax_advans.ini',
];

foreach ($langFiles as $file) {
    $fullPath = $pluginDir . '/' . $file;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $lines = explode("\n", $content);
        $validLines = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === ';') continue;
            if (strpos($line, '=') !== false) {
                $validLines++;
            }
        }
        
        if ($validLines > 0) {
            echo "  ✓ $file has $validLines translations\n";
            $passed++;
        } else {
            echo "  ⚠ $file has no translations\n";
            $warnings[] = "No translations in $file";
        }
    }
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: $passed\n";
echo "Errors: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n";

if (count($errors) > 0) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}

if (count($warnings) > 0) {
    echo "\nWarnings:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
}

echo "\n✓ All tests passed!\n";
exit(0);
