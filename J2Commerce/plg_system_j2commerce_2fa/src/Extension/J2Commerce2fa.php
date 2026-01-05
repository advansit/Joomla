<?php
/**
 * @package     Advans.Plugin
 * @subpackage  System.J2Commerce2fa
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\System\J2Commerce2fa\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * J2Commerce 2FA Plugin
 *
 * Fixes Two-Factor Authentication login issues with J2Commerce
 *
 * @since  1.0.0
 */
final class J2Commerce2fa extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onUserAfterLogin'  => 'onUserAfterLogin',
            'onUserBeforeLogout' => 'onUserBeforeLogout',
        ];
    }

    /**
     * This method is called after user login
     *
     * Preserves J2Commerce session and cart after 2FA verification
     *
     * @param   Event  $event  The event object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserAfterLogin(Event $event): void
    {
        // Get plugin parameters
        if (!$this->params->get('enabled', 1)) {
            return;
        }

        $options = $event->getArgument('options', []);
        $user = $event->getArgument('user', null);

        if (!$user) {
            return;
        }

        // Check if this is a 2FA login
        $is2FA = isset($options['twoFactorAuth']) && $options['twoFactorAuth'];

        if ($is2FA) {
            $this->handle2FALogin($user, $options);
        } else {
            // Regular login - preserve guest cart if enabled
            if ($this->params->get('preserve_guest_cart', 1)) {
                $this->preserveGuestCart($user);
            }
        }

        // Debug logging
        if ($this->params->get('debug', 0)) {
            $this->logDebug('User logged in', [
                'user_id' => $user->id,
                'is_2fa' => $is2FA,
            ]);
        }
    }

    /**
     * Handle 2FA login
     *
     * @param   object  $user     The user object
     * @param   array   $options  Login options
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function handle2FALogin(object $user, array $options): void
    {
        $app = Factory::getApplication();
        $session = $app->getSession();

        // Preserve J2Commerce cart
        if ($this->params->get('preserve_cart', 1)) {
            $this->preserveCart($session);
        }

        // Handle return URL
        $this->handleReturnUrl($app, $options);

        // Refresh session
        $this->refreshSession($session);
    }

    /**
     * Preserve shopping cart
     * J2Commerce uses 'j2store' namespace for session data
     *
     * @param   object  $session  The session object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function preserveCart(object $session): void
    {
        // J2Commerce uses 'j2store' namespace, not 'j2commerce'
        $cart = $session->get('cart', null, 'j2store');
        $shipping = $session->get('shipping', null, 'j2store');
        $payment = $session->get('payment', null, 'j2store');
        $billing = $session->get('billing', null, 'j2store');

        if ($cart) {
            // Re-set cart to ensure it's preserved after session regeneration
            $session->set('cart', $cart, 'j2store');
            
            if ($this->params->get('debug', 0)) {
                $itemCount = is_array($cart) ? count($cart) : 0;
                $this->logDebug('J2Commerce cart preserved', ['items' => $itemCount]);
            }
        }
        
        // Preserve shipping info
        if ($shipping) {
            $session->set('shipping', $shipping, 'j2store');
        }
        
        // Preserve payment info
        if ($payment) {
            $session->set('payment', $payment, 'j2store');
        }
        
        // Preserve billing info
        if ($billing) {
            $session->set('billing', $billing, 'j2store');
        }
    }

    /**
     * Handle return URL
     *
     * @param   object  $app      The application object
     * @param   array   $options  Login options
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function handleReturnUrl(object $app, array $options): void
    {
        $return = $app->input->get('return', '', 'base64');

        if ($return) {
            $app->setUserState('users.login.form.return', $return);

            if ($this->params->get('debug', 0)) {
                $this->logDebug('Return URL preserved', ['return' => base64_decode($return)]);
            }
        }
    }

    /**
     * Refresh session
     *
     * @param   object  $session  The session object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function refreshSession(object $session): void
    {
        // Regenerate session ID for security
        $session->regenerate(false);

        if ($this->params->get('debug', 0)) {
            $this->logDebug('Session refreshed', ['session_id' => $session->getId()]);
        }
    }

    /**
     * This method is called before user logout
     *
     * @param   Event  $event  The event object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserBeforeLogout(Event $event): void
    {
        if ($this->params->get('debug', 0)) {
            $user = $event->getArgument('user', null);
            $this->logDebug('User logging out', ['user_id' => $user ? $user->id : 'unknown']);
        }
    }

    /**
     * Preserve guest cart when user logs in
     * Transfers cart from guest session to user account
     *
     * @param   object  $user  The user object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function preserveGuestCart(object $user): void
    {
        $app = Factory::getApplication();
        $session = $app->getSession();
        $db = Factory::getDbo();

        try {
            // Get guest cart ID from session
            $guestCartId = $session->get('cart_id.cart', 0, 'j2store');
            
            if (!$guestCartId) {
                return; // No guest cart to preserve
            }

            // Check if guest cart exists in database
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__j2store_carts'))
                ->where($db->quoteName('j2store_cart_id') . ' = :cartid')
                ->bind(':cartid', $guestCartId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $guestCart = $db->loadObject();

            if (!$guestCart) {
                return; // Guest cart not found
            }

            // Check if user already has a cart
            $query = $db->getQuery(true)
                ->select('j2store_cart_id')
                ->from($db->quoteName('#__j2store_carts'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->bind(':userid', $user->id, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $userCartId = $db->loadResult();

            if ($userCartId) {
                // User has existing cart - merge items
                $this->mergeCartItems($guestCartId, $userCartId);
                
                // Delete guest cart
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__j2store_carts'))
                    ->where($db->quoteName('j2store_cart_id') . ' = :cartid')
                    ->bind(':cartid', $guestCartId, \Joomla\Database\ParameterType::INTEGER);
                $db->setQuery($query);
                $db->execute();
                
                // Update session to user cart
                $session->set('cart_id.cart', $userCartId, 'j2store');
                
                if ($this->params->get('debug', 0)) {
                    $this->logDebug('Guest cart merged with user cart', [
                        'guest_cart_id' => $guestCartId,
                        'user_cart_id' => $userCartId
                    ]);
                }
            } else {
                // User has no cart - transfer guest cart to user
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__j2store_carts'))
                    ->set($db->quoteName('user_id') . ' = :userid')
                    ->where($db->quoteName('j2store_cart_id') . ' = :cartid')
                    ->bind(':userid', $user->id, \Joomla\Database\ParameterType::INTEGER)
                    ->bind(':cartid', $guestCartId, \Joomla\Database\ParameterType::INTEGER);
                
                $db->setQuery($query);
                $db->execute();
                
                if ($this->params->get('debug', 0)) {
                    $this->logDebug('Guest cart transferred to user', [
                        'cart_id' => $guestCartId,
                        'user_id' => $user->id
                    ]);
                }
            }
        } catch (\Exception $e) {
            if ($this->params->get('debug', 0)) {
                $this->logDebug('Error preserving guest cart', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Merge cart items from guest cart to user cart
     *
     * @param   int  $guestCartId  Guest cart ID
     * @param   int  $userCartId   User cart ID
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function mergeCartItems(int $guestCartId, int $userCartId): void
    {
        $db = Factory::getDbo();

        try {
            // Get guest cart items
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__j2store_cartitems'))
                ->where($db->quoteName('cart_id') . ' = :cartid')
                ->bind(':cartid', $guestCartId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $guestItems = $db->loadObjectList();

            foreach ($guestItems as $guestItem) {
                // Check if user cart already has this item
                $query = $db->getQuery(true)
                    ->select('j2store_cartitem_id, product_qty')
                    ->from($db->quoteName('#__j2store_cartitems'))
                    ->where($db->quoteName('cart_id') . ' = :cartid')
                    ->where($db->quoteName('variant_id') . ' = :variantid')
                    ->where($db->quoteName('product_options') . ' = :options')
                    ->bind(':cartid', $userCartId, \Joomla\Database\ParameterType::INTEGER)
                    ->bind(':variantid', $guestItem->variant_id, \Joomla\Database\ParameterType::INTEGER)
                    ->bind(':options', $guestItem->product_options);
                
                $db->setQuery($query);
                $userItem = $db->loadObject();

                if ($userItem) {
                    // Item exists - update quantity
                    $newQty = $userItem->product_qty + $guestItem->product_qty;
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__j2store_cartitems'))
                        ->set($db->quoteName('product_qty') . ' = :qty')
                        ->where($db->quoteName('j2store_cartitem_id') . ' = :itemid')
                        ->bind(':qty', $newQty, \Joomla\Database\ParameterType::INTEGER)
                        ->bind(':itemid', $userItem->j2store_cartitem_id, \Joomla\Database\ParameterType::INTEGER);
                    
                    $db->setQuery($query);
                    $db->execute();
                } else {
                    // Item doesn't exist - transfer to user cart
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__j2store_cartitems'))
                        ->set($db->quoteName('cart_id') . ' = :cartid')
                        ->where($db->quoteName('j2store_cartitem_id') . ' = :itemid')
                        ->bind(':cartid', $userCartId, \Joomla\Database\ParameterType::INTEGER)
                        ->bind(':itemid', $guestItem->j2store_cartitem_id, \Joomla\Database\ParameterType::INTEGER);
                    
                    $db->setQuery($query);
                    $db->execute();
                }
            }
        } catch (\Exception $e) {
            if ($this->params->get('debug', 0)) {
                $this->logDebug('Error merging cart items', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Log debug message
     *
     * @param   string  $message  The message
     * @param   array   $context  Additional context
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function logDebug(string $message, array $context = []): void
    {
        $app = Factory::getApplication();
        $app->enqueueMessage(
            sprintf('[J2Commerce 2FA] %s: %s', $message, json_encode($context)),
            'info'
        );
    }
}
