<?php
/**
 * PHPUnit bootstrap file
 *
 * @package     Advans.Plugin
 * @subpackage  System.J2Commerce2faFix
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

defined('_JEXEC') or define('_JEXEC', 1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define Joomla constants for testing
define('JPATH_BASE', __DIR__ . '/fixtures/joomla');
define('JPATH_ROOT', JPATH_BASE);
define('JPATH_SITE', JPATH_BASE);
define('JPATH_ADMINISTRATOR', JPATH_BASE . '/administrator');
define('JPATH_PLUGINS', JPATH_BASE . '/plugins');

// Mock Joomla classes if needed
if (!class_exists('JPlugin')) {
    class JPlugin {}
}

if (!class_exists('CMSPlugin')) {
    class CMSPlugin extends JPlugin {}
}
