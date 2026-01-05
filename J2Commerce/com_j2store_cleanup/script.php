<?php
/**
 * @package     J2Store Cleanup
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class Com_J2store_CleanupInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '4.0';
    protected $minimumPhp = '7.4';

    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update') {
            $app = Factory::getApplication();
            
            $app->enqueueMessage(
                '<h3>J2Store Extension Cleanup installed successfully!</h3>' .
                '<p>You can now access the component via <strong>Components → J2Store Extension Cleanup</strong></p>' .
                '<p>This tool helps you identify and remove incompatible J2Store extensions.</p>' .
                '<p>For more information, visit: <a href="https://advans.ch" target="_blank">advans.ch</a></p>',
                'message'
            );
        }
    }
}
