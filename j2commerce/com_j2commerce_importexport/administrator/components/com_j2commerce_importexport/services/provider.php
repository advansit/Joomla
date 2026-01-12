<?php
/**
 * @package     J2Commerce Import/Export Component
 * @subpackage  Services
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Advans\Component\J2CommerceImportExport\Administrator\Extension\J2CommerceImportExportComponent;

/**
 * Service provider for J2Commerce Import/Export component
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
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Advans\\Component\\J2CommerceImportExport'));
        $container->registerServiceProvider(new MVCFactory('\\Advans\\Component\\J2CommerceImportExport'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new J2CommerceImportExportComponent($container->get(ComponentDispatcherFactoryInterface::class));

                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRegistry($container->get(Registry::class));

                return $component;
            }
        );
    }
};
