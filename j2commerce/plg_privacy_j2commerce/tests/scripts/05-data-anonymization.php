<?php
/**
 * Data Anonymization Tests - validates that the anonymization queries
 * target the correct tables and columns in real J2Commerce schema.
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;

class DataAnonymizationTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->db = Factory::getContainer()->get('DatabaseDriver');
    }

    private function test($name, $condition, $message = '')
    {
        if ($condition) {
            echo "PASS $name\n";
            $this->passed++;
        } else {
            echo "FAIL $name" . ($message ? " - $message" : "") . "\n";
            $this->failed++;
        }
        return $condition;
    }

    public function run(): bool
    {
        echo "=== Data Anonymization Tests ===\n\n";

        // Test 1: Verify anonymization targets correct tables
        echo "--- Schema Validation for Anonymization ---\n";

        // orders table should have user_email, customer_note, ip_address
        $orderCols = $this->getTableColumns('#__j2store_orders');
        $this->test('orders has user_email column', in_array('user_email', $orderCols));
        $this->test('orders has customer_note column', in_array('customer_note', $orderCols));
        $this->test('orders has ip_address column', in_array('ip_address', $orderCols));
        // orders should NOT have billing_first_name (that's in orderinfos)
        $this->test('orders does NOT have billing_first_name', !in_array('billing_first_name', $orderCols),
            'billing_first_name belongs in orderinfos, not orders');

        // orderinfos table should have billing/shipping fields
        $infoCols = $this->getTableColumns('#__j2store_orderinfos');
        $this->test('orderinfos has billing_first_name', in_array('billing_first_name', $infoCols));
        $this->test('orderinfos has billing_last_name', in_array('billing_last_name', $infoCols));
        $this->test('orderinfos has billing_address_1', in_array('billing_address_1', $infoCols));
        $this->test('orderinfos has billing_city', in_array('billing_city', $infoCols));
        $this->test('orderinfos has billing_zip', in_array('billing_zip', $infoCols));
        $this->test('orderinfos has billing_phone_1', in_array('billing_phone_1', $infoCols));
        $this->test('orderinfos has shipping_first_name', in_array('shipping_first_name', $infoCols));
        $this->test('orderinfos has shipping_address_1', in_array('shipping_address_1', $infoCols));

        // Test 2: Full anonymization round-trip
        echo "\n--- Anonymization Round-Trip ---\n";

        // Create test order + orderinfo
        $orderId = 'ANON-TEST-' . time();
        $testOrder = (object) [
            'order_id' => $orderId,
            'user_id' => 998,
            'user_email' => 'private@example.com',
            'order_total' => 50.00000,
            'order_subtotal' => 45.00000,
            'order_tax' => 5.00000,
            'order_shipping' => 0.00000,
            'order_discount' => 0.00000,
            'order_state_id' => 1,
            'currency_code' => 'CHF',
            'currency_value' => 1.00000000,
            'customer_note' => 'Please deliver before 5pm',
            'ip_address' => '192.168.1.100',
            'created_on' => date('Y-m-d H:i:s', strtotime('-12 years'))
        ];
        $this->db->insertObject('#__j2store_orders', $testOrder, 'j2store_order_id');
        $orderPk = $this->db->insertid();

        $testInfo = (object) [
            'order_id' => $orderId,
            'billing_first_name' => 'Hans',
            'billing_last_name' => 'Muster',
            'billing_address_1' => 'Bahnhofstrasse 1',
            'billing_city' => 'Zürich',
            'billing_zip' => '8001',
            'billing_phone_1' => '+41 44 111 22 33',
            'billing_company' => 'Muster AG',
            'billing_tax_number' => 'CHE-123.456.789',
            'shipping_first_name' => 'Hans',
            'shipping_last_name' => 'Muster',
            'shipping_address_1' => 'Bahnhofstrasse 1',
            'shipping_city' => 'Zürich',
            'shipping_zip' => '8001',
            'shipping_phone_1' => '+41 44 111 22 33',
        ];
        $this->db->insertObject('#__j2store_orderinfos', $testInfo, 'j2store_orderinfo_id');
        $infoPk = $this->db->insertid();

        // Anonymize orders table
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__j2store_orders'))
            ->set([
                $this->db->quoteName('user_email') . ' = ' . $this->db->quote('anonymized@example.com'),
                $this->db->quoteName('customer_note') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('ip_address') . ' = ' . $this->db->quote(''),
            ])
            ->where('j2store_order_id = ' . $orderPk);
        $this->db->setQuery($query);
        $this->db->execute();

        // Anonymize orderinfos table
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__j2store_orderinfos'))
            ->set([
                $this->db->quoteName('billing_first_name') . ' = ' . $this->db->quote('Anonymized'),
                $this->db->quoteName('billing_last_name') . ' = ' . $this->db->quote('User'),
                $this->db->quoteName('billing_phone_1') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('billing_address_1') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('billing_city') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('billing_zip') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('billing_company') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('billing_tax_number') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('shipping_first_name') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('shipping_last_name') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('shipping_phone_1') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('shipping_address_1') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('shipping_city') . ' = ' . $this->db->quote(''),
                $this->db->quoteName('shipping_zip') . ' = ' . $this->db->quote(''),
            ])
            ->where('order_id = ' . $this->db->quote($orderId));
        $this->db->setQuery($query);
        $this->db->execute();

        // Verify
        $query = $this->db->getQuery(true)
            ->select('user_email, customer_note, ip_address')
            ->from($this->db->quoteName('#__j2store_orders'))
            ->where('j2store_order_id = ' . $orderPk);
        $this->db->setQuery($query);
        $order = $this->db->loadObject();
        $this->test('user_email anonymized', $order->user_email === 'anonymized@example.com');
        $this->test('customer_note cleared', $order->customer_note === '');
        $this->test('ip_address cleared', $order->ip_address === '');

        $query = $this->db->getQuery(true)
            ->select('billing_first_name, billing_address_1, billing_company, billing_tax_number, shipping_first_name')
            ->from($this->db->quoteName('#__j2store_orderinfos'))
            ->where('j2store_orderinfo_id = ' . $infoPk);
        $this->db->setQuery($query);
        $info = $this->db->loadObject();
        $this->test('billing_first_name anonymized', $info->billing_first_name === 'Anonymized');
        $this->test('billing_address_1 cleared', $info->billing_address_1 === '');
        $this->test('billing_company cleared', $info->billing_company === '');
        $this->test('billing_tax_number cleared', $info->billing_tax_number === '');
        $this->test('shipping_first_name cleared', $info->shipping_first_name === '');

        // Verify order_total is preserved (financial data must remain)
        $query = $this->db->getQuery(true)
            ->select('order_total')
            ->from($this->db->quoteName('#__j2store_orders'))
            ->where('j2store_order_id = ' . $orderPk);
        $this->db->setQuery($query);
        $this->test('order_total preserved after anonymization',
            (float) $this->db->loadResult() === 50.0);

        // Cleanup
        $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__j2store_orderinfos') . ' WHERE j2store_orderinfo_id = ' . $infoPk);
        $this->db->execute();
        $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__j2store_orders') . ' WHERE j2store_order_id = ' . $orderPk);
        $this->db->execute();

        echo "\n=== Data Anonymization Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        return $this->failed === 0;
    }

    private function getTableColumns(string $table): array
    {
        $columns = $this->db->getTableColumns($table);
        return array_keys($columns);
    }
}

$test = new DataAnonymizationTest();
exit($test->run() ? 0 : 1);
