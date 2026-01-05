<?php
/**
 * Test extension uninstallation
 */

// Set CLI environment variables for Joomla
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/cli/test.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

$results = [];

try {
    $app = Factory::getApplication('administrator');
    $db = Factory::getDbo();
    
    // Get plugin extension ID
    $query = $db->getQuery(true)
        ->select('extension_id')
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('j2commerce'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('privacy'));
    
    $db->setQuery($query);
    $extensionId = $db->loadResult();
    
    if (!$extensionId) {
        throw new Exception("Plugin not found for uninstallation");
    }
    
    $results[] = "Found plugin with ID: $extensionId";
    
    // Uninstall
    $installer = Installer::getInstance();
    
    if ($installer->uninstall('plugin', $extensionId)) {
        $results[] = "✅ Plugin uninstalled successfully";
    } else {
        throw new Exception("Uninstallation failed: " . $installer->getError());
    }
    
    // Verify plugin is removed from database
    $db->setQuery($query);
    $stillExists = $db->loadResult();
    
    if (!$stillExists) {
        $results[] = "✅ Plugin removed from database";
    } else {
        $results[] = "❌ Plugin still in database";
        throw new Exception("Plugin not removed from database");
    }
    
    // Verify files are removed
    $pluginPath = JPATH_PLUGINS . '/privacy/j2commerce';
    if (!is_dir($pluginPath)) {
        $results[] = "✅ Plugin files removed";
    } else {
        $results[] = "❌ Plugin files still exist";
        throw new Exception("Plugin files not removed");
    }
    
    echo implode("\n", $results) . "\n";
    echo "\n✅ Uninstallation tests passed\n";
    exit(0);
    
} catch (Exception $e) {
    echo implode("\n", $results) . "\n";
    echo "\n❌ Uninstallation tests failed: " . $e->getMessage() . "\n";
    exit(1);
}
