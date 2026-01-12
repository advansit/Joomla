<?php
/**
 * @package     Privacy J2Commerce Plugin
 * @subpackage  Extension
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\Privacy\J2Commerce\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Export\Domain;
use Joomla\Component\Privacy\Administrator\Removal\Status;
use Joomla\Component\Privacy\Administrator\Table\RequestTable;

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
     * Checks if user data can be removed
     * Called BEFORE deletion to validate if removal is allowed
     *
     * @param   RequestTable  $request  The request record
     * @param   User|null     $user     The user to check (null if user deleted)
     *
     * @return  Status
     *
     * @since   1.0.0
     */
    public function onPrivacyCanRemoveData(RequestTable $request, ?User $user): Status
    {
        $status = new Status();

        // If user is already deleted, allow removal
        if (!$user) {
            $status->canRemove = true;
            return $status;
        }

        // Check for orders within retention period
        $retentionCheck = $this->checkOrderRetention($user->id);
        
        if (!$retentionCheck['can_delete']) {
            $status->canRemove = false;
            $status->reason = $this->formatRetentionMessage($retentionCheck);
            return $status;
        }

        $status->canRemove = true;
        return $status;
    }

    /**
     * Removes user data from J2Commerce
     * Called DURING deletion to perform actual data removal
     * Return values are NOT processed by Joomla
     *
     * @param   RequestTable  $request  The request record
     * @param   User|null     $user     The user to remove data for (null if user deleted)
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onPrivacyRemoveData(RequestTable $request, ?User $user): void
    {
        // If user is already deleted, nothing to do
        if (!$user) {
            return;
        }

        try {
            // Anonymize or delete orders
            if ($this->params->get('anonymize_orders', 1)) {
                $this->anonymizeOrders($user->id);
            }

            // Delete addresses
            if ($this->params->get('delete_addresses', 1)) {
                $this->deleteAddresses($user->id);
            }
        } catch (\Exception $e) {
            // Log error but don't throw - exceptions are caught by Joomla
            $this->getApplication()->enqueueMessage(
                'J2Commerce data removal failed: ' . $e->getMessage(),
                'error'
            );
        }
    }

    /**
     * Check if user has orders that prevent data deletion due to retention requirements
     *
     * @param   int  $userId  The user ID
     *
     * @return  array  Retention information
     *
     * @since   1.0.0
     */
    protected function checkOrderRetention(int $userId): array
    {
        $db = $this->getDatabase();
        $retentionYears = (int) $this->params->get('retention_years', 10);
        
        $result = [
            'can_delete' => true,
            'orders' => [],
            'lifetime_licenses_accounting' => [],
            'retention_years' => $retentionYears
        ];

        // Check for ALL orders
        $query = $db->getQuery(true)
            ->select([
                'o.j2store_order_id',
                'o.order_number',
                'o.billing_email',
                'o.created_on',
                'o.order_total',
                'o.currency_code',
                'oi.orderitem_name',
                'oi.orderitem_sku',
                'oi.orderitem_quantity',
                'oi.product_id'
            ])
            ->from($db->quoteName('#__j2store_orders', 'o'))
            ->leftJoin(
                $db->quoteName('#__j2store_orderitems', 'oi') . 
                ' ON ' . $db->quoteName('o.j2store_order_id') . ' = ' . $db->quoteName('oi.order_id')
            )
            ->where($db->quoteName('o.user_id') . ' = :userid')
            ->bind(':userid', $userId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $orders = $db->loadObjectList();

        if (empty($orders)) {
            return $result;
        }

        $now = time();
        $cutoffDate = strtotime("-{$retentionYears} years");

        // Group orders by order_id to avoid duplicates
        $processedOrders = [];
        
        foreach ($orders as $order) {
            if (isset($processedOrders[$order->j2store_order_id])) {
                continue;
            }
            
            $orderDate = strtotime($order->created_on);
            
            // Check if this is a lifetime license via Custom Field
            $isLifetime = $this->isLifetimeLicense($order->product_id);
            
            if ($isLifetime && $orderDate <= $cutoffDate) {
                // Lifetime license older than retention period
                // Can be partially anonymized (keep email for activation)
                // But still block full deletion
                $result['can_delete'] = false;
                $result['lifetime_licenses_accounting'][] = [
                    'order_number' => $order->order_number,
                    'product_name' => $order->orderitem_name,
                    'sku' => $order->orderitem_sku,
                    'order_date' => date('d.m.Y', $orderDate),
                    'order_total' => number_format($order->order_total, 2),
                    'currency' => $order->currency_code,
                    'accounting_expired' => true
                ];
                
                $processedOrders[$order->j2store_order_id] = true;
                continue;
            }
            
            // Check if order is within retention period
            if ($orderDate > $cutoffDate) {
                $orderAge = ($now - $orderDate) / (365 * 24 * 60 * 60);
                $yearsRemaining = $retentionYears - $orderAge;
                $retentionEnd = date('d.m.Y', strtotime($order->created_on . " +{$retentionYears} years"));
                
                $result['can_delete'] = false;
                $result['orders'][] = [
                    'order_number' => $order->order_number,
                    'order_date' => date('d.m.Y', $orderDate),
                    'order_total' => number_format($order->order_total, 2),
                    'currency' => $order->currency_code,
                    'order_age_years' => round($orderAge, 1),
                    'years_remaining' => round($yearsRemaining, 1),
                    'retention_end' => $retentionEnd,
                    'is_lifetime' => $isLifetime
                ];
                
                $processedOrders[$order->j2store_order_id] = true;
            }
        }

        return $result;
    }

    /**
     * Check if product is a lifetime license via J2Store Custom Field
     *
     * @param   int|null  $productId  Product ID
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function isLifetimeLicense(?int $productId): bool
    {
        if ($productId === null) {
            return false;
        }
        
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select($db->quoteName('field_value'))
            ->from($db->quoteName('#__j2store_product_customfields'))
            ->where($db->quoteName('product_id') . ' = :productid')
            ->where($db->quoteName('field_name') . ' = ' . $db->quote('is_lifetime_license'))
            ->bind(':productid', $productId, \Joomla\Database\ParameterType::INTEGER);
        
        $db->setQuery($query);
        $fieldValue = $db->loadResult();
        
        // Check if custom field is set to 'Yes'
        return $fieldValue !== null && strtolower(trim($fieldValue)) === 'yes';
    }



    /**
     * Format retention message for user
     *
     * @param   array  $retentionCheck  Retention check result
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function formatRetentionMessage(array $retentionCheck): string
    {
        $retentionYears = $retentionCheck['retention_years'];
        $supportEmail = $this->params->get('support_email', 'support@example.com');
        
        $message = "═══════════════════════════════════════════════════════\n";
        $message .= Text::_('PLG_PRIVACY_J2COMMERCE_DELETION_NOT_POSSIBLE') . "\n";
        $message .= "═══════════════════════════════════════════════════════\n\n";
        
        // Check if there are lifetime licenses with expired accounting period
        if (!empty($retentionCheck['lifetime_licenses_accounting'])) {
            $message .= "Sie haben Lifetime-Lizenzen erworben. Die Buchhaltungsfrist\n";
            $message .= "ist abgelaufen, aber Ihre E-Mail-Adresse wird für die\n";
            $message .= "Lizenzaktivierung benötigt.\n\n";
            
            $message .= "═══════════════════════════════════════════════════════\n";
            $message .= "LIFETIME-LIZENZEN (Buchhaltungsfrist abgelaufen)\n";
            $message .= "═══════════════════════════════════════════════════════\n\n";
            
            foreach ($retentionCheck['lifetime_licenses_accounting'] as $i => $license) {
                $message .= ($i + 1) . ". {$license['product_name']}\n";
                $message .= "   Bestellung: {$license['order_number']}\n";
                $message .= "   Gekauft am: {$license['order_date']}\n";
                $message .= "   Betrag: {$license['order_total']} {$license['currency']}\n";
                $message .= "   Status: Zeitlich unbegrenzt gültig\n\n";
            }
            
            $message .= "═══════════════════════════════════════════════════════\n";
            $message .= "WAS WIRD GESPEICHERT?\n";
            $message .= "═══════════════════════════════════════════════════════\n\n";
            
            $message .= "Für die Lizenzaktivierung notwendig (GDPR Art. 5 lit. c):\n";
            $message .= "✅ E-Mail-Adresse (für Aktivierung)\n";
            $message .= "✅ Lizenzschlüssel\n";
            $message .= "✅ Kaufdatum\n";
            $message .= "✅ Produktinformation\n\n";
            
            $message .= "Bereits gelöscht/anonymisiert:\n";
            $message .= "❌ Vollständiger Name\n";
            $message .= "❌ Rechnungsadresse\n";
            $message .= "❌ Lieferadresse\n";
            $message .= "❌ Telefonnummer\n";
            $message .= "❌ Zahlungsinformationen\n\n";
            
            $message .= "Rechtsgrundlage: GDPR Art. 6 Abs. 1 lit. b\n";
            $message .= "(Vertragserfüllung - Datenminimierung)\n\n";
            
            $message .= "═══════════════════════════════════════════════════════\n";
            $message .= "IHRE OPTIONEN\n";
            $message .= "═══════════════════════════════════════════════════════\n\n";
            
            $message .= "1. LIZENZ BEHALTEN\n";
            $message .= "   Ihre E-Mail-Adresse bleibt gespeichert für die\n";
            $message .= "   Lizenzaktivierung. Alle anderen Daten wurden bereits\n";
            $message .= "   anonymisiert.\n\n";
            
            $message .= "2. LIZENZ ZURÜCKGEBEN\n";
            $message .= "   Wenn Sie die Lizenz nicht mehr benötigen, können\n";
            $message .= "   Sie diese zurückgeben. Danach wird auch Ihre\n";
            $message .= "   E-Mail-Adresse gelöscht.\n\n";
            
            $message .= "   ⚠️  WICHTIG: Nach der Rückgabe können Sie die\n";
            $message .= "   Lizenz nicht mehr aktivieren!\n\n";
        }
        
        // Check if there are orders within retention
        if (!empty($retentionCheck['orders'])) {
            if (empty($retentionCheck['lifetime_licenses'])) {
                $message .= "Ihre Daten können derzeit nicht gelöscht werden, da Sie\n";
                $message .= "Bestellungen getätigt haben, für die eine gesetzliche\n";
                $message .= "Aufbewahrungspflicht besteht.\n\n";
                
                $message .= "═══════════════════════════════════════════════════════\n";
                $message .= "GESETZLICHE GRUNDLAGE\n";
                $message .= "═══════════════════════════════════════════════════════\n\n";
                
                $message .= "Geschäftsunterlagen müssen {$retentionYears} Jahre aufbewahrt werden.\n";
                $message .= "Dies gilt für alle Rechnungen und Bestellungen.\n\n";
                
                $legalBasis = $this->params->get('legal_basis', '');
                if (!empty($legalBasis)) {
                    $message .= "Rechtsgrundlagen:\n";
                    $message .= $legalBasis . "\n\n";
                }
            } else {
                $message .= "═══════════════════════════════════════════════════════\n";
                $message .= "WEITERE BESTELLUNGEN\n";
                $message .= "═══════════════════════════════════════════════════════\n\n";
                
                $message .= "Zusätzlich zu den Lifetime-Lizenzen haben Sie weitere\n";
                $message .= "Bestellungen, die noch in der Aufbewahrungsfrist sind:\n\n";
            }
            
            foreach ($retentionCheck['orders'] as $i => $order) {
                $message .= ($i + 1) . ". Bestellung {$order['order_number']}\n";
                $message .= "   Datum: {$order['order_date']}\n";
                $message .= "   Betrag: {$order['order_total']} {$order['currency']}\n";
                $message .= "   Aufbewahrung bis: {$order['retention_end']}\n";
                $message .= "   Verbleibend: {$order['years_remaining']} Jahre\n\n";
            }
            
            if (empty($retentionCheck['lifetime_licenses'])) {
                // Find latest retention end date
                $latestOrder = end($retentionCheck['orders']);
                $message .= "Automatische Löschung ab: {$latestOrder['retention_end']}\n\n";
                
                $message .= "═══════════════════════════════════════════════════════\n";
                $message .= "AUTOMATISCHE LÖSCHUNG\n";
                $message .= "═══════════════════════════════════════════════════════\n\n";
                
                $message .= "✅ Ihre Daten werden AUTOMATISCH gelöscht, sobald die\n";
                $message .= "   Aufbewahrungsfrist abgelaufen ist.\n\n";
                
                $message .= "✅ Sie müssen NICHTS weiter tun.\n\n";
                
                $message .= "✅ Das System prüft täglich alle Benutzerkonten und\n";
                $message .= "   anonymisiert abgelaufene Daten automatisch.\n\n";
                
                $message .= "═══════════════════════════════════════════════════════\n";
                $message .= "WAS WIRD GELÖSCHT?\n";
                $message .= "═══════════════════════════════════════════════════════\n\n";
                
                $message .= "Nach Ablauf der Frist werden automatisch anonymisiert:\n";
                $message .= "• E-Mail-Adresse\n";
                $message .= "• Name und Anschrift\n";
                $message .= "• Telefonnummer\n";
                $message .= "• Alle persönlichen Daten\n\n";
                
                $message .= "Erhalten bleiben (anonymisiert):\n";
                $message .= "• Bestellnummer\n";
                $message .= "• Bestelldatum\n";
                $message .= "• Rechnungsbetrag\n";
                $message .= "(Für Buchhaltung und Statistik)\n\n";
            }
        }
        
        $message .= "═══════════════════════════════════════════════════════\n";
        $message .= "KONTAKT\n";
        $message .= "═══════════════════════════════════════════════════════\n\n";
        
        $message .= "Bei Fragen kontaktieren Sie uns:\n";
        $message .= "{$supportEmail}\n\n";
        
        return $message;
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
