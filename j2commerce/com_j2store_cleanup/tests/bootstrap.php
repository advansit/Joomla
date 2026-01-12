<?php
/**
 * @package     J2Store Cleanup Tests
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 */

defined('_JEXEC') or define('_JEXEC', 1);

// Define Joomla path constants
define('JPATH_BASE', dirname(__DIR__, 4) . '/live');
define('JPATH_ADMINISTRATOR', JPATH_BASE . '/administrator');

// Bootstrap basic test environment
if (file_exists(JPATH_BASE . '/includes/defines.php')) {
    require_once JPATH_BASE . '/includes/defines.php';
}
