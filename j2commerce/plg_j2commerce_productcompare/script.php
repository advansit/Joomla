<?php
/**
 * @package     J2commerce Productcompare
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class Plgj2commerceproductcompareInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '4.0';
    protected $minimumPhp = '7.4';

    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update') {
            $app = Factory::getApplication();
            
            $app->enqueueMessage(
                '<h3>J2commerce Productcompare installed successfully!</h3>' .
                '<p>Please enable and configure the plugin via <strong>System → Plugins</strong></p>' .
                '<p>Search for: <strong>J2commerce Productcompare</strong></p>' .
                '<p>Documentation: <a href="https://advans.ch" target="_blank">advans.ch</a></p>',
                'message'
            );
        }
    }
}
