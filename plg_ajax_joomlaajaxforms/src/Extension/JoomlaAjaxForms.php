<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Ajax.JoomlaAjaxForms
 *
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary License
 */

namespace Advans\Plugin\Ajax\JoomlaAjaxForms\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

class JoomlaAjaxForms extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRoute'          => 'onAfterRoute',
            'onAjaxJoomlaajaxforms' => 'onAjaxJoomlaajaxforms',
            'onBeforeRender'        => 'onBeforeRender',
        ];
    }

    /**
     * Intercept AJAX requests before Joomla's SEF redirect.
     *
     * Joomla 5.x encodes & as &amp; in redirect Location headers,
     * causing infinite 303 loops for com_ajax URLs with multiple
     * query parameters. This handler detects our AJAX requests
     * early and responds directly.
     */
    public function onAfterRoute(): void
    {
        $app = $this->getApplication();
        $input = $app->getInput();

        if ($input->getCmd('option') !== 'com_ajax'
            || $input->getCmd('plugin') !== 'joomlaajaxforms'
            || $input->getCmd('format', 'html') !== 'json'
        ) {
            return;
        }

        // This is our AJAX request — handle it directly
        $result = $this->onAjaxJoomlaajaxforms();

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode(['data' => [$result]], JSON_UNESCAPED_UNICODE);
        $app->close();
    }

    /**
     * Pass language strings to JavaScript via Joomla script options
     */
    public function onBeforeRender(): void
    {
        $doc = $this->getApplication()->getDocument();
        if (method_exists($doc, 'addScriptOptions')) {
            $doc->addScriptOptions('plg_ajax_joomlaajaxforms', [
                'ERROR_GENERIC'          => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_JS_ERROR_GENERIC'),
                'MFA_SELECT_METHOD'      => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_JS_MFA_SELECT_METHOD'),
                'MFA_ENTER_CODE'         => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_JS_MFA_ENTER_CODE'),
                'MFA_METHOD'             => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_JS_MFA_METHOD'),
                'MFA_CODE_LABEL'         => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_JS_MFA_CODE_LABEL'),
                'MFA_CANCEL'             => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_JS_MFA_CANCEL'),
                'MFA_VERIFY'             => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_JS_MFA_VERIFY'),
                'MFA_CODE_INVALID_LENGTH' => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_JS_MFA_CODE_INVALID_LENGTH'),
            ]);
        }
    }



    /**
     * Main AJAX handler
     *
     * @return  string  JSON response
     */
    public function onAjaxJoomlaajaxforms(): string
    {
        // Validate CSRF token
        if (!Session::checkToken('get') && !Session::checkToken('post') && !Session::checkToken()) {
            return $this->jsonError(Text::_('JINVALID_TOKEN'));
        }

        $input = $this->getApplication()->getInput();
        $task = $input->getCmd('task', '');

        switch ($task) {
            case 'login':
                return $this->handleLogin();
            case 'logout':
                return $this->handleLogout();
            case 'register':
                return $this->handleRegistration();
            case 'reset':
                return $this->handleReset();
            case 'remind':
                return $this->handleRemind();
            case 'mfa_validate':
                return $this->handleMfaValidate();
            case 'removeCartItem':
                return $this->handleRemoveCartItem();
            case 'saveProfile':
                return $this->handleSaveProfile();
            default:
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_INVALID_TASK'));
        }
    }

    /**
     * Handle login request
     *
     * @return  string  JSON response
     */
    protected function handleLogin(): string
    {
        if (!$this->params->get('enable_login', 1)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));
        }

        $user = $this->getApplication()->getIdentity();
        if ($user && !$user->guest) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_ALREADY_LOGGED_IN'));
        }

        $input = $this->getApplication()->getInput();
        $username = $input->post->getString('username', '');
        $password = $input->post->getString('password', '');
        $remember = $input->post->getInt('remember', 0);
        $returnUrl = $input->post->getBase64('return', '');

        if (empty($username) || empty($password)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_FAILED'));
        }

        $credentials = [
            'username' => $username,
            'password' => $password,
        ];

        $options = [
            'remember' => (bool) $remember,
            'action'   => 'core.login.site',
        ];

        $result = $this->getApplication()->login($credentials, $options);

        if ($result === true) {
            $user = $this->getApplication()->getIdentity();

            // Check if MFA is required
            $mfaMethods = $this->getMfaMethods($user->id);
            if (!empty($mfaMethods)) {
                // Store credentials in session for MFA validation
                $session = $this->getApplication()->getSession();
                $session->set('mfa_user_id', $user->id);
                $session->set('mfa_pending', true);

                // Logout user until MFA is validated
                $this->getApplication()->logout($user->id);

                return $this->jsonSuccess([
                    'mfa_required' => true,
                    'methods'      => $mfaMethods,
                    'message'      => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_REQUIRED'),
                ]);
            }

            $redirect = '';
            if ($returnUrl) {
                $redirect = base64_decode($returnUrl);
            }
            if (empty($redirect)) {
                $redirect = Uri::base();
            }

            return $this->jsonSuccess([
                'redirect' => $redirect,
                'message'  => Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_SUCCESS', $user->name),
            ]);
        }

        return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_FAILED'));
    }

    /**
     * Handle logout request
     *
     * @return  string  JSON response
     */
    protected function handleLogout(): string
    {
        $user = $this->getApplication()->getIdentity();
        if (!$user || $user->guest) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_NOT_LOGGED_IN'));
        }

        $input = $this->getApplication()->getInput();
        $returnUrl = $input->post->getBase64('return', '');

        $result = $this->getApplication()->logout();

        if ($result === true) {
            $redirect = '';
            if ($returnUrl) {
                $redirect = base64_decode($returnUrl);
            }
            if (empty($redirect)) {
                $redirect = Uri::base();
            }

            return $this->jsonSuccess([
                'redirect' => $redirect,
                'message'  => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGOUT_SUCCESS'),
            ]);
        }

        return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGOUT_FAILED'));
    }

    /**
     * Handle registration request
     *
     * @return  string  JSON response
     */
    protected function handleRegistration(): string
    {
        if (!$this->params->get('enable_registration', 1)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));
        }

        $usersConfig = ComponentHelper::getParams('com_users');
        if (!$usersConfig->get('allowUserRegistration')) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_DISABLED'));
        }

        $input = $this->getApplication()->getInput();
        $name = trim($input->post->getString('name', ''));
        $username = trim($input->post->getString('username', ''));
        $email = trim($input->post->getString('email', ''));
        $email2 = trim($input->post->getString('email2', ''));
        $password = $input->post->getString('password', '');
        $password2 = $input->post->getString('password2', '');

        // Validation
        if (empty($name)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_NAME_REQUIRED'));
        }
        if (empty($username) || strlen($username) < 2) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_USERNAME_TOO_SHORT'));
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_INVALID'));
        }
        if ($email !== $email2) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_MISMATCH'));
        }
        if (empty($password)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_PASSWORD_REQUIRED'));
        }
        if ($password !== $password2) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_PASSWORD_MISMATCH'));
        }

        // Check if username/email already exists
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('username') . ' = ' . $db->quote($username));
        $db->setQuery($query);
        if ($db->loadResult() > 0) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_USERNAME_EXISTS'));
        }

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = ' . $db->quote($email));
        $db->setQuery($query);
        if ($db->loadResult() > 0) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_EXISTS'));
        }

        try {
            $user = new \Joomla\CMS\User\User();
            $user->name = $name;
            $user->username = $username;
            $user->email = $email;
            $user->password = UserHelper::hashPassword($password);

            $userActivation = $usersConfig->get('useractivation');
            $defaultGroup = $usersConfig->get('new_usertype', 2);

            $user->groups = [$defaultGroup];

            if ($userActivation == 1) {
                // Self-activation
                $user->activation = UserHelper::genRandomPassword(32);
                $user->block = 1;
            } elseif ($userActivation == 2) {
                // Admin activation
                $user->activation = UserHelper::genRandomPassword(32);
                $user->block = 1;
            } else {
                $user->block = 0;
            }

            $user->registerDate = Factory::getDate()->toSql();

            if (!$user->save()) {
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_FAILED'));
            }

            // Send activation email if needed
            if ($userActivation == 1) {
                $this->sendActivationEmail($user, $usersConfig);
                return $this->jsonSuccess([
                    'message' => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_ACTIVATE_SELF'),
                ]);
            } elseif ($userActivation == 2) {
                $this->sendActivationEmail($user, $usersConfig);
                $this->sendAdminNotification($user);
                return $this->jsonSuccess([
                    'message' => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_ACTIVATE_ADMIN'),
                ]);
            }

            return $this->jsonSuccess([
                'message' => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_SUCCESS'),
            ]);
        } catch (\Exception $e) {
            Log::add('Registration error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_FAILED'));
        }
    }

    /**
     * Handle password reset request
     *
     * @return  string  JSON response
     */
    protected function handleReset(): string
    {
        if (!$this->params->get('enable_reset', 1)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));
        }

        $input = $this->getApplication()->getInput();
        $email = trim($input->post->getString('email', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_INVALID'));
        }

        // Always return success to prevent email enumeration
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('email') . ' = ' . $db->quote($email))
                ->where($db->quoteName('block') . ' = 0');
            $db->setQuery($query);
            $user = $db->loadObject();

            if ($user) {
                $token = UserHelper::genRandomPassword(32);
                $hashedToken = UserHelper::hashPassword($token);
                $tokenCreated = Factory::getDate()->toSql();

                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__users'))
                    ->set($db->quoteName('activation') . ' = ' . $db->quote($hashedToken))
                    ->set($db->quoteName('lastResetTime') . ' = ' . $db->quote($tokenCreated))
                    ->where($db->quoteName('id') . ' = ' . (int) $user->id);
                $db->setQuery($query);
                $db->execute();

                $this->sendResetEmail($user, $token);
            }
        } catch (\Exception $e) {
            Log::add('Password reset error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
        }

        return $this->jsonSuccess([
            'message' => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_RESET_SUCCESS'),
        ]);
    }

    /**
     * Handle username reminder request
     *
     * @return  string  JSON response
     */
    protected function handleRemind(): string
    {
        if (!$this->params->get('enable_remind', 1)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));
        }

        $input = $this->getApplication()->getInput();
        $email = trim($input->post->getString('email', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_INVALID'));
        }

        // Always return success to prevent email enumeration
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('email') . ' = ' . $db->quote($email))
                ->where($db->quoteName('block') . ' = 0');
            $db->setQuery($query);
            $user = $db->loadObject();

            if ($user) {
                $this->sendRemindEmail($user);
            }
        } catch (\Exception $e) {
            Log::add('Username remind error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
        }

        return $this->jsonSuccess([
            'message' => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REMIND_SUCCESS'),
        ]);
    }

    /**
     * Handle MFA code validation
     *
     * @return  string  JSON response
     */
    protected function handleMfaValidate(): string
    {
        $session = $this->getApplication()->getSession();
        $userId = $session->get('mfa_user_id', 0);
        $pending = $session->get('mfa_pending', false);

        if (!$userId || !$pending) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_SESSION_EXPIRED'));
        }

        $input = $this->getApplication()->getInput();
        $code = trim($input->post->getString('code', ''));
        $recordId = $input->post->getInt('record_id', 0);

        if (empty($code)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_CODE_REQUIRED'));
        }

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            // Get the MFA record
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__user_mfa'))
                ->where($db->quoteName('id') . ' = ' . (int) $recordId)
                ->where($db->quoteName('user_id') . ' = ' . (int) $userId);
            $db->setQuery($query);
            $record = $db->loadObject();

            if (!$record) {
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_INVALID_METHOD'));
            }

            // Validate the code using Joomla's MFA system
            $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);

            // Use the MFA plugin to validate
            $this->getApplication()->getDispatcher()->dispatch('onUserMultifactorValidate', new \Joomla\Event\Event('onUserMultifactorValidate', [$record, $code]));

            // If we get here without exception, the code is valid
            // Clear MFA session data
            $session->clear('mfa_user_id');
            $session->clear('mfa_pending');

            // Log the user in
            $credentials = ['username' => $user->username];
            $options = ['action' => 'core.login.site', 'skip_mfa' => true];
            $this->getApplication()->login($credentials, $options);

            return $this->jsonSuccess([
                'redirect' => Uri::base(),
                'message'  => Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_SUCCESS', $user->name),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_CODE_INVALID'));
        }
    }

    /**
     * Handle cart item removal via J2Store API
     */
    protected function handleRemoveCartItem(): string
    {
        $input = $this->getApplication()->getInput();
        $cartitemId = $input->getInt('cartitem_id', 0);

        if (!$cartitemId) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_CART_REMOVE_FAILED'));
        }

        try {
            if (!class_exists('J2Store')) {
                require_once JPATH_ADMINISTRATOR . '/components/com_j2store/helpers/j2store.php';
            }

            // Remove cart item via direct DB query (compatible with all J2Store/J2Commerce versions)
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__j2store_cartitems'))
                ->where($db->quoteName('j2store_cartitem_id') . ' = ' . (int) $cartitemId);
            $db->setQuery($query);
            $db->execute();

            // Get updated cart info
            $helper = \J2Store::helper('Cart');
            $cartItems = $helper->getItems();
            $cartCount = 0;
            $cartTotal = 0;
            foreach ($cartItems as $item) {
                $cartCount += $item->orderitem_quantity;
                $cartTotal += $item->orderitem_finalprice;
            }

            // Format the total using J2Store currency
            $currency = \J2Store::currency();
            $formattedTotal = $currency->format($cartTotal);

            return $this->jsonSuccess([
                'cartCount' => $cartCount,
                'cartTotal' => Text::sprintf('J2STORE_CART_TOTAL', $cartCount, $formattedTotal),
                'message'   => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_CART_ITEM_REMOVED'),
            ]);
        } catch (\Exception $e) {
            Log::add('Cart remove error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_CART_REMOVE_FAILED'));
        }
    }

    /**
     * Handle profile save
     */
    protected function handleSaveProfile(): string
    {
        $user = $this->getApplication()->getIdentity();
        if (!$user || $user->guest) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_NOT_LOGGED_IN'));
        }

        $input = $this->getApplication()->getInput();

        try {
            $data = [
                'name'  => trim($input->post->getString('name', '')),
                'email' => trim($input->post->getString('email', '')),
            ];

            if (empty($data['name'])) {
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_NAME_REQUIRED'));
            }
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_INVALID'));
            }

            $user->name = $data['name'];
            $user->email = $data['email'];

            // Update password if provided
            $password = $input->post->getString('password', '');
            $password2 = $input->post->getString('password2', '');
            if (!empty($password)) {
                if ($password !== $password2) {
                    return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_PASSWORD_MISMATCH'));
                }
                $user->password = UserHelper::hashPassword($password);
            }

            if (!$user->save(true)) {
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_PROFILE_SAVE_FAILED'));
            }

            return $this->jsonSuccess([
                'message' => Text::_('PLG_AJAX_JOOMLAAJAXFORMS_PROFILE_SAVED'),
            ]);
        } catch (\Exception $e) {
            Log::add('Profile save error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_PROFILE_SAVE_FAILED'));
        }
    }

    /**
     * Get MFA methods for a user
     *
     * @param   int  $userId  User ID
     *
     * @return  array  MFA methods
     */
    protected function getMfaMethods(int $userId): array
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select([$db->quoteName('id'), $db->quoteName('title'), $db->quoteName('method')])
                ->from($db->quoteName('#__user_mfa'))
                ->where($db->quoteName('user_id') . ' = ' . (int) $userId);
            $db->setQuery($query);
            $records = $db->loadObjectList();

            if (empty($records)) {
                return [];
            }

            $methods = [];
            foreach ($records as $record) {
                $methods[] = [
                    'id'     => $record->id,
                    'title'  => $record->title,
                    'method' => $record->method,
                ];
            }

            return $methods;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Send password reset email
     *
     * @param   object  $user   User object
     * @param   string  $token  Reset token
     *
     * @return  void
     */
    protected function sendResetEmail(object $user, string $token): void
    {
        try {
            $siteName = $this->getApplication()->get('sitename');
            $resetLink = Uri::root() . 'index.php?option=com_users&view=reset&layout=confirm&token=' . $token;

            $subject = Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_RESET_EMAIL_SUBJECT', $siteName);
            $body = Text::sprintf(
                'PLG_AJAX_JOOMLAAJAXFORMS_RESET_EMAIL_BODY',
                $user->name,
                $siteName,
                $resetLink,
                $token,
                $siteName
            );

            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $mailer->addRecipient($user->email, $user->name);
            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $mailer->Send();
        } catch (\Exception $e) {
            Log::add('Reset email error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
        }
    }

    /**
     * Send username reminder email
     *
     * @param   object  $user  User object
     *
     * @return  void
     */
    protected function sendRemindEmail(object $user): void
    {
        try {
            $siteName = $this->getApplication()->get('sitename');
            $loginLink = Uri::root() . 'index.php?option=com_users&view=login';

            $subject = Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_REMIND_EMAIL_SUBJECT', $siteName);
            $body = Text::sprintf(
                'PLG_AJAX_JOOMLAAJAXFORMS_REMIND_EMAIL_BODY',
                $user->name,
                $siteName,
                $user->username,
                $loginLink,
                $siteName
            );

            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $mailer->addRecipient($user->email, $user->name);
            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $mailer->Send();
        } catch (\Exception $e) {
            Log::add('Remind email error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
        }
    }

    /**
     * Send activation email
     *
     * @param   \Joomla\CMS\User\User  $user    User object
     * @param   \Joomla\Registry\Registry  $config  Users config
     *
     * @return  void
     */
    protected function sendActivationEmail($user, $config): void
    {
        try {
            $siteName = $this->getApplication()->get('sitename');
            $activationLink = Uri::root() . 'index.php?option=com_users&task=registration.activate&token=' . $user->activation;

            $subject = Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_ACTIVATION_EMAIL_SUBJECT', $siteName);
            $body = Text::sprintf(
                'PLG_AJAX_JOOMLAAJAXFORMS_ACTIVATION_EMAIL_BODY',
                $user->name,
                $siteName,
                $activationLink,
                $user->activation,
                $siteName
            );

            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $mailer->addRecipient($user->email, $user->name);
            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $mailer->Send();
        } catch (\Exception $e) {
            Log::add('Activation email error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
        }
    }

    /**
     * Send admin notification about new registration
     *
     * @param   \Joomla\CMS\User\User  $user  User object
     *
     * @return  void
     */
    protected function sendAdminNotification($user): void
    {
        try {
            $siteName = $this->getApplication()->get('sitename');
            $adminEmail = $this->getApplication()->get('mailfrom');

            $subject = Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_ADMIN_ACTIVATION_EMAIL_SUBJECT', $siteName);
            $body = Text::sprintf(
                'PLG_AJAX_JOOMLAAJAXFORMS_ADMIN_ACTIVATION_EMAIL_BODY',
                $siteName,
                $user->name,
                $user->username,
                $user->email
            );

            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $mailer->addRecipient($adminEmail);
            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $mailer->Send();
        } catch (\Exception $e) {
            Log::add('Admin notification error: ' . $e->getMessage(), Log::ERROR, 'plg_ajax_joomlaajaxforms');
        }
    }

    /**
     * Return a JSON success response
     *
     * @param   array  $data  Additional data
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
     *
     * @return  string
     */
    protected function jsonError(string $message): string
    {
        return json_encode(['success' => false, 'message' => $message]);
    }
}
