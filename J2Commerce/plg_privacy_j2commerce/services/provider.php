<?php
/**
 * @package     Privacy J2Commerce Plugin
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
use Advans\Plugin\Privacy\J2Commerce\Extension\J2Commerce;
use Advans\Plugin\Privacy\J2Commerce\Task\AutoCleanupTask;

return new class implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new J2Commerce(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('privacy', 'j2commerce')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase(Factory::getContainer()->get('DatabaseDriver'));

                return $plugin;
            }
        );
        
        // Register auto-cleanup task
        $container->set(
            AutoCleanupTask::class,
            function (Container $container) {
                $task = new AutoCleanupTask(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('privacy', 'j2commerce')
                );
                $task->setApplication(Factory::getApplication());
                $task->setDatabase(Factory::getContainer()->get('DatabaseDriver'));

                return $task;
            }
        );
    }
};
