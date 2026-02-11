<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Ajax.Advans
 *
 * @copyright   Copyright (C) 2025-2026 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary License
 */

namespace Advans\Plugin\Ajax\Advans\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

/**
 * Advans AJAX Plugin
 *
 * Handles AJAX requests for:
 * - J2Store minicart item removal (without page redirect)
 * - User profile updates (without page redirect)
 */
class Advans extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Application instance
     *
     * @var CMSApplicationInterface
     */
    protected $app;

    /**
     * Load plugin language files automatically
     *
     * @var boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher  The event dispatcher
     * @param   array                $config      Plugin configuration
     */
    public function __construct(DispatcherInterface $dispatcher, array $config)
    {
        parent::__construct($dispatcher, $config);
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAjaxAdvans' => 'onAjaxAdvans',
        ];
    }

    /**
     * Main AJAX handler
     *
     * @param   Event  $event  The event object
     *
     * @return  void
     */
    public function onAjaxAdvans(Event $event): void
    {
        $result = $this->handleRequest();
        $event->addResult($result);
    }

    /**
     * Handle the AJAX request
     *
     * @return string JSON response
     */
    protected function handleRequest(): string
    {
        // Verify CSRF token
        if (!Session::checkToken('get') && !Session::checkToken('post')) {
            return $this->jsonError(Text::_('JINVALID_TOKEN'));
        }

        $input = $this->app->getInput();
        $action = $input->getCmd('action', '');

        switch ($action) {
            case 'removeCartItem':
                return $this->removeCartItem();

            case 'saveProfile':
                return $this->saveProfile();

            case 'getCartCount':
                return $this->getCartCount();

            default:
                return $this->jsonError(Text::_('PLG_AJAX_ADVANS_INVALID_ACTION'));
        }
    }

    /**
     * Remove an item from the J2Store cart
     *
     * @return string JSON response
     */
    protected function removeCartItem(): string
    {
        $input = $this->app->getInput();
        $cartItemId = $input->getInt('cartitem_id', 0);

        if ($cartItemId <= 0) {
            return $this->jsonError(Text::_('PLG_AJAX_ADVANS_INVALID_CART_ITEM'));
        }

        // Check if J2Store is installed
        if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_j2store/j2store.php')) {
            return $this->jsonError(Text::_('PLG_AJAX_ADVANS_J2STORE_NOT_FOUND'));
        }

        try {
            // Load J2Store
            require_once JPATH_ADMINISTRATOR . '/components/com_j2store/helpers/j2store.php';
            
            // Get the cart model
            $cart = \J2Store::cart();
            
            // Remove the item
            $result = $cart->removeItem($cartItemId);
            
            if ($result) {
                // Get updated cart info
                $cartItems = $cart->getItems();
                $cartCount = count($cartItems);
                $cartTotal = $cart->getTotal();
                
                return $this->jsonSuccess([
                    'message' => Text::_('PLG_AJAX_ADVANS_ITEM_REMOVED'),
                    'cartCount' => $cartCount,
                    'cartTotal' => $cartTotal,
                    'cartItemId' => $cartItemId,
                ]);
            } else {
                return $this->jsonError(Text::_('PLG_AJAX_ADVANS_REMOVE_FAILED'));
            }
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get current cart item count
     *
     * @return string JSON response
     */
    protected function getCartCount(): string
    {
        try {
            if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_j2store/j2store.php')) {
                return $this->jsonError(Text::_('PLG_AJAX_ADVANS_J2STORE_NOT_FOUND'));
            }

            require_once JPATH_ADMINISTRATOR . '/components/com_j2store/helpers/j2store.php';
            
            $cart = \J2Store::cart();
            $cartItems = $cart->getItems();
            $cartCount = count($cartItems);
            
            return $this->jsonSuccess([
                'cartCount' => $cartCount,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Save user profile data
     *
     * @return string JSON response
     */
    protected function saveProfile(): string
    {
        $user = $this->app->getIdentity();

        if ($user->guest) {
            return $this->jsonError(Text::_('PLG_AJAX_ADVANS_NOT_LOGGED_IN'));
        }

        $input = $this->app->getInput();
        $data = $input->post->getArray();

        // Validate required fields
        $name = trim($input->post->getString('name', ''));
        $email = trim($input->post->getString('email', ''));

        if (empty($name)) {
            return $this->jsonError(Text::_('PLG_AJAX_ADVANS_NAME_REQUIRED'));
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError(Text::_('PLG_AJAX_ADVANS_EMAIL_INVALID'));
        }

        try {
            // Check if email is already used by another user
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('email') . ' = ' . $db->quote($email))
                ->where($db->quoteName('id') . ' != ' . (int) $user->id);
            $db->setQuery($query);
            
            if ($db->loadResult()) {
                return $this->jsonError(Text::_('PLG_AJAX_ADVANS_EMAIL_EXISTS'));
            }

            // Update user data
            $userTable = $user->getTable();
            $userTable->load($user->id);
            
            $userTable->name = $name;
            $userTable->email = $email;

            // Handle password change if provided
            $password1 = $input->post->getString('password1', '');
            $password2 = $input->post->getString('password2', '');

            if (!empty($password1)) {
                if ($password1 !== $password2) {
                    return $this->jsonError(Text::_('PLG_AJAX_ADVANS_PASSWORDS_MISMATCH'));
                }
                
                if (strlen($password1) < 12) {
                    return $this->jsonError(Text::_('PLG_AJAX_ADVANS_PASSWORD_TOO_SHORT'));
                }

                $userTable->password = \Joomla\CMS\User\UserHelper::hashPassword($password1);
            }

            if (!$userTable->store()) {
                return $this->jsonError($userTable->getError());
            }

            // Update session with new user data
            $user->name = $name;
            $user->email = $email;

            return $this->jsonSuccess([
                'message' => Text::_('PLG_AJAX_ADVANS_PROFILE_SAVED'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Return a JSON success response
     *
     * @param   array  $data  Response data
     *
     * @return  string
     */
    protected function jsonSuccess(array $data = []): string
    {
        return json_encode(array_merge(['success' => true], $data));
    }

    /**
     * Return a JSON error response
     *
     * @param   string  $message  Error message
     * @param   int     $code     Error code
     *
     * @return  string
     */
    protected function jsonError(string $message, int $code = 400): string
    {
        return json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
        ]);
    }
}
