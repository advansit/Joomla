<?php
/**
 * J2Commerce AcyMailing Plugin
 * @subpackage  Extension
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\J2Commerce\AcyMailing\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class AcyMailing extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onJ2StoreAfterSaveOrder' => 'handleOrderComplete',
            'onJ2StoreAfterPaymentConfirmed' => 'handleOrderComplete',
            'onContentPrepare' => 'handleProductDisplay',
        ];
    }

    /**
     * Handle order completion events
     * Processes newsletter subscriptions after order is confirmed
     *
     * @param   Event  $event  The event object
     * @return  void
     * @since   1.0.0
     */
    public function handleOrderComplete(Event $event): void
    {
        // Get order from event - try different argument positions
        $order = $event->getArgument('order') ?? $event->getArgument(0);
        
        if (!$order) {
            return;
        }

        // Only process confirmed orders
        if (empty($order->order_state) || $order->order_state !== 'confirmed') {
            return;
        }

        $app = Factory::getApplication();
        
        // Check if auto-subscribe is enabled
        $autoSubscribe = $this->params->get('auto_subscribe', 0);
        
        if ($autoSubscribe) {
            // Auto-subscribe without checkbox
            $this->subscribeUser($order);
            return;
        }

        // Check if user manually subscribed via checkbox
        if (!$this->params->get('show_in_checkout', 1)) {
            return;
        }

        $subscribe = $app->input->get('acymailing_subscribe', 0, 'int');

        if (!$subscribe) {
            return;
        }

        // Check guest subscription setting
        $user = Factory::getUser();
        if ($user->guest && !$this->params->get('guest_subscription', 1)) {
            return;
        }

        $this->subscribeUser($order);
    }

    /**
     * Display subscription checkbox in checkout
     * J2Commerce uses template overrides for checkout forms
     * This method provides the HTML that can be included in templates
     */
    public function onJ2StoreGetCheckoutFields(&$fields)
    {
        // Don't show checkbox if auto-subscribe is enabled
        if ($this->params->get('auto_subscribe', 0)) {
            return;
        }

        if (!$this->params->get('show_in_checkout', 1)) {
            return;
        }

        // Check guest subscription setting
        $user = Factory::getUser();
        if ($user->guest && !$this->params->get('guest_subscription', 1)) {
            return;
        }

        $checkboxLabel = Text::_($this->params->get('checkbox_label', 'PLG_J2STORE_ACYMAILING_DEFAULT_CHECKBOX_LABEL'));
        $checkboxDefault = $this->params->get('checkbox_default', 0) ? 'checked' : '';

        $html = '<div class="acymailing-subscription form-group">';
        $html .= '<label class="checkbox">';
        $html .= '<input type="checkbox" name="acymailing_subscribe" value="1" ' . $checkboxDefault . '>';
        $html .= ' ' . $checkboxLabel;
        $html .= '</label>';
        $html .= '</div>';

        $fields['acymailing_subscribe'] = $html;
    }

    /**
     * Handle product display to show subscription checkbox
     *
     * @param   Event  $event  The event object
     * @return  void
     * @since   1.0.0
     */
    public function handleProductDisplay(Event $event): void
    {
        if (!$this->params->get('show_in_products', 0)) {
            return;
        }

        // Don't show if auto-subscribe is enabled
        if ($this->params->get('auto_subscribe', 0)) {
            return;
        }

        $context = $event->getArgument('context');
        
        // Only process J2Store product context
        if ($context !== 'com_j2store.product') {
            return;
        }

        $article = $event->getArgument('article');
        
        if (!$article) {
            return;
        }

        // Check guest subscription setting
        $user = Factory::getUser();
        if ($user->guest && !$this->params->get('guest_subscription', 1)) {
            return;
        }

        $checkboxLabel = Text::_($this->params->get('checkbox_label', 'PLG_J2STORE_ACYMAILING_DEFAULT_CHECKBOX_LABEL'));
        $checkboxDefault = $this->params->get('checkbox_default', 0) ? 'checked' : '';

        $html = '<div class="acymailing-subscription-product form-group">';
        $html .= '<label class="checkbox">';
        $html .= '<input type="checkbox" name="acymailing_subscribe_product" value="1" ' . $checkboxDefault . '>';
        $html .= ' ' . $checkboxLabel;
        $html .= '</label>';
        $html .= '</div>';

        // Append to article text
        $article->text .= $html;
    }

    /**
     * Subscribe user to AcyMailing list
     *
     * @param   object  $order  The order object
     * @return  bool    True on success, false on failure
     * @since   1.0.0
     */
    protected function subscribeUser($order): bool
    {
        try {
            // Check if AcyMailing is installed
            if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_acym/helpers/helper.php')) {
                $this->getApplication()->enqueueMessage(
                    Text::_('PLG_J2STORE_ACYMAILING_ERROR_NOT_INSTALLED'),
                    'warning'
                );
                return false;
            }

            require_once JPATH_ADMINISTRATOR . '/components/com_acym/helpers/helper.php';

            // Get list IDs - check for multiple lists first
            $multipleLists = $this->params->get('multiple_lists', '');
            $listIds = [];
            
            if (!empty($multipleLists)) {
                // Parse comma-separated list IDs
                $listIds = array_map('trim', explode(',', $multipleLists));
                $listIds = array_filter($listIds, 'is_numeric');
            } else {
                // Use single list ID
                $singleListId = $this->params->get('list_id', '');
                if (!empty($singleListId)) {
                    $listIds = [$singleListId];
                }
            }
            
            if (empty($listIds)) {
                $this->getApplication()->enqueueMessage(
                    Text::_('PLG_J2STORE_ACYMAILING_ERROR_NO_LIST'),
                    'warning'
                );
                return false;
            }

            $email = $order->billing_email ?? '';
            $name = trim(($order->billing_first_name ?? '') . ' ' . ($order->billing_last_name ?? ''));

            if (empty($email)) {
                return false;
            }

            // AcyMailing 6+ API
            $userClass = acym_get('class.user');
            $listClass = acym_get('class.list');

            if (!$userClass || !$listClass) {
                throw new \Exception('AcyMailing API not available');
            }

            $subscriber = [
                'email' => $email,
                'name' => $name,
                'source' => 'J2Commerce Checkout',
            ];

            $subId = $userClass->save($subscriber);

            if ($subId) {
                $subscription = [
                    'status' => $this->params->get('double_optin', 1) ? 0 : 1,
                ];
                
                // Subscribe to all configured lists
                foreach ($listIds as $listId) {
                    $listClass->subscribe($subId, $listId, $subscription);
                }

                if ($this->params->get('double_optin', 1)) {
                    $userClass->sendConfirmationEmail($subId);
                }

                $this->getApplication()->enqueueMessage(
                    Text::_('PLG_J2STORE_ACYMAILING_SUCCESS'),
                    'success'
                );
                return true;
            }

            throw new \Exception('Failed to save subscriber');

        } catch (\Exception $e) {
            $this->getApplication()->enqueueMessage(
                Text::sprintf('PLG_J2STORE_ACYMAILING_ERROR', $e->getMessage()),
                'error'
            );
            return false;
        }
    }
}
