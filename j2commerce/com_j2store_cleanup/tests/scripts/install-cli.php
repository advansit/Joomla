#!/usr/bin/env php
<?php
/**
 * Direct installation without Factory::getApplication
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/cli/install.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Installer\Installer;

$extensionName = getenv('EXTENSION_NAME') ?: 'Extension';
$packagePath = '/tmp/extension.zip';

echo "=== Installing $extensionName ===\n\n";

if (!file_exists($packagePath)) {
    echo "❌ Package not found: $packagePath\n";
    exit(1);
}

echo "Package: $packagePath\n";
echo "Size: " . round(filesize($packagePath) / 1024, 2) . " KB\n\n";

try {
    $installer = Installer::getInstance();
    
    echo "Installing extension...\n";
    $result = $installer->install($packagePath);
    
    if ($result) {
        echo "✅ Installation successful!\n";
        exit(0);
    } else {
        echo "❌ Installation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}
