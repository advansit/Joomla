#!/usr/bin/env php
<?php
/**
 * Install J2Commerce Import/Export via Joomla's HTTP installer interface
 * This mimics what a user does in the browser
 */

$baseUrl = 'http://localhost';
$adminUser = 'admin';
$adminPass = 'Admin123456789!@#$%';
$packagePath = '/tmp/com_j2commerce_importexport.zip';

echo "=== Installing J2Commerce Import/Export via HTTP ===\n\n";

// Step 1: Login to admin
echo "Step 1: Logging in to Joomla admin...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/administrator/index.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Retry logic for token extraction
$token = null;
$maxRetries = 3;
for ($i = 0; $i < $maxRetries; $i++) {
    $loginPage = curl_exec($ch);
    
    if ($loginPage === false) {
        echo "  Retry " . ($i + 1) . "/$maxRetries: curl error\n";
        sleep(2);
        continue;
    }
    
    // Extract form token
    preg_match('/name="([a-f0-9]{32})" value="1"/', $loginPage, $matches);
    $token = $matches[1] ?? null;
    
    if ($token) {
        break;
    }
    
    echo "  Retry " . ($i + 1) . "/$maxRetries: no token found\n";
    sleep(2);
}

if (!$token) {
    echo "❌ Could not find login token after $maxRetries attempts\n";
    exit(1);
}

echo "  Token: $token\n";

// Submit login
curl_setopt($ch, CURLOPT_URL, "$baseUrl/administrator/index.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => $adminUser,
    'passwd' => $adminPass,
    'option' => 'com_login',
    'task' => 'login',
    'return' => base64_encode('index.php'),
    $token => '1'
]));
$loginResult = curl_exec($ch);

if (strpos($loginResult, 'task=logout') !== false || strpos($loginResult, 'com_cpanel') !== false) {
    echo "✅ Login successful\n\n";
} else {
    echo "❌ Login failed\n";
    exit(1);
}

// Step 2: Get installer page
echo "Step 2: Accessing installer...\n";
curl_setopt($ch, CURLOPT_URL, "$baseUrl/administrator/index.php?option=com_installer&view=install");
curl_setopt($ch, CURLOPT_POST, false);
$installerPage = curl_exec($ch);

// Extract new token
preg_match('/name="([a-f0-9]{32})" value="1"/', $installerPage, $matches);
$token = $matches[1] ?? null;

if (!$token) {
    echo "❌ Could not find installer token\n";
    exit(1);
}

echo "  Token: $token\n";

// Step 3: Upload and install package
echo "\nStep 3: Uploading package...\n";

if (!file_exists($packagePath)) {
    echo "❌ Package not found: $packagePath\n";
    exit(1);
}

$cfile = new CURLFile($packagePath, 'application/zip', 'com_j2commerce_importexport.zip');

curl_setopt($ch, CURLOPT_URL, "$baseUrl/administrator/index.php?option=com_installer&view=install");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'install_package' => $cfile,
    'type' => 'upload',
    'installtype' => 'upload',
    $token => '1',
    'task' => 'install.install',
    'option' => 'com_installer'
]);

$installResult = curl_exec($ch);
curl_close($ch);

// Check result
if (strpos($installResult, 'was successful') !== false || 
    strpos($installResult, 'Installation of the package was successful') !== false ||
    strpos($installResult, 'successfully installed') !== false) {
    echo "✅ Installation successful!\n\n";
    
    // Extract messages
    if (preg_match_all('/<div class="alert[^>]*>(.*?)<\/div>/s', $installResult, $messages)) {
        echo "Installation messages:\n";
        foreach ($messages[1] as $msg) {
            $cleanMsg = strip_tags($msg);
            $cleanMsg = trim(preg_replace('/\s+/', ' ', $cleanMsg));
            if (!empty($cleanMsg)) {
                echo "  - $cleanMsg\n";
            }
        }
    }
    
    exit(0);
} else {
    echo "❌ Installation may have failed\n";
    
    // Try to extract error messages
    if (preg_match_all('/<div class="alert[^>]*alert-error[^>]*>(.*?)<\/div>/s', $installResult, $errors)) {
        echo "\nErrors:\n";
        foreach ($errors[1] as $error) {
            $cleanError = strip_tags($error);
            $cleanError = trim(preg_replace('/\s+/', ' ', $cleanError));
            if (!empty($cleanError)) {
                echo "  - $cleanError\n";
            }
        }
    }
    
    // Save full output for debugging
    file_put_contents('/tmp/install-result.html', $installResult);
    echo "\nFull output saved to /tmp/install-result.html\n";
    
    exit(1);
}
