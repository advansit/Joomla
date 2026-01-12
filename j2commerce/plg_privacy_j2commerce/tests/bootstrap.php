<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Define Joomla constants
define('_JEXEC', 1);
define('JPATH_BASE', getenv('JOOMLA_PATH') ?: '/var/www/html');

// Load Joomla framework
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Boot the DI container
$container = \Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.web.site')
    ->alias(\Joomla\Session\SessionInterface::class, 'session');
