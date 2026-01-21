<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Ajax.JoomlaAjaxForms
 *
 * @copyright   Copyright (C) 2025-2026 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary License - See LICENSE.txt
 */

namespace Advans\Plugin\Ajax\JoomlaAjaxForms\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Users\Administrator\Helper\Mfa as MfaHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

/**
 * Joomla AJAX Forms Plugin
 *
 * Provides AJAX handling for Joomla core forms (password reset, username reminder, etc.)
 * Returns JSON responses for seamless user experience without page reloads.
 */
class JoomlaAjaxForms extends CMSPlugin implements SubscriberInterface
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
            'onAjaxJoomlaajaxforms' => 'onAjaxJoomlaajaxforms',
        ];
    }

    /**
     * Main AJAX handler
     *
     * @param   Event  $event  The event object
     *
     * @return  void
     */
    public function onAjaxJoomlaajaxforms(Event $event): void
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

        $task = $this->app->input->get('task', '', 'cmd');

        switch ($task) {
            case 'login':
                if ($this->params->get('enable_login', 1)) {
                    return $this->handleLogin();
                }
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));

            case 'logout':
                if ($this->params->get('enable_login', 1)) {
                    return $this->handleLogout();
                }
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));

            case 'register':
                if ($this->params->get('enable_registration', 1)) {
                    return $this->handleRegistration();
                }
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));

            case 'reset':
                if ($this->params->get('enable_reset', 1)) {
                    return $this->handleReset();
                }
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));

            case 'remind':
                if ($this->params->get('enable_remind', 1)) {
                    return $this->handleRemind();
                }
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));

            case 'mfa_validate':
                if ($this->params->get('enable_login', 1)) {
                    return $this->handleMfaValidate();
                }
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_TASK_DISABLED'));

            default:
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_INVALID_TASK'));
        }
    }

    /**
     * Handle login request
     *
     * @return string JSON response
     */
    protected function handleLogin(): string
    {
        $username = $this->app->input->post->get('username', '', 'username');
        $password = $this->app->input->post->get('password', '', 'raw');
        $remember = $this->app->input->post->get('remember', false, 'bool');

        // Validate input
        if (empty($username)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_USERNAME_REQUIRED'));
        }

        if (empty($password)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_PASSWORD_REQUIRED'));
        }

        // Check if user is already logged in
        $user = $this->app->getIdentity();
        if ($user && !$user->guest) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_ALREADY_LOGGED_IN'));
        }

        // First, authenticate without logging in to check MFA
        $authenticate = Authentication::getInstance();
        $response = $authenticate->authenticate([
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->status !== Authentication::STATUS_SUCCESS) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_FAILED'));
        }

        // Get user to check MFA
        $userId = (int) User::getInstance($username)->id;
        
        if ($userId === 0) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_FAILED'));
        }

        // Check if MFA is enabled for this user
        $mfaRecords = $this->getUserMfaRecords($userId);
        
        if (!empty($mfaRecords)) {
            // MFA is required - store credentials in session for second step
            $session = $this->app->getSession();
            $session->set('ajax_mfa_user_id', $userId);
            $session->set('ajax_mfa_remember', $remember);
            $session->set('ajax_mfa_return', $this->app->input->post->get('return', '', 'base64'));
            $session->set('ajax_mfa_timestamp', time());

            // Get available MFA methods
            $methods = [];
            foreach ($mfaRecords as $record) {
                $methods[] = [
                    'id'     => $record->id,
                    'method' => $record->method,
                    'title'  => $record->title,
                ];
            }

            return $this->jsonSuccess(
                Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_REQUIRED'),
                [
                    'mfa_required' => true,
                    'methods'      => $methods,
                    'default_id'   => $mfaRecords[0]->id,
                ]
            );
        }

        // No MFA - proceed with normal login
        $credentials = [
            'username' => $username,
            'password' => $password,
        ];

        $options = [
            'remember' => $remember,
            'silent'   => true,
        ];

        $result = $this->app->login($credentials, $options);

        if ($result === true) {
            $user = $this->app->getIdentity();
            
            return $this->jsonSuccess(
                Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_SUCCESS', $user->name),
                [
                    'user' => [
                        'id'       => $user->id,
                        'name'     => $user->name,
                        'username' => $user->username,
                    ],
                    'redirect' => $this->getLoginRedirect(),
                ]
            );
        }

        return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_FAILED'));
    }

    /**
     * Handle MFA validation request
     *
     * @return string JSON response
     */
    protected function handleMfaValidate(): string
    {
        $session = $this->app->getSession();
        
        // Get stored MFA session data
        $userId = $session->get('ajax_mfa_user_id', 0);
        $remember = $session->get('ajax_mfa_remember', false);
        $timestamp = $session->get('ajax_mfa_timestamp', 0);
        
        // Validate session (max 5 minutes)
        if (!$userId || (time() - $timestamp) > 300) {
            $this->clearMfaSession();
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_SESSION_EXPIRED'));
        }

        $code = $this->app->input->post->get('code', '', 'string');
        $recordId = $this->app->input->post->get('record_id', 0, 'int');

        if (empty($code)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_CODE_REQUIRED'));
        }

        // Get the MFA record
        $record = $this->getMfaRecord($recordId, $userId);
        
        if (!$record) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_INVALID_METHOD'));
        }

        // Validate the MFA code
        if (!$this->validateMfaCode($record, $code)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_MFA_CODE_INVALID'));
        }

        // MFA validated - complete login
        $user = User::getInstance($userId);
        
        // Clear MFA session data
        $this->clearMfaSession();

        // Force login the user
        $options = [
            'remember' => $remember,
            'silent'   => true,
        ];

        // Set the user as logged in
        $session->set('user', $user);
        $this->app->loadIdentity($user);

        // Trigger login event
        PluginHelper::importPlugin('user');
        $this->app->triggerEvent('onUserLogin', [(array) $user, $options]);

        return $this->jsonSuccess(
            Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_LOGIN_SUCCESS', $user->name),
            [
                'user' => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'username' => $user->username,
                ],
                'redirect' => $this->getLoginRedirect(),
            ]
        );
    }

    /**
     * Get MFA records for a user
     *
     * @param   int  $userId  User ID
     *
     * @return  array
     */
    protected function getUserMfaRecords(int $userId): array
    {
        // Check if MFA plugins are enabled
        if (!PluginHelper::isEnabled('multifactorauth')) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__user_mfa'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        
        try {
            return $db->loadObjectList() ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get a specific MFA record
     *
     * @param   int  $recordId  Record ID
     * @param   int  $userId    User ID
     *
     * @return  object|null
     */
    protected function getMfaRecord(int $recordId, int $userId): ?object
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__user_mfa'))
            ->where($db->quoteName('id') . ' = :recordId')
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':recordId', $recordId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        
        try {
            return $db->loadObject();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate MFA code
     *
     * @param   object  $record  MFA record
     * @param   string  $code    User-provided code
     *
     * @return  bool
     */
    protected function validateMfaCode(object $record, string $code): bool
    {
        // Import MFA plugins
        PluginHelper::importPlugin('multifactorauth');

        // Get the MFA plugin for this method
        $event = new \Joomla\CMS\Event\MultiFactor\Validate($record, $this->app->getIdentity() ?: User::getInstance($record->user_id), $code);
        
        try {
            $this->app->getDispatcher()->dispatch('onUserMultifactorValidate', $event);
            return $event->isValid();
        } catch (\Exception $e) {
            // Fallback: Manual TOTP validation for the most common case
            if ($record->method === 'totp') {
                return $this->validateTotpCode($record, $code);
            }
            return false;
        }
    }

    /**
     * Validate TOTP code manually (fallback)
     *
     * @param   object  $record  MFA record
     * @param   string  $code    User-provided code
     *
     * @return  bool
     */
    protected function validateTotpCode(object $record, string $code): bool
    {
        $options = json_decode($record->options, true);
        
        if (empty($options['key'])) {
            return false;
        }

        $secret = $options['key'];
        $code = preg_replace('/\s+/', '', $code);
        
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        // Check current and adjacent time windows (30 second intervals)
        $timeSlice = floor(time() / 30);
        
        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->getTotpCode($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate TOTP code for a given time slice
     *
     * @param   string  $secret     Base32 encoded secret
     * @param   int     $timeSlice  Time slice
     *
     * @return  string
     */
    protected function getTotpCode(string $secret, int $timeSlice): string
    {
        // Decode base32 secret
        $secret = $this->base32Decode($secret);
        
        // Pack time slice as 64-bit big-endian
        $time = pack('N*', 0, $timeSlice);
        
        // Calculate HMAC-SHA1
        $hash = hash_hmac('sha1', $time, $secret, true);
        
        // Get offset from last nibble
        $offset = ord($hash[19]) & 0x0F;
        
        // Get 4 bytes from offset
        $binary = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);
        
        // Get 6-digit code
        return str_pad($binary % 1000000, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode base32 string
     *
     * @param   string  $input  Base32 encoded string
     *
     * @return  string
     */
    protected function base32Decode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $buffer = ($buffer << 5) | strpos($alphabet, $input[$i]);
            $bitsLeft += 5;
            
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * Clear MFA session data
     *
     * @return  void
     */
    protected function clearMfaSession(): void
    {
        $session = $this->app->getSession();
        $session->clear('ajax_mfa_user_id');
        $session->clear('ajax_mfa_remember');
        $session->clear('ajax_mfa_return');
        $session->clear('ajax_mfa_timestamp');
    }

    /**
     * Handle logout request
     *
     * @return string JSON response
     */
    protected function handleLogout(): string
    {
        $user = $this->app->getIdentity();
        
        if (!$user || $user->guest) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_NOT_LOGGED_IN'));
        }

        $result = $this->app->logout();

        if ($result === true) {
            return $this->jsonSuccess(
                Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGOUT_SUCCESS'),
                ['redirect' => $this->getLogoutRedirect()]
            );
        }

        return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_LOGOUT_FAILED'));
    }

    /**
     * Handle registration request
     *
     * @return string JSON response
     */
    protected function handleRegistration(): string
    {
        // Check if registration is allowed
        $usersConfig = ComponentHelper::getParams('com_users');
        if (!$usersConfig->get('allowUserRegistration', 0)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_DISABLED'));
        }

        // Get form data
        $name      = $this->app->input->post->get('name', '', 'string');
        $username  = $this->app->input->post->get('username', '', 'username');
        $email     = $this->app->input->post->get('email', '', 'string');
        $email2    = $this->app->input->post->get('email2', '', 'string');
        $password  = $this->app->input->post->get('password', '', 'raw');
        $password2 = $this->app->input->post->get('password2', '', 'raw');

        // Validate required fields
        if (empty($name)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_NAME_REQUIRED'));
        }

        if (empty($username)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_USERNAME_REQUIRED'));
        }

        if (empty($email)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_REQUIRED'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

        // Check username length
        if (strlen($username) < 2) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_USERNAME_TOO_SHORT'));
        }

        // Check if username already exists
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('username') . ' = :username')
            ->bind(':username', $username);

        $db->setQuery($query);
        if ($db->loadResult()) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_USERNAME_EXISTS'));
        }

        // Check if email already exists
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = :email')
            ->bind(':email', $email);

        $db->setQuery($query);
        if ($db->loadResult()) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_EXISTS'));
        }

        // Create user
        $user = new User();
        $data = [
            'name'      => $name,
            'username'  => $username,
            'email'     => $email,
            'password'  => $password,
            'password2' => $password2,
        ];

        // Get default user group
        $defaultUserGroup = $usersConfig->get('new_usertype', 2);
        $data['groups'] = [$defaultUserGroup];

        // Check if activation is required
        $userActivation = $usersConfig->get('useractivation', 0);
        if ($userActivation == 1) {
            // Self-activation
            $data['activation'] = UserHelper::genRandomPassword(32);
            $data['block'] = 1;
        } elseif ($userActivation == 2) {
            // Admin activation
            $data['activation'] = UserHelper::genRandomPassword(32);
            $data['block'] = 1;
        }

        // Bind and save
        if (!$user->bind($data)) {
            return $this->jsonError($user->getError() ?: Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_FAILED'));
        }

        if (!$user->save()) {
            return $this->jsonError($user->getError() ?: Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_FAILED'));
        }

        // Send activation email if required
        if ($userActivation == 1) {
            $this->sendActivationEmail($user, $data['activation']);
            return $this->jsonSuccess(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_ACTIVATE_SELF'));
        } elseif ($userActivation == 2) {
            $this->sendActivationEmail($user, $data['activation']);
            $this->sendAdminActivationEmail($user);
            return $this->jsonSuccess(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_ACTIVATE_ADMIN'));
        }

        return $this->jsonSuccess(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REGISTRATION_SUCCESS'));
    }

    /**
     * Get login redirect URL
     *
     * @return string
     */
    protected function getLoginRedirect(): string
    {
        $return = $this->app->input->post->get('return', '', 'base64');
        
        if ($return) {
            $return = base64_decode($return);
            if (\Joomla\CMS\Uri\Uri::isInternal($return)) {
                return $return;
            }
        }

        return \Joomla\CMS\Uri\Uri::root();
    }

    /**
     * Get logout redirect URL
     *
     * @return string
     */
    protected function getLogoutRedirect(): string
    {
        $return = $this->app->input->post->get('return', '', 'base64');
        
        if ($return) {
            $return = base64_decode($return);
            if (\Joomla\CMS\Uri\Uri::isInternal($return)) {
                return $return;
            }
        }

        return \Joomla\CMS\Uri\Uri::root();
    }

    /**
     * Send activation email to user
     *
     * @param   User    $user        User object
     * @param   string  $activation  Activation token
     *
     * @return boolean
     */
    protected function sendActivationEmail(User $user, string $activation): bool
    {
        $config = Factory::getApplication()->getConfig();
        $sitename = $config->get('sitename');
        $mailfrom = $config->get('mailfrom');
        $fromname = $config->get('fromname');

        $link = \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_users&task=registration.activate&token=' . $activation;

        $subject = Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_ACTIVATION_EMAIL_SUBJECT', $sitename);
        $body = Text::sprintf(
            'PLG_AJAX_JOOMLAAJAXFORMS_ACTIVATION_EMAIL_BODY',
            $user->name,
            $sitename,
            $link,
            $activation
        );

        try {
            $mailer = Factory::getMailer();
            $mailer->setSender([$mailfrom, $fromname]);
            $mailer->addRecipient($user->email);
            $mailer->setSubject($subject);
            $mailer->setBody($body);

            return $mailer->Send();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Send notification to admins about new registration
     *
     * @param   User  $user  User object
     *
     * @return boolean
     */
    protected function sendAdminActivationEmail(User $user): bool
    {
        $config = Factory::getApplication()->getConfig();
        $sitename = $config->get('sitename');
        $mailfrom = $config->get('mailfrom');
        $fromname = $config->get('fromname');

        // Get admin users
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('email'))
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('sendEmail') . ' = 1')
            ->where($db->quoteName('block') . ' = 0');

        $db->setQuery($query);
        $adminEmails = $db->loadColumn();

        if (empty($adminEmails)) {
            return true;
        }

        $subject = Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_ADMIN_ACTIVATION_EMAIL_SUBJECT', $sitename);
        $body = Text::sprintf(
            'PLG_AJAX_JOOMLAAJAXFORMS_ADMIN_ACTIVATION_EMAIL_BODY',
            $sitename,
            $user->name,
            $user->username,
            $user->email
        );

        try {
            $mailer = Factory::getMailer();
            $mailer->setSender([$mailfrom, $fromname]);
            $mailer->addRecipient($adminEmails);
            $mailer->setSubject($subject);
            $mailer->setBody($body);

            return $mailer->Send();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Handle password reset request
     *
     * @return string JSON response
     */
    protected function handleReset(): string
    {
        $email = $this->app->input->post->get('email', '', 'string');
        $email = trim($email);

        // Validate email
        if (empty($email)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_REQUIRED'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_INVALID'));
        }

        // Find user by email
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'username', 'email', 'block']))
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = :email')
            ->bind(':email', $email);

        $db->setQuery($query);
        $user = $db->loadObject();

        // Security: Always return success message to prevent email enumeration
        if (!$user || $user->block) {
            // Log the attempt but return generic success
            return $this->jsonSuccess(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_RESET_SUCCESS'));
        }

        // Generate reset token
        $token = UserHelper::genRandomPassword(32);
        $hashedToken = UserHelper::hashPassword($token);

        // Store token in database
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__users'))
            ->set($db->quoteName('activation') . ' = :token')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':token', $hashedToken)
            ->bind(':id', $user->id);

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\Exception $e) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_ERROR_DATABASE'));
        }

        // Send reset email
        if (!$this->sendResetEmail($user, $token)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_ERROR_EMAIL'));
        }

        return $this->jsonSuccess(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_RESET_SUCCESS'));
    }

    /**
     * Handle username reminder request
     *
     * @return string JSON response
     */
    protected function handleRemind(): string
    {
        $email = $this->app->input->post->get('email', '', 'string');
        $email = trim($email);

        // Validate email
        if (empty($email)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_REQUIRED'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_EMAIL_INVALID'));
        }

        // Find user by email
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'username', 'email', 'name', 'block']))
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = :email')
            ->bind(':email', $email);

        $db->setQuery($query);
        $user = $db->loadObject();

        // Security: Always return success message to prevent email enumeration
        if (!$user || $user->block) {
            return $this->jsonSuccess(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REMIND_SUCCESS'));
        }

        // Send reminder email
        if (!$this->sendRemindEmail($user)) {
            return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_ERROR_EMAIL'));
        }

        return $this->jsonSuccess(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_REMIND_SUCCESS'));
    }

    /**
     * Send password reset email
     *
     * @param   object  $user   User object
     * @param   string  $token  Reset token
     *
     * @return boolean
     */
    protected function sendResetEmail(object $user, string $token): bool
    {
        $config = Factory::getApplication()->getConfig();
        $sitename = $config->get('sitename');
        $mailfrom = $config->get('mailfrom');
        $fromname = $config->get('fromname');

        // Build reset link
        $link = \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_users&view=reset&layout=confirm&token=' . $token;

        // Email subject and body
        $subject = Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_RESET_EMAIL_SUBJECT', $sitename);
        $body = Text::sprintf(
            'PLG_AJAX_JOOMLAAJAXFORMS_RESET_EMAIL_BODY',
            $user->name,
            $sitename,
            $link,
            $token
        );

        try {
            $mailer = Factory::getMailer();
            $mailer->setSender([$mailfrom, $fromname]);
            $mailer->addRecipient($user->email);
            $mailer->setSubject($subject);
            $mailer->setBody($body);

            return $mailer->Send();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Send username reminder email
     *
     * @param   object  $user  User object
     *
     * @return boolean
     */
    protected function sendRemindEmail(object $user): bool
    {
        $config = Factory::getApplication()->getConfig();
        $sitename = $config->get('sitename');
        $mailfrom = $config->get('mailfrom');
        $fromname = $config->get('fromname');

        // Build login link
        $link = \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_users&view=login';

        // Email subject and body
        $subject = Text::sprintf('PLG_AJAX_JOOMLAAJAXFORMS_REMIND_EMAIL_SUBJECT', $sitename);
        $body = Text::sprintf(
            'PLG_AJAX_JOOMLAAJAXFORMS_REMIND_EMAIL_BODY',
            $user->name,
            $sitename,
            $user->username,
            $link
        );

        try {
            $mailer = Factory::getMailer();
            $mailer->setSender([$mailfrom, $fromname]);
            $mailer->addRecipient($user->email);
            $mailer->setSubject($subject);
            $mailer->setBody($body);

            return $mailer->Send();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Return JSON success response
     *
     * @param   string  $message  Success message
     * @param   mixed   $data     Optional data
     *
     * @return string
     */
    protected function jsonSuccess(string $message, $data = null): string
    {
        return json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'error' => null
        ]);
    }

    /**
     * Return JSON error response (J2Commerce compatible format)
     *
     * @param   string  $message  Error message
     *
     * @return string
     */
    protected function jsonError(string $message): string
    {
        return json_encode([
            'success' => false,
            'message' => null,
            'data' => null,
            'error' => [
                'warning' => $message
            ]
        ]);
    }
}
