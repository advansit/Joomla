<?php
/**
 * @package     J2Commerce Privacy Plugin
 * @subpackage  Extension
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\System\J2Commerce\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Component\Privacy\Administrator\Export\Domain;
use Joomla\Component\Privacy\Administrator\Export\Field;
use Joomla\Component\Privacy\Administrator\Export\Item;
use Joomla\Component\Privacy\Administrator\Removal\Status;
use Joomla\Component\Privacy\Administrator\Table\RequestTable;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;

class J2Commerce extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    /**
     * Create a database query object (Joomla 4/5/6 compatible)
     * Joomla 6 deprecates getQuery(true) in favor of createQuery()
     *
     * @return  \Joomla\Database\QueryInterface
     */
    protected function createDbQuery()
    {
        $db = $this->getDatabase();
        
        // Joomla 6+: use createQuery()
        if (method_exists($db, 'createQuery')) {
            return $db->createQuery();
        }
        
        // Joomla 4/5: use getQuery(true)
        return $db->getQuery(true);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Privacy Component Events (Joomla 5 style)
            'onPrivacyExportRequest'   => 'onPrivacyExportRequest',
            'onPrivacyCanRemoveData'   => 'onPrivacyCanRemoveData',
            'onPrivacyRemoveData'      => 'onPrivacyRemoveData',
            // System Events for Checkout
            'onAfterRender'            => 'onAfterRender',
            'onAjaxJ2commercePrivacy'  => 'onAjaxJ2commercePrivacy',
        ];
    }

    // =========================================================================
    // PRIVACY EXPORT FUNCTIONALITY
    // =========================================================================

    /**
     * Process privacy export request
     * Supports both Joomla 4 (direct params) and Joomla 5 (event object)
     *
     * @param   ExportRequestEvent|User  $eventOrUser  Event object (J5) or User (J4)
     *
     * @return  array|void
     */
    public function onPrivacyExportRequest($eventOrUser)
    {
        // Joomla 5: Event object
        if ($eventOrUser instanceof ExportRequestEvent) {
            $user = $eventOrUser->getUser();
            if (!$user) {
                return;
            }
            $domains = $this->collectExportDomains($user);
            $eventOrUser->addResult($domains);
            return;
        }

        // Joomla 4: Direct User parameter
        $user = $eventOrUser;
        return $this->collectExportDomains($user);
    }

    /**
     * Collect all export domains for a user
     */
    protected function collectExportDomains(User $user): array
    {
        $domains = [];
        $domains[] = $this->createOrdersDomain($user);
        $domains[] = $this->createAddressesDomain($user);

        if ($this->params->get('include_joomla_data', 1)) {
            $domains[] = $this->createJoomlaUserDomain($user);
            $domains[] = $this->createJoomlaProfileDomain($user);
            $domains[] = $this->createJoomlaActionLogsDomain($user);
        }

        return $domains;
    }

    /**
     * Create a new domain object
     */
    protected function createDomain(string $name, string $description = ''): Domain
    {
        $domain = new Domain();
        $domain->name = $name;
        $domain->description = $description;
        return $domain;
    }

    /**
     * Create an item object from an array
     */
    protected function createItemFromArray(array $data, $itemId = null): Item
    {
        $item = new Item();
        $item->id = $itemId;

        foreach ($data as $key => $value) {
            if (\is_object($value)) {
                $value = (array) $value;
            }
            if (\is_array($value)) {
                $value = print_r($value, true);
            }

            $field = new Field();
            $field->name = $key;
            $field->value = $value;
            $item->addField($field);
        }

        return $item;
    }

    protected function createOrdersDomain(User $user): Domain
    {
        $domain = $this->createDomain('j2store_orders', 'J2Store order data');
        $db = $this->getDatabase();

        $query = $this->createDbQuery()
            ->select(['o.*', 'oi.orderitem_name', 'oi.orderitem_sku', 'oi.orderitem_quantity', 'oi.orderitem_price', 'oi.orderitem_final_price'])
            ->from($db->quoteName('#__j2store_orders', 'o'))
            ->leftJoin($db->quoteName('#__j2store_orderitems', 'oi') . ' ON o.j2store_order_id = oi.order_id')
            ->where($db->quoteName('o.user_id') . ' = :userid')
            ->bind(':userid', $user->id, ParameterType::INTEGER);

        $db->setQuery($query);
        $rows = $db->loadAssocList();

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

    protected function createAddressesDomain(User $user): Domain
    {
        $domain = $this->createDomain('j2store_addresses', 'J2Store address data');
        $db = $this->getDatabase();

        $query = $this->createDbQuery()
            ->select('*')
            ->from($db->quoteName('#__j2store_addresses'))
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $user->id, ParameterType::INTEGER);

        $db->setQuery($query);
        $addresses = $db->loadAssocList();

        foreach ($addresses as $address) {
            $domain->addItem($this->createItemFromArray($address, $address['j2store_address_id'] ?? $address['id']));
        }

        return $domain;
    }

    protected function createJoomlaUserDomain(User $user): Domain
    {
        $domain = $this->createDomain('joomla_user', 'Joomla user account data');
        $db = $this->getDatabase();

        $query = $this->createDbQuery()
            ->select(['id', 'name', 'username', 'email', 'registerDate', 'lastvisitDate', 'params'])
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('id') . ' = :userid')
            ->bind(':userid', $user->id, ParameterType::INTEGER);

        $db->setQuery($query);
        $userData = $db->loadAssoc();

        if ($userData) {
            $domain->addItem($this->createItemFromArray($userData, $userData['id']));
        }

        return $domain;
    }

    protected function createJoomlaProfileDomain(User $user): Domain
    {
        $domain = $this->createDomain('joomla_user_profiles', 'Joomla user profile data');
        $db = $this->getDatabase();

        $query = $this->createDbQuery()
            ->select('*')
            ->from($db->quoteName('#__user_profiles'))
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $user->id, ParameterType::INTEGER);

        $db->setQuery($query);
        $profiles = $db->loadAssocList();

        foreach ($profiles as $profile) {
            $domain->addItem($this->createItemFromArray($profile, $profile['user_id'] . '_' . $profile['profile_key']));
        }

        return $domain;
    }

    protected function createJoomlaActionLogsDomain(User $user): Domain
    {
        $domain = $this->createDomain('joomla_action_logs', 'Joomla user activity logs');
        $db = $this->getDatabase();

        $tables = $db->getTableList();
        $prefix = $db->getPrefix();
        if (!in_array($prefix . 'action_logs', $tables)) {
            return $domain;
        }

        $query = $this->createDbQuery()
            ->select(['id', 'message_language_key', 'message', 'log_date', 'extension', 'item_id', 'ip_address'])
            ->from($db->quoteName('#__action_logs'))
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $user->id, ParameterType::INTEGER)
            ->order($db->quoteName('log_date') . ' DESC');

        $db->setQuery($query);
        $logs = $db->loadAssocList();

        foreach ($logs as $log) {
            $domain->addItem($this->createItemFromArray($log, $log['id']));
        }

        return $domain;
    }

    // =========================================================================
    // PRIVACY REMOVAL FUNCTIONALITY
    // =========================================================================

    /**
     * Check if user data can be removed
     * Supports both Joomla 4 and Joomla 5 signatures
     *
     * @param   CanRemoveDataEvent|RequestTable  $eventOrRequest  Event (J5) or RequestTable (J4)
     * @param   User|null                        $user            User object (J4 only)
     *
     * @return  Status|void
     */
    public function onPrivacyCanRemoveData($eventOrRequest, ?User $user = null)
    {
        // Joomla 5: Event object
        if ($eventOrRequest instanceof CanRemoveDataEvent) {
            $user = $eventOrRequest->getUser();
            $status = $this->checkCanRemoveData($user);
            $eventOrRequest->addResult($status);
            return;
        }

        // Joomla 4: Direct parameters
        return $this->checkCanRemoveData($user);
    }

    /**
     * Check if user data can be removed
     */
    protected function checkCanRemoveData(?User $user): Status
    {
        $status = new Status();

        if (!$user) {
            return $status;
        }

        $retentionCheck = $this->checkRetentionPeriod($user->id);

        if (!$retentionCheck['can_delete']) {
            $status->canRemove = false;
            $status->reason = $this->formatRetentionMessage($retentionCheck);
        }

        return $status;
    }

    /**
     * Remove user data
     * Supports both Joomla 4 and Joomla 5 signatures
     *
     * @param   RemoveDataEvent|RequestTable  $eventOrRequest  Event (J5) or RequestTable (J4)
     * @param   User|null                     $user            User object (J4 only)
     *
     * @return  void
     */
    public function onPrivacyRemoveData($eventOrRequest, ?User $user = null): void
    {
        // Joomla 5: Event object
        if ($eventOrRequest instanceof RemoveDataEvent) {
            $user = $eventOrRequest->getUser();
            $this->processDataRemoval($user);
            return;
        }

        // Joomla 4: Direct parameters
        $this->processDataRemoval($user);
    }

    /**
     * Process data removal for user
     */
    protected function processDataRemoval(?User $user): void
    {
        if (!$user) {
            return;
        }

        $retentionCheck = $this->checkRetentionPeriod($user->id);
        if (!$retentionCheck['can_delete']) {
            return;
        }

        if ($this->params->get('anonymize_orders', 1)) {
            $this->anonymizeOrders($user->id);
        }

        if ($this->params->get('delete_addresses', 1)) {
            $this->deleteAddresses($user->id);
        }
    }

    protected function checkRetentionPeriod(int $userId): array
    {
        $retentionYears = (int) $this->params->get('retention_years', 10);
        $db = $this->getDatabase();

        $query = $this->createDbQuery()
            ->select(['o.j2store_order_id', 'o.order_number', 'o.created_on', 'o.order_total', 'o.currency_code', 'oi.product_id'])
            ->from($db->quoteName('#__j2store_orders', 'o'))
            ->leftJoin($db->quoteName('#__j2store_orderitems', 'oi') . ' ON o.j2store_order_id = oi.order_id')
            ->where($db->quoteName('o.user_id') . ' = :userid')
            ->bind(':userid', $userId, ParameterType::INTEGER);

        $db->setQuery($query);
        $orders = $db->loadObjectList();

        $result = [
            'can_delete' => true,
            'retention_years' => $retentionYears,
            'orders' => [],
            'lifetime_licenses' => [],
            'lifetime_licenses_accounting' => []
        ];

        if (empty($orders)) {
            return $result;
        }

        $now = time();
        $cutoffDate = strtotime("-{$retentionYears} years");
        $processedOrders = [];

        foreach ($orders as $order) {
            if (isset($processedOrders[$order->j2store_order_id])) {
                continue;
            }

            $orderDate = strtotime($order->created_on);
            $isLifetime = $this->isLifetimeLicense($order->product_id);

            if ($isLifetime && $orderDate <= $cutoffDate) {
                $result['can_delete'] = false;
                $result['lifetime_licenses_accounting'][] = [
                    'order_number' => $order->order_number,
                    'order_date' => date('d.m.Y', $orderDate),
                    'order_total' => number_format($order->order_total, 2),
                    'currency' => $order->currency_code,
                    'product_name' => 'Lifetime License'
                ];
                $processedOrders[$order->j2store_order_id] = true;
                continue;
            }

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
                    'years_remaining' => round($yearsRemaining, 1),
                    'retention_end' => $retentionEnd
                ];
                $processedOrders[$order->j2store_order_id] = true;
            }
        }

        return $result;
    }

    protected function isLifetimeLicense(?int $productId): bool
    {
        if ($productId === null) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $this->createDbQuery()
            ->select($db->quoteName('field_value'))
            ->from($db->quoteName('#__j2store_product_customfields'))
            ->where($db->quoteName('product_id') . ' = :productid')
            ->where($db->quoteName('field_name') . ' = ' . $db->quote('is_lifetime_license'))
            ->bind(':productid', $productId, ParameterType::INTEGER);

        $db->setQuery($query);
        $fieldValue = $db->loadResult();

        return $fieldValue !== null && strtolower(trim($fieldValue)) === 'yes';
    }

    protected function formatRetentionMessage(array $retentionCheck): string
    {
        $retentionYears = $retentionCheck['retention_years'];
        $supportEmail = $this->params->get('support_email', 'support@example.com');

        $message = "═══════════════════════════════════════════════════════\n";
        $message .= Text::_('PLG_PRIVACY_J2COMMERCE_DELETION_NOT_POSSIBLE') . "\n";
        $message .= "═══════════════════════════════════════════════════════\n\n";

        if (!empty($retentionCheck['lifetime_licenses_accounting'])) {
            $message .= "Lifetime-Lizenzen vorhanden. E-Mail wird für Aktivierung benötigt.\n\n";
            foreach ($retentionCheck['lifetime_licenses_accounting'] as $i => $license) {
                $message .= ($i + 1) . ". {$license['product_name']}\n";
                $message .= "   Bestellung: {$license['order_number']} vom {$license['order_date']}\n";
                $message .= "   Betrag: {$license['order_total']} {$license['currency']}\n\n";
            }
        }

        if (!empty($retentionCheck['orders'])) {
            $message .= "Bestellungen innerhalb der Aufbewahrungsfrist ({$retentionYears} Jahre):\n\n";
            foreach ($retentionCheck['orders'] as $i => $order) {
                $message .= ($i + 1) . ". Bestellung {$order['order_number']}\n";
                $message .= "   Datum: {$order['order_date']}\n";
                $message .= "   Betrag: {$order['order_total']} {$order['currency']}\n";
                $message .= "   Aufbewahrung bis: {$order['retention_end']}\n";
                $message .= "   Verbleibend: {$order['years_remaining']} Jahre\n\n";
            }
        }

        $message .= "Kontakt: {$supportEmail}\n";

        return $message;
    }

    protected function anonymizeOrders(int $userId): void
    {
        $db = $this->getDatabase();

        $query = $this->createDbQuery()
            ->update($db->quoteName('#__j2store_orders'))
            ->set([
                $db->quoteName('billing_first_name') . ' = ' . $db->quote('Anonymized'),
                $db->quoteName('billing_last_name') . ' = ' . $db->quote('User'),
                $db->quoteName('billing_email') . ' = ' . $db->quote('anonymized@example.com'),
                $db->quoteName('billing_phone_1') . ' = ' . $db->quote(''),
                $db->quoteName('billing_phone_2') . ' = ' . $db->quote(''),
                $db->quoteName('billing_address_1') . ' = ' . $db->quote(''),
                $db->quoteName('billing_address_2') . ' = ' . $db->quote(''),
                $db->quoteName('billing_city') . ' = ' . $db->quote(''),
                $db->quoteName('billing_zip') . ' = ' . $db->quote(''),
                $db->quoteName('shipping_first_name') . ' = ' . $db->quote(''),
                $db->quoteName('shipping_last_name') . ' = ' . $db->quote(''),
                $db->quoteName('shipping_phone_1') . ' = ' . $db->quote(''),
                $db->quoteName('shipping_address_1') . ' = ' . $db->quote(''),
                $db->quoteName('shipping_city') . ' = ' . $db->quote(''),
                $db->quoteName('shipping_zip') . ' = ' . $db->quote(''),
            ])
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $userId, ParameterType::INTEGER);

        $db->setQuery($query);
        $db->execute();
    }

    protected function deleteAddresses(int $userId): void
    {
        $db = $this->getDatabase();

        $query = $this->createDbQuery()
            ->delete($db->quoteName('#__j2store_addresses'))
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':userid', $userId, ParameterType::INTEGER);

        $db->setQuery($query);
        $db->execute();
    }

    // =========================================================================
    // CHECKOUT CONSENT & FRONTEND FEATURES
    // =========================================================================

    public function onAfterRender(): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $option = $app->input->get('option');
        $view = $app->input->get('view');

        if ($option !== 'com_j2store') {
            return;
        }

        $body = $app->getBody();
        $modified = false;

        if ($this->params->get('show_consent_checkbox', 1)) {
            $body = $this->injectConsentCheckbox($body);
            $modified = true;
        }

        if ($this->params->get('show_delete_address', 1) && $view === 'myprofile') {
            $body = $this->injectDeleteAddressButtons($body);
            $modified = true;
        }

        if ($modified) {
            $app->setBody($body);
        }
    }

    protected function injectConsentCheckbox(string $body): string
    {
        $patterns = [
            '/<button[^>]*class="[^"]*j2store-checkout-button[^"]*"[^>]*>/i',
            '/<button[^>]*id="[^"]*place-order[^"]*"[^>]*>/i',
            '/<input[^>]*type="submit"[^>]*class="[^"]*checkout[^"]*"[^>]*>/i',
        ];

        $consentHtml = $this->getConsentCheckboxHtml();

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $matches, PREG_OFFSET_STRING)) {
                $position = $matches[0][1] ?? strpos($body, $matches[0]);
                if ($position !== false) {
                    $body = substr_replace($body, $consentHtml, $position, 0);
                    break;
                }
            }
        }

        $body = $this->injectValidationScript($body);

        return $body;
    }

    protected function getConsentCheckboxHtml(): string
    {
        $required = $this->params->get('consent_required', 1);
        $consentText = $this->params->get('consent_text', 'I have read and agree to the {privacy_policy}.');
        $articleId = $this->params->get('privacy_article', 0);

        if ($articleId) {
            $link = Route::_('index.php?option=com_content&view=article&id=' . $articleId);
            $linkHtml = '<a href="' . $link . '" target="_blank" rel="noopener">' . Text::_('PLG_PRIVACY_J2COMMERCE_POLICY_LINK') . '</a>';
        } else {
            $linkHtml = Text::_('PLG_PRIVACY_J2COMMERCE_POLICY_LINK');
        }

        $consentText = str_replace('{privacy_policy}', $linkHtml, $consentText);
        $requiredAttr = $required ? 'required' : '';
        $requiredStar = $required ? '<span class="text-danger">*</span>' : '';

        return <<<HTML
<div class="j2commerce-privacy-consent mb-3">
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="j2commerce_privacy_consent" name="j2commerce_privacy_consent" value="1" {$requiredAttr}>
        <label class="form-check-label" for="j2commerce_privacy_consent">{$consentText} {$requiredStar}</label>
    </div>
</div>
HTML;
    }

    protected function injectValidationScript(string $body): string
    {
        if (!$this->params->get('consent_required', 1)) {
            return $body;
        }

        $errorMsg = Text::_('PLG_PRIVACY_J2COMMERCE_CONSENT_REQUIRED_ERROR');

        $script = <<<SCRIPT
<script>
document.addEventListener('DOMContentLoaded', function() {
    var forms = document.querySelectorAll('form[action*="j2store"], form.j2store-checkout-form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var consent = document.getElementById('j2commerce_privacy_consent');
            if (consent && !consent.checked) {
                e.preventDefault();
                alert('{$errorMsg}');
                consent.focus();
                return false;
            }
        });
    });
});
</script>
SCRIPT;

        return str_replace('</body>', $script . '</body>', $body);
    }

    protected function injectDeleteAddressButtons(string $body): string
    {
        $pattern = '/<div[^>]*class="[^"]*j2store-address[^"]*"[^>]*data-address-id="(\d+)"[^>]*>/i';

        $body = preg_replace_callback($pattern, function ($matches) {
            $addressId = $matches[1];
            return $matches[0] . $this->getDeleteAddressButtonHtml((int) $addressId);
        }, $body);

        $body = $this->injectDeleteAddressScript($body);

        return $body;
    }

    protected function getDeleteAddressButtonHtml(int $addressId): string
    {
        $confirmMsg = Text::_('PLG_PRIVACY_J2COMMERCE_DELETE_ADDRESS_CONFIRM');

        return <<<HTML
<button type="button" class="btn btn-sm btn-danger j2commerce-delete-address" data-address-id="{$addressId}" title="{$confirmMsg}">
    <span class="icon-trash" aria-hidden="true"></span>
</button>
HTML;
    }

    protected function injectDeleteAddressScript(string $body): string
    {
        $confirmMsg = Text::_('PLG_PRIVACY_J2COMMERCE_DELETE_ADDRESS_CONFIRM');
        $successMsg = Text::_('PLG_PRIVACY_J2COMMERCE_DELETE_ADDRESS_SUCCESS');
        $errorMsg = Text::_('PLG_PRIVACY_J2COMMERCE_DELETE_ADDRESS_ERROR');
        $ajaxUrl = Uri::base() . 'index.php?option=com_ajax&plugin=j2commerce_privacy&group=system&format=json';

        $script = <<<SCRIPT
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.j2commerce-delete-address').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var addressId = this.dataset.addressId;
            if (confirm('{$confirmMsg}')) {
                fetch('{$ajaxUrl}&task=deleteAddress&address_id=' + addressId, {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('{$successMsg}');
                        location.reload();
                    } else {
                        alert(data.message || '{$errorMsg}');
                    }
                })
                .catch(() => alert('{$errorMsg}'));
            }
        });
    });
});
</script>
SCRIPT;

        return str_replace('</body>', $script . '</body>', $body);
    }

    public function onAjaxJ2commercePrivacy(): array
    {
        $app = $this->getApplication();
        $task = $app->input->get('task', '');
        $user = $app->getIdentity();

        if (!$user || $user->guest) {
            return ['success' => false, 'message' => Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST')];
        }

        switch ($task) {
            case 'deleteAddress':
                return $this->deleteUserAddress($app->input->getInt('address_id', 0), $user->id);
            default:
                return ['success' => false, 'message' => 'Invalid task'];
        }
    }

    protected function deleteUserAddress(int $addressId, int $userId): array
    {
        if (!$addressId) {
            return ['success' => false, 'message' => Text::_('PLG_PRIVACY_J2COMMERCE_INVALID_ADDRESS')];
        }

        $db = $this->getDatabase();

        $query = $this->createDbQuery()
            ->select('id')
            ->from($db->quoteName('#__j2store_addresses'))
            ->where($db->quoteName('id') . ' = :addressid')
            ->where($db->quoteName('user_id') . ' = :userid')
            ->bind(':addressid', $addressId, ParameterType::INTEGER)
            ->bind(':userid', $userId, ParameterType::INTEGER);

        $db->setQuery($query);

        if (!$db->loadResult()) {
            return ['success' => false, 'message' => Text::_('PLG_PRIVACY_J2COMMERCE_ADDRESS_NOT_FOUND')];
        }

        $query = $this->createDbQuery()
            ->delete($db->quoteName('#__j2store_addresses'))
            ->where($db->quoteName('id') . ' = :addressid')
            ->bind(':addressid', $addressId, ParameterType::INTEGER);

        $db->setQuery($query);

        try {
            $db->execute();
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => Text::_('PLG_PRIVACY_J2COMMERCE_DELETE_ADDRESS_ERROR')];
        }
    }
}
