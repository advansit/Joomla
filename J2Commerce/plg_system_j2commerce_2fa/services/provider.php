<?php
/**
 * @package     System J2Commerce 2FA Plugin
 * @subpackage  Services
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Advans\Plugin\System\J2Commerce2fa\Extension\J2Commerce2fa;

/**
 * Service provider for J2Commerce 2FA plugin
 *
 * @since  1.0.0
 */
return new class implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new J2Commerce2fa(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'j2commerce_2fa')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
