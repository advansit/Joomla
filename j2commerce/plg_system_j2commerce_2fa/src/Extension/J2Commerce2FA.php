<?php
/**
 * @package     J2Commerce 2FA Plugin
 * @subpackage  Extension
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\System\J2Commerce2FA\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

class J2Commerce2FA extends CMSPlugin
{
    protected $autoloadLanguage = true;

    /**
     * Handle user login - preserve session and cart data after 2FA
     *
     * @param   array  $user     User data
     * @param   array  $options  Login options
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function onUserAfterLogin($user, $options = [])
    {
        try {
            $app = Factory::getApplication();
            $session = $app->getSession();
            
            // Check if this is a 2FA login
            $is2FA = isset($options['twoFactorAuth']) && $options['twoFactorAuth'];
            
            if ($is2FA) {
                // Preserve J2Commerce/J2Store cart data after 2FA verification
                if ($this->params->get('preserve_cart', 1)) {
                    $this->preserveCartData($session);
                }
                
                // Preserve session data
                if ($this->params->get('preserve_session', 1)) {
                    $this->preserveSessionData($session);
                }
            } else {
                // Regular login - transfer guest cart if enabled
                if ($this->params->get('preserve_guest_cart', 1)) {
                    $this->transferGuestCart($session, $user);
                }
            }
            
            // Regenerate session ID for security
            if ($this->params->get('regenerate_session', 1)) {
                $session->restart();
            }
        } catch (\Exception $e) {
            // Log error but don't break login
            if ($this->params->get('debug', 0)) {
                Factory::getApplication()->enqueueMessage(
                    'J2Commerce 2FA Plugin Error: ' . $e->getMessage(),
                    'warning'
                );
            }
        }
        
        return true;
    }

    /**
     * Preserve cart data during 2FA login
     *
     * @param   object  $session  Session object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function preserveCartData($session)
    {
        // Preserve J2Store cart
        $cartData = $session->get('j2store.cart', null);
        if ($cartData) {
            $session->set('j2store.cart.preserved', $cartData);
        }
        
        // Preserve J2Commerce cart (if different)
        $j2commerceCart = $session->get('j2commerce.cart', null);
        if ($j2commerceCart) {
            $session->set('j2commerce.cart.preserved', $j2commerceCart);
        }
    }

    /**
     * Preserve session data during 2FA login
     *
     * @param   object  $session  Session object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function preserveSessionData($session)
    {
        // Preserve return URL
        $returnUrl = $session->get('return_url', null);
        if ($returnUrl) {
            $session->set('return_url.preserved', $returnUrl);
        }
        
        // Preserve checkout data
        $checkoutData = $session->get('checkout.data', null);
        if ($checkoutData) {
            $session->set('checkout.data.preserved', $checkoutData);
        }
    }

    /**
     * Transfer guest cart to logged-in user
     *
     * @param   object  $session  Session object
     * @param   array   $user     User data
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function transferGuestCart($session, $user)
    {
        $guestCart = $session->get('j2store.cart.guest', null);
        
        if ($guestCart && isset($user['id'])) {
            // Transfer guest cart to user cart
            $session->set('j2store.cart.user_' . $user['id'], $guestCart);
            $session->clear('j2store.cart.guest');
        }
    }
}
