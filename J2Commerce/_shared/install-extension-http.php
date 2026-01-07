#!/usr/bin/env php
<?php
/**
 * Install J2Commerce Extension via Joomla's HTTP installer interface
 * Generic script that works for all extension types (components, plugins, modules)
 */

$baseUrl = 'http://localhost';
$adminUser = getenv('JOOMLA_ADMIN_USERNAME') ?: 'admin';
$adminPass = getenv('JOOMLA_ADMIN_PASSWORD') ?: 'Admin123!@#';
$packagePath = '/tmp/extension.zip';
$extensionName = getenv('EXTENSION_NAME') ?: 'Unknown Extension';

echo "=== Installing $extensionName via HTTP ===\n\n";

if (!file_exists($packagePath)) {
    echo "❌ Package not found: $packagePath\n";
    exit(1);
}

echo "Package: $packagePath (" . round(filesize($packagePath) / 1024, 2) . " KB)\n\n";

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
$maxRetries = 5;
for ($i = 0; $i < $maxRetries; $i++) {
    $loginPage = curl_exec($ch);
    
    if ($loginPage === false) {
        echo "  Retry " . ($i + 1) . "/$maxRetries: curl error - " . curl_error($ch) . "\n";
        sleep(3);
        continue;
    }
    
    // Extract form token (Joomla 4/5 format)
    if (preg_match('/name="([a-f0-9]{32})" value="1"/', $loginPage, $matches)) {
        $token = $matches[1];
        break;
    }
    
    echo "  Retry " . ($i + 1) . "/$maxRetries: no token found\n";
    sleep(3);
}

if (!$token) {
    echo "❌ Could not find login token after $maxRetries attempts\n";
    exit(1);
}

echo "  ✅ Token extracted: $token\n";

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
    echo "  ✅ Login successful\n\n";
} else {
    echo "❌ Login failed\n";
    echo "Response preview: " . substr($loginResult, 0, 500) . "...\n";
    exit(1);
}

// Step 2: Get installer page
echo "Step 2: Accessing installer...\n";
curl_setopt($ch, CURLOPT_URL, "$baseUrl/administrator/index.php?option=com_installer&view=install");
curl_setopt($ch, CURLOPT_POST, false);
$installerPage = curl_exec($ch);

// Extract new token
if (!preg_match('/name="([a-f0-9]{32})" value="1"/', $installerPage, $matches)) {
    echo "❌ Could not find installer token\n";
    exit(1);
}
$token = $matches[1];
echo "  ✅ Token extracted: $token\n";

// Step 3: Upload and install package
echo "\nStep 3: Uploading and installing package...\n";

$boundary = '----WebKitFormBoundary' . uniqid();
$postData = '';

// Add token
$postData .= "--$boundary\r\n";
$postData .= "Content-Disposition: form-data; name=\"$token\"\r\n\r\n";
$postData .= "1\r\n";

// Add task
$postData .= "--$boundary\r\n";
$postData .= "Content-Disposition: form-data; name=\"task\"\r\n\r\n";
$postData .= "install.install\r\n";

// Add option
$postData .= "--$boundary\r\n";
$postData .= "Content-Disposition: form-data; name=\"option\"\r\n\r\n";
$postData .= "com_installer\r\n";

// Add install type
$postData .= "--$boundary\r\n";
$postData .= "Content-Disposition: form-data; name=\"installtype\"\r\n\r\n";
$postData .= "upload\r\n";

// Add file
$postData .= "--$boundary\r\n";
$postData .= "Content-Disposition: form-data; name=\"install_package\"; filename=\"extension.zip\"\r\n";
$postData .= "Content-Type: application/zip\r\n\r\n";
$postData .= file_get_contents($packagePath) . "\r\n";
$postData .= "--$boundary--\r\n";

curl_setopt($ch, CURLOPT_URL, "$baseUrl/administrator/index.php?option=com_installer&view=install");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: multipart/form-data; boundary=$boundary",
    "Content-Length: " . strlen($postData)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$installResult = curl_exec($ch);

if ($installResult === false) {
    echo "❌ Installation request failed: " . curl_error($ch) . "\n";
    exit(1);
}

// Check for success indicators
$success = false;
$successIndicators = [
    'Installation of the',
    'was successful',
    'successfully installed',
    'Installation successful',
    'alert-success'
];

foreach ($successIndicators as $indicator) {
    if (stripos($installResult, $indicator) !== false) {
        $success = true;
        break;
    }
}

// Check for error indicators
$errorIndicators = [
    'Installation failed',
    'Error installing',
    'alert-error',
    'alert-danger',
    'Installation of the package failed'
];

foreach ($errorIndicators as $indicator) {
    if (stripos($installResult, $indicator) !== false) {
        $success = false;
        echo "❌ Installation failed - error indicator found: $indicator\n";
        
        // Try to extract error message
        if (preg_match('/<div[^>]*alert[^>]*>(.*?)<\/div>/is', $installResult, $matches)) {
            $errorMsg = strip_tags($matches[1]);
            echo "Error message: " . trim($errorMsg) . "\n";
        }
        break;
    }
}

curl_close($ch);

if ($success) {
    echo "✅ Installation successful\n";
    exit(0);
} else {
    echo "⚠️  Installation status unclear - check manually\n";
    echo "Response length: " . strlen($installResult) . " bytes\n";
    exit(1);
}
