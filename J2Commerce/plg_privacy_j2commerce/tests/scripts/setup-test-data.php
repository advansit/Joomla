<?php
/**
 * Setup test data for J2Commerce privacy tests
 * Creates test user, orders, and addresses
 */

// Set CLI environment variables for Joomla
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/cli/setup.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

$results = [];

try {
    $app = Factory::getApplication('administrator');
    $db = Factory::getDbo();
    
    // Create test user
    $testUser = new User();
    $userData = [
        'name' => 'Privacy Test User',
        'username' => 'privacytest',
        'email' => 'privacytest@example.com',
        'password' => 'TestPassword123!',
        'password2' => 'TestPassword123!',
        'block' => 0,
        'groups' => [2], // Registered
    ];
    
    if (!$testUser->bind($userData)) {
        throw new Exception("Failed to bind user data");
    }
    
    if (!$testUser->save()) {
        throw new Exception("Failed to save user");
    }
    
    $userId = $testUser->id;
    $results[] = "✅ Test user created (ID: $userId)";
    
    // J2Commerce tables should already exist from J2Commerce installation
    // Just verify they exist
    $tables = $db->getTableList();
    $prefix = $db->getPrefix();
    $hasOrders = in_array($prefix . 'j2store_orders', $tables);
    $hasOrderItems = in_array($prefix . 'j2store_orderitems', $tables);
    
    if (!$hasOrders || !$hasOrderItems) {
        throw new Exception("J2Commerce tables not found. J2Commerce may not be installed correctly.");
    }
    
    $results[] = "✅ J2Commerce tables verified";
    
    // Insert test orders (J2Commerce structure)
    $orders = [
        [
            'user_id' => $userId,
            'order_state' => 'confirmed',
            'order_state_id' => 5,
            'billing_first_name' => 'John',
            'billing_last_name' => 'Doe',
            'billing_email' => 'john.doe@example.com',
            'billing_phone' => '+41 44 123 45 67',
            'billing_address_1' => 'Teststrasse 123',
            'billing_address_2' => '',
            'billing_city' => 'Zürich',
            'billing_zip' => '8000',
            'billing_country_id' => 41,
            'billing_zone_id' => 2,
            'shipping_first_name' => 'John',
            'shipping_last_name' => 'Doe',
            'shipping_phone' => '+41 44 123 45 67',
            'shipping_address_1' => 'Teststrasse 123',
            'shipping_address_2' => '',
            'shipping_city' => 'Zürich',
            'shipping_zip' => '8000',
            'shipping_country_id' => 41,
            'shipping_zone_id' => 2,
            'order_total' => 1299.00,
            'order_subtotal' => 1199.00,
            'order_tax' => 100.00,
            'currency_code' => 'CHF',
            'currency_value' => 1.00,
            'created_on' => date('Y-m-d H:i:s'),
            'modified_on' => date('Y-m-d H:i:s'),
        ],
        [
            'user_id' => $userId,
            'order_state' => 'pending',
            'order_state_id' => 1,
            'billing_first_name' => 'John',
            'billing_last_name' => 'Doe',
            'billing_email' => 'john.doe@example.com',
            'billing_phone' => '+41 44 123 45 67',
            'billing_address_1' => 'Teststrasse 123',
            'billing_address_2' => '',
            'billing_city' => 'Zürich',
            'billing_zip' => '8000',
            'billing_country_id' => 41,
            'billing_zone_id' => 2,
            'shipping_first_name' => 'John',
            'shipping_last_name' => 'Doe',
            'shipping_phone' => '+41 44 123 45 67',
            'shipping_address_1' => 'Teststrasse 123',
            'shipping_address_2' => '',
            'shipping_city' => 'Zürich',
            'shipping_zip' => '8000',
            'shipping_country_id' => 41,
            'shipping_zone_id' => 2,
            'order_total' => 599.00,
            'order_subtotal' => 549.00,
            'order_tax' => 50.00,
            'currency_code' => 'CHF',
            'currency_value' => 1.00,
            'created_on' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'modified_on' => date('Y-m-d H:i:s', strtotime('-7 days')),
        ]
    ];
    
    $orderIds = [];
    foreach ($orders as $order) {
        $db->insertObject('#__j2store_orders', (object)$order);
        $orderIds[] = $db->insertid();
    }
    $results[] = "✅ Test orders created (2 orders)";
    
    // Insert test order items
    $orderItems = [
        [
            'order_id' => $orderIds[0],
            'product_id' => 1,
            'variant_id' => 0,
            'orderitem_sku' => 'TEST-PRODUCT-001',
            'orderitem_name' => 'Test Product 1',
            'orderitem_quantity' => 1,
            'orderitem_price' => 1199.00,
            'orderitem_final_price' => 1199.00,
            'orderitem_tax' => 100.00,
        ],
        [
            'order_id' => $orderIds[1],
            'product_id' => 2,
            'variant_id' => 0,
            'orderitem_sku' => 'TEST-PRODUCT-002',
            'orderitem_name' => 'Test Product 2',
            'orderitem_quantity' => 1,
            'orderitem_price' => 549.00,
            'orderitem_final_price' => 549.00,
            'orderitem_tax' => 50.00,
        ]
    ];
    
    foreach ($orderItems as $item) {
        $db->insertObject('#__j2store_orderitems', (object)$item);
    }
    $results[] = "✅ Test order items created (2 items)";
    
    // Store test user ID for later tests
    file_put_contents('/tmp/test_user_id.txt', $userId);
    $results[] = "✅ Test user ID saved: $userId";
    
    echo implode("\n", $results) . "\n";
    echo "\n✅ Test data setup complete\n";
    exit(0);
    
} catch (Exception $e) {
    echo implode("\n", $results) . "\n";
    echo "\n❌ Test data setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
