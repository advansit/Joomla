<?php
/**
 * @package     System J2commerce 2fa
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class Plgsystemj2commerce2faInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '4.0';
    protected $minimumPhp = '7.4';

    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update') {
            $app = Factory::getApplication();
            
            $app->enqueueMessage(
                '<h3>System J2commerce 2fa installed successfully!</h3>' .
                '<p>Please enable and configure the plugin via <strong>System → Plugins</strong></p>' .
                '<p>Search for: <strong>System J2commerce 2fa</strong></p>' .
                '<p>Documentation: <a href="https://advans.ch" target="_blank">advans.ch</a></p>',
                'message'
            );
        }
    }
}
