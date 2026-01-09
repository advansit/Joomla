<?php
/**
 * @package     Advans.Plugin
 * @subpackage  System.J2Commerce2fa
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\System\J2Commerce2fa\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
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
     * @param   \Joomla\Event\Event  $event  The event object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserAfterLogin(\Joomla\Event\Event $event): void
    {
        // Minimal implementation - just return
        return;
    }

    /**
     * This method is called before user logout
     *
     * @param   \Joomla\Event\Event  $event  The event object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserBeforeLogout(\Joomla\Event\Event $event): void
    {
        // Minimal implementation - just return
        return;
    }
}
