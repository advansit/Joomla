<?php
/**
 * Install a Joomla extension using the real Joomla Installer API.
 * Usage: php install-extension.php /path/to/extension.zip
 *
 * This ensures the XML manifest is fully parsed and validated,
 * catching issues like missing folders that cp-based installs miss.
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';

// Suppress notices during bootstrap
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;

$zipPath = $argv[1] ?? null;

if (!$zipPath || !file_exists($zipPath)) {
    fwrite(STDERR, "Usage: php install-extension.php /path/to/extension.zip\n");
    exit(2);
}

echo "Installing extension from: $zipPath\n";

// Boot the application
$app = Factory::getApplication('administrator');

// Extract the package
$package = InstallerHelper::unpack($zipPath);

if (!$package || !is_array($package)) {
    fwrite(STDERR, "ERROR: Failed to unpack $zipPath\n");
    exit(1);
}

echo "Package extracted to: {$package['dir']}\n";
echo "Package type: {$package['type']}\n";

// Install using the real Joomla Installer
$installer = Installer::getInstance();
$result = $installer->install($package['dir']);

// Clean up extracted files
InstallerHelper::cleanupInstall($zipPath, $package['extractdir'] ?? $package['dir']);

if ($result) {
    echo "Extension installed successfully via Joomla Installer\n";
    exit(0);
} else {
    fwrite(STDERR, "ERROR: Joomla Installer returned failure\n");
    // Print any messages from the installer
    $messages = $app->getMessageQueue();
    foreach ($messages as $msg) {
        fwrite(STDERR, "[{$msg['type']}] {$msg['message']}\n");
    }
    exit(1);
}
