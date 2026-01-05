<?php
/**
 * @package     Privacy J2Commerce Plugin
 * @subpackage  Extension
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\Privacy\J2Commerce\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Export\Domain;
use Joomla\Component\Privacy\Administrator\Removal\Status;

class J2Commerce extends PrivacyPlugin
{
    /**
     * Processes an export request for J2Commerce user data
     *
     * @param   User  $user  The user requesting the export
     *
     * @return  Domain[]
     *
     * @since   1.0.0
     */
    public function onPrivacyExportRequest(User $user): array
    {
        $domains = [];

        // J2Commerce data
        $domains[] = $this->createOrdersDomain($user);
        $domains[] = $this->createAddressesDomain($user);

        // Joomla core data (if enabled)
        if ($this->params->get('include_joomla_data', 1)) {
            $domains[] = $this->createJoomlaUserDomain($user);
            $domains[] = $this->createJoomlaProfileDomain($user);
            $domains[] = $this->createJoomlaActionLogsDomain($user);
        }

        return $domains;
    }

    /**
     * Removes user data from J2Commerce
     *
     * @param   User  $user  The user to remove data for
     *
     * @return  Status
     *
     * @since   1.0.0
     */
    public function onPrivacyRemoveData(User $user): Status
    {
        $status = new Status();

        try {
            $db = $this->getDatabase();

            // Anonymize or delete orders
            if ($this->params->get('anonymize_orders', 1)) {
                $this->anonymizeOrders($user->id);
            }

            // Delete addresses
            if ($this->params->get('delete_addresses', 1)) {
                $this->deleteAddresses($user->id);
            }

            $status->success = true;
        } catch (\Exception $e) {
            $status->success = false;
            $status->error = $e->getMessage();
        }

        return $status;
    }

    /**
     * Create domain for user orders
     *
     * @param   User  $user  The user
     *
     * @return  Domain
     *
     * @since   1.0.0
     */
    protected function createOrdersDomain(User $user): Domain
    {
        $domain = $this->createDomain('j2store_orders', 'J2Store order data');

        $db = $this->getDatabase();
        
        // Query orders with order items
        $query = $db->getQuery(true)
            ->select([
                'o.*',
                'oi.orderitem_name',
                'oi.orderitem_sku',
                'oi.orderitem_quantity',
                'oi.orderitem_price',
                'oi.orderitem_final_price'
            ])
            ->from($db->quoteName('#__j2store_orders', 'o'))
            ->leftJoin(
                $db->quoteName('#__j2store_orderitems', 'oi') . 
                ' ON ' . $db->quoteName('o.j2store_order_id') . ' = ' . $db->quoteName('oi.order_id')
            )
            ->where($db->quoteName('o.user_id') . ' = :userid')
            ->bind(':userid', $user->id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $rows = $db->loadAssocList();

        // Group by order ID
        $orders = [];
        foreach ($rows as $row) {
            $orderId = $row['j2store_order_id'];
            
            if (!isset($orders[$orderId])) {
                $orders[$orderId] = [
                    'order_id' => $orderId,
                    'order_state' => $row['order_state'],
                    'order_total' => $row['order_total'],
                    'currency_code' => $row['currency_code'],
                    'created_on' => $row['created_on'],
                    'billing_first_name' => $row['billing_first_name'],
                    'billing_last_name' => $row['billing_last_name'],
                    'billing_email' => $row['billing_email'],
                    'billing_address_1' => $row['billing_address_1'],
                    'billing_city' => $row['billing_city'],
                    'billing_zip' => $row['billing_zip'],
                    'items' => []
                ];
            }
            
            if ($row['orderitem_name']) {
                $orders[$orderId]['items'][] = [
                    'name' => $row['orderitem_name'],
                    'sku' => $row['orderitem_sku'],
                    'quantity' => $row['orderitem_quantity'],
                    'price' => $row['orderitem_final_price']
                ];
            }
        }

        foreach ($orders as $order) {
            $domain->addItem($this->createItemFromArray($order, $order['order_id']));
        }

        return $domain;
    }

    /**
     * Create domain for user addresses
     *
     * @param   User  $user  The user
     *
     * @return  Domain
     *
     * @since   1.0.0
     */
    protected function createAddressesDomain(User $user): Domain
    {
        $domain = $this->createDomain('j2store_addresses', 'J2Store address data');

        $db = $this->getDatabase();
        
        // Addresses are stored in the orders table (billing_* and shipping_* columns)
        $query = $db->getQuery(true)
            ->select([
                'j2store_order_id',
                'billing_first_name',
                'billing_last_name',
                'billing_email',
                'billing_phone',
                'billing_address_1',
                'billing_address_2',
                'billing_city',
                'billing_zip',
                'billing_country_id',
                'billing_zone_id',
                'shipping_first_name',
                'shipping_last_name',
                'shipping_phone',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_zip',
                'shipping_country_id',
                'shipping_zone_id'
            ])
            ->from($db->quoteName('#__j2store_orders'))
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $user->id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $orders = $db->loadAssocList();

        foreach ($orders as $order) {
            // Add billing address
            $billingAddress = [
                'type' => 'billing',
                'order_id' => $order['j2store_order_id'],
                'first_name' => $order['billing_first_name'],
                'last_name' => $order['billing_last_name'],
                'email' => $order['billing_email'],
                'phone' => $order['billing_phone'],
                'address_1' => $order['billing_address_1'],
                'address_2' => $order['billing_address_2'],
                'city' => $order['billing_city'],
                'zip' => $order['billing_zip'],
                'country_id' => $order['billing_country_id'],
                'zone_id' => $order['billing_zone_id']
            ];
            $domain->addItem($this->createItemFromArray($billingAddress, $order['j2store_order_id'] . '_billing'));
            
            // Add shipping address if different from billing
            if (!empty($order['shipping_first_name'])) {
                $shippingAddress = [
                    'type' => 'shipping',
                    'order_id' => $order['j2store_order_id'],
                    'first_name' => $order['shipping_first_name'],
                    'last_name' => $order['shipping_last_name'],
                    'phone' => $order['shipping_phone'],
                    'address_1' => $order['shipping_address_1'],
                    'address_2' => $order['shipping_address_2'],
                    'city' => $order['shipping_city'],
                    'zip' => $order['shipping_zip'],
                    'country_id' => $order['shipping_country_id'],
                    'zone_id' => $order['shipping_zone_id']
                ];
                $domain->addItem($this->createItemFromArray($shippingAddress, $order['j2store_order_id'] . '_shipping'));
            }
        }

        return $domain;
    }

    /**
     * Anonymize user orders
     *
     * @param   int  $userId  The user ID
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function anonymizeOrders(int $userId): void
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__j2store_orders'))
            ->set([
                $db->quoteName('billing_first_name') . ' = ' . $db->quote('Anonymized'),
                $db->quoteName('billing_last_name') . ' = ' . $db->quote('User'),
                $db->quoteName('billing_email') . ' = ' . $db->quote('anonymized@example.com'),
                $db->quoteName('billing_phone') . ' = ' . $db->quote(''),
                $db->quoteName('billing_address_1') . ' = ' . $db->quote('Anonymized'),
                $db->quoteName('billing_address_2') . ' = ' . $db->quote(''),
                $db->quoteName('billing_city') . ' = ' . $db->quote('Anonymized'),
                $db->quoteName('billing_zip') . ' = ' . $db->quote('00000'),
                $db->quoteName('shipping_first_name') . ' = ' . $db->quote('Anonymized'),
                $db->quoteName('shipping_last_name') . ' = ' . $db->quote('User'),
                $db->quoteName('shipping_phone') . ' = ' . $db->quote(''),
                $db->quoteName('shipping_address_1') . ' = ' . $db->quote('Anonymized'),
                $db->quoteName('shipping_address_2') . ' = ' . $db->quote(''),
                $db->quoteName('shipping_city') . ' = ' . $db->quote('Anonymized'),
                $db->quoteName('shipping_zip') . ' = ' . $db->quote('00000'),
            ])
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $userId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Delete user addresses
     * 
     * Note: Addresses are stored in orders table, so this method
     * is not applicable. Address anonymization is handled by anonymizeOrders().
     *
     * @param   int  $userId  The user ID
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function deleteAddresses(int $userId): void
    {
        // Addresses are stored in the orders table (billing_* and shipping_* columns)
        // They are anonymized by the anonymizeOrders() method
        // J2Commerce
    }

    /**
     * Create domain for Joomla user data
     *
     * @param   User  $user  The user
     *
     * @return  Domain
     *
     * @since   1.0.0
     */
    protected function createJoomlaUserDomain(User $user): Domain
    {
        $domain = $this->createDomain('joomla_user', 'Joomla user account data');

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'id',
                'name',
                'username',
                'email',
                'registerDate',
                'lastvisitDate',
                'lastResetTime',
                'resetCount',
                'params'
            ])
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('id') . ' = :userid')
            ->bind(':userid', $user->id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $userData = $db->loadAssoc();

        if ($userData) {
            $domain->addItem($this->createItemFromArray($userData, $userData['id']));
        }

        return $domain;
    }

    /**
     * Create domain for Joomla user profile data
     *
     * @param   User  $user  The user
     *
     * @return  Domain
     *
     * @since   1.0.0
     */
    protected function createJoomlaProfileDomain(User $user): Domain
    {
        $domain = $this->createDomain('joomla_user_profiles', 'Joomla user profile data');

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__user_profiles'))
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $user->id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $profiles = $db->loadAssocList();

        foreach ($profiles as $profile) {
            $domain->addItem($this->createItemFromArray($profile, $profile['user_id'] . '_' . $profile['profile_key']));
        }

        return $domain;
    }

    /**
     * Create domain for Joomla action logs
     *
     * @param   User  $user  The user
     *
     * @return  Domain
     *
     * @since   1.0.0
     */
    protected function createJoomlaActionLogsDomain(User $user): Domain
    {
        $domain = $this->createDomain('joomla_action_logs', 'Joomla user activity logs');

        $db = $this->getDatabase();
        
        // Check if action logs table exists
        $tables = $db->getTableList();
        $prefix = $db->getPrefix();
        
        if (!in_array($prefix . 'action_logs', $tables)) {
            return $domain;
        }

        $query = $db->getQuery(true)
            ->select([
                'id',
                'message_language_key',
                'message',
                'log_date',
                'extension',
                'user_id',
                'item_id',
                'ip_address'
            ])
            ->from($db->quoteName('#__action_logs'))
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $user->id, \Joomla\Database\ParameterType::INTEGER)
            ->order($db->quoteName('log_date') . ' DESC');

        $db->setQuery($query);
        $logs = $db->loadAssocList();

        foreach ($logs as $log) {
            $domain->addItem($this->createItemFromArray($log, $log['id']));
        }

        return $domain;
    }
}
