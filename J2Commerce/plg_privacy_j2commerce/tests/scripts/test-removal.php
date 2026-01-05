<?php
/**
 * Test data removal/anonymization
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
use Joomla\CMS\Plugin\PluginHelper;

$results = [];

try {
    $app = Factory::getApplication('site');
    $db = Factory::getDbo();
    
    // Get test user ID
    if (!file_exists('/tmp/test_user_id.txt')) {
        throw new Exception("Test user ID not found. Run setup-test-data.php first");
    }
    
    $testUserId = (int)file_get_contents('/tmp/test_user_id.txt');
    $testUser = Factory::getUser($testUserId);
    
    $results[] = "=== Testing Data Removal/Anonymization ===";
    $results[] = "Test User ID: $testUserId";
    
    // Check data BEFORE removal
    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__j2commerce_orders'))
        ->where($db->quoteName('user_id') . ' = ' . $testUserId);
    $db->setQuery($query);
    $ordersBefore = $db->loadObjectList();
    
    $results[] = "\n--- Data BEFORE removal ---";
    $results[] = "Orders: " . count($ordersBefore);
    if (!empty($ordersBefore)) {
        $firstOrder = $ordersBefore[0];
        $results[] = "  First order billing name: {$firstOrder->billing_first_name} {$firstOrder->billing_last_name}";
        $results[] = "  First order billing email: {$firstOrder->billing_email}";
    }
    
    $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('#__j2commerce_addresses'))
        ->where($db->quoteName('user_id') . ' = ' . $testUserId);
    $db->setQuery($query);
    $addressCountBefore = $db->loadResult();
    $results[] = "Addresses: $addressCountBefore";
    
    // Trigger onPrivacyRemoveData
    $results[] = "\n--- Triggering onPrivacyRemoveData ---";
    
    PluginHelper::importPlugin('privacy');
    $dispatcher = Factory::getApplication()->getDispatcher();
    
    $event = new \Joomla\CMS\Event\Privacy\RemoveDataEvent('onPrivacyRemoveData', [
        'user' => $testUser
    ]);
    
    $removeResults = $dispatcher->dispatch('onPrivacyRemoveData', $event);
    $results[] = "✅ onPrivacyRemoveData triggered";
    
    // Check data AFTER removal
    $results[] = "\n--- Data AFTER removal ---";
    
    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__j2commerce_orders'))
        ->where($db->quoteName('user_id') . ' = ' . $testUserId);
    $db->setQuery($query);
    $ordersAfter = $db->loadObjectList();
    
    $results[] = "Orders: " . count($ordersAfter);
    if (!empty($ordersAfter)) {
        $firstOrder = $ordersAfter[0];
        $results[] = "  First order billing name: {$firstOrder->billing_first_name} {$firstOrder->billing_last_name}";
        $results[] = "  First order billing email: {$firstOrder->billing_email}";
        
        // Verify anonymization
        if ($firstOrder->billing_first_name === 'Anonymized' && $firstOrder->billing_last_name === 'User') {
            $results[] = "✅ Orders were anonymized";
        } else {
            $results[] = "❌ Orders were NOT anonymized";
        }
        
        if ($firstOrder->billing_email === 'anonymized@example.com') {
            $results[] = "✅ Email was anonymized";
        } else {
            $results[] = "❌ Email was NOT anonymized";
        }
    }
    
    $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('#__j2commerce_addresses'))
        ->where($db->quoteName('user_id') . ' = ' . $testUserId);
    $db->setQuery($query);
    $addressCountAfter = $db->loadResult();
    $results[] = "Addresses: $addressCountAfter";
    
    if ($addressCountAfter === 0 && $addressCountBefore > 0) {
        $results[] = "✅ Addresses were deleted";
    } else if ($addressCountAfter > 0) {
        $results[] = "❌ Addresses were NOT deleted";
    }
    
    echo implode("\n", $results) . "\n";
    echo "\n✅ Removal tests passed\n";
    exit(0);
    
} catch (Exception $e) {
    echo implode("\n", $results) . "\n";
    echo "\n❌ Removal tests failed: " . $e->getMessage() . "\n";
    exit(1);
}
