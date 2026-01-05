<?php
/**
 * J2Commerce AcyMailing Plugin
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
use Advans\Plugin\J2Commerce\AcyMailing\Extension\AcyMailing;

return new class implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new AcyMailing(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('j2store', 'acymailing')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
