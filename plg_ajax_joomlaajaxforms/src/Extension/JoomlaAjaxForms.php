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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
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
     * @return string JSON response
     */
    public function onAjaxJoomlaajaxforms(): string
    {
        // Verify CSRF token
        if (!Session::checkToken('get') && !Session::checkToken('post')) {
            return $this->jsonError(Text::_('JINVALID_TOKEN'));
        }

        $task = $this->app->input->get('task', '', 'cmd');

        switch ($task) {
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

            default:
                return $this->jsonError(Text::_('PLG_AJAX_JOOMLAAJAXFORMS_INVALID_TASK'));
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
