<?php
/**
 * @package     J2Commerce Import/Export
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class Com_J2commerce_ImportexportInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '4.0';
    protected $minimumPhp = '7.4';

    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update') {
            $app = Factory::getApplication();
            
            $app->enqueueMessage(
                '<h3>J2Commerce Import/Export installed successfully!</h3>' .
                '<p>Access via <strong>Components → J2Commerce Import/Export</strong></p>' .
                '<p><strong>Configuration:</strong> Set batch size, default format, and CSV settings in Options.</p>' .
                '<p><strong>Features:</strong> Import/Export products, categories, variants, and prices in CSV, XML, or JSON format.</p>' .
                '<p>Documentation: <a href="https://advans.ch" target="_blank">advans.ch</a></p>',
                'message'
            );
        }
    }
}
