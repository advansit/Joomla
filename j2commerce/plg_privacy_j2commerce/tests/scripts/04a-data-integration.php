<?php
/**
 * J2Commerce Privacy Plugin - Data Integration Tests
 * Tests actual data operations with mock J2Commerce tables
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class DataIntegrationTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $testUserId = 100; // From mock data

    public function __construct()
    {
        $this->db = Factory::getContainer()->get('DatabaseDriver');
    }

    public function test($name, $condition)
    {
        if ($condition) {
            echo "✓ $name\n";
            $this->passed++;
        } else {
            echo "✗ $name\n";
            $this->failed++;
        }
        return $condition;
    }

    public function run()
    {
        echo "\n========================================\n";
        echo "J2Commerce Data Integration Tests\n";
        echo "========================================\n\n";

        // Test 1: Verify test data exists
        echo "--- Test Data Verification ---\n";
        
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__j2store_addresses'))
            ->where('user_id = ' . $this->testUserId);
        $this->db->setQuery($query);
        $addressCount = (int) $this->db->loadResult();
        $this->test('Test user has addresses', $addressCount >= 2);

        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__j2store_orders'))
            ->where('user_id = ' . $this->testUserId);
        $this->db->setQuery($query);
        $orderCount = (int) $this->db->loadResult();
        $this->test('Test user has orders', $orderCount >= 2);

        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__j2store_carts'))
            ->where('user_id = ' . $this->testUserId);
        $this->db->setQuery($query);
        $cartCount = (int) $this->db->loadResult();
        $this->test('Test user has cart', $cartCount >= 1);

        // Test 2: Retention period logic
        echo "\n--- Retention Period Tests ---\n";
        
        $retentionYears = 10;
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionYears} years"));
        
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__j2store_orders'))
            ->where('user_id = ' . $this->testUserId)
            ->where('created_on >= ' . $this->db->quote($cutoffDate));
        $this->db->setQuery($query);
        $recentOrders = (int) $this->db->loadResult();
        $this->test('Orders within retention period found', $recentOrders >= 1);

        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__j2store_orders'))
            ->where('user_id = ' . $this->testUserId)
            ->where('created_on < ' . $this->db->quote($cutoffDate));
        $this->db->setQuery($query);
        $oldOrders = (int) $this->db->loadResult();
        $this->test('Orders outside retention period found', $oldOrders >= 1);

        // Test 3: Address deletion simulation
        echo "\n--- Address Operations Tests ---\n";
        
        // Insert a test address to delete
        $testAddress = (object) [
            'user_id' => 999,
            'first_name' => 'Delete',
            'last_name' => 'Test',
            'address_1' => 'To Be Deleted',
            'city' => 'Test City',
            'zip' => '12345',
            'email' => 'delete@test.com',
            'type' => 'billing'
        ];
        $this->db->insertObject('#__j2store_addresses', $testAddress, 'j2store_address_id');
        $insertedId = $this->db->insertid();
        $this->test('Test address inserted', $insertedId > 0);

        // Delete the test address
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__j2store_addresses'))
            ->where('j2store_address_id = ' . $insertedId);
        $this->db->setQuery($query);
        $deleted = $this->db->execute();
        $this->test('Test address deleted', $deleted !== false);

        // Verify deletion
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__j2store_addresses'))
            ->where('j2store_address_id = ' . $insertedId);
        $this->db->setQuery($query);
        $count = (int) $this->db->loadResult();
        $this->test('Address deletion verified', $count === 0);

        // Test 4: Cart deletion simulation
        echo "\n--- Cart Operations Tests ---\n";
        
        // Insert test cart
        $testCart = (object) [
            'user_id' => 999,
            'cart_type' => 'cart',
            'created_on' => date('Y-m-d H:i:s')
        ];
        $this->db->insertObject('#__j2store_carts', $testCart, 'j2store_cart_id');
        $cartId = $this->db->insertid();
        $this->test('Test cart inserted', $cartId > 0);

        // Insert test cart item
        $testCartItem = (object) [
            'cart_id' => $cartId,
            'product_id' => 1,
            'variant_id' => 1,
            'quantity' => 1
        ];
        $this->db->insertObject('#__j2store_cartitems', $testCartItem, 'j2store_cartitem_id');
        $cartItemId = $this->db->insertid();
        $this->test('Test cart item inserted', $cartItemId > 0);

        // Delete cart items first
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__j2store_cartitems'))
            ->where('cart_id = ' . $cartId);
        $this->db->setQuery($query);
        $this->db->execute();

        // Delete cart
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__j2store_carts'))
            ->where('j2store_cart_id = ' . $cartId);
        $this->db->setQuery($query);
        $deleted = $this->db->execute();
        $this->test('Test cart deleted', $deleted !== false);

        // Test 5: Order anonymization simulation
        echo "\n--- Order Anonymization Tests ---\n";
        
        // Insert test order to anonymize
        $testOrder = (object) [
            'order_id' => 'TEST-ANON-001',
            'user_id' => 999,
            'user_email' => 'anon@test.com',
            'billing_first_name' => 'Anon',
            'billing_last_name' => 'Test',
            'billing_address_1' => 'Secret Street 1',
            'billing_city' => 'Secret City',
            'billing_zip' => '99999',
            'order_total' => 100.00,
            'order_state_id' => 1,
            'created_on' => date('Y-m-d H:i:s', strtotime('-5 years'))
        ];
        $this->db->insertObject('#__j2store_orders', $testOrder, 'j2store_order_id');
        $orderId = $this->db->insertid();
        $this->test('Test order inserted', $orderId > 0);

        // Anonymize the order
        $anonymizedData = (object) [
            'j2store_order_id' => $orderId,
            'user_email' => 'anonymized@privacy.local',
            'billing_first_name' => '[ANONYMIZED]',
            'billing_last_name' => '[ANONYMIZED]',
            'billing_address_1' => '[ANONYMIZED]',
            'billing_city' => '[ANONYMIZED]',
            'billing_zip' => '[ANONYMIZED]',
            'billing_phone' => null,
            'shipping_first_name' => '[ANONYMIZED]',
            'shipping_last_name' => '[ANONYMIZED]',
            'shipping_address_1' => '[ANONYMIZED]',
            'shipping_city' => '[ANONYMIZED]',
            'shipping_zip' => '[ANONYMIZED]',
            'shipping_phone' => null,
            'customer_note' => null
        ];
        $this->db->updateObject('#__j2store_orders', $anonymizedData, 'j2store_order_id');
        
        // Verify anonymization
        $query = $this->db->getQuery(true)
            ->select('billing_first_name, user_email')
            ->from($this->db->quoteName('#__j2store_orders'))
            ->where('j2store_order_id = ' . $orderId);
        $this->db->setQuery($query);
        $order = $this->db->loadObject();
        $this->test('Order anonymized correctly', 
            $order->billing_first_name === '[ANONYMIZED]' && 
            $order->user_email === 'anonymized@privacy.local');

        // Cleanup test order
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__j2store_orders'))
            ->where('j2store_order_id = ' . $orderId);
        $this->db->setQuery($query);
        $this->db->execute();

        echo "\n=== Data Integration Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        return $this->failed === 0;
    }
}

$test = new DataIntegrationTest();
$success = $test->run();
exit($success ? 0 : 1);
