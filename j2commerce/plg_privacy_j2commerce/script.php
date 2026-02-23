<?php
/**
 * @package     J2Commerce Privacy System Plugin
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class Plgprivacyj2commerceInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '4.0';
    protected $minimumPhp = '7.4';

    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update') {
            $this->ensureUpdateSite();

            $app = Factory::getApplication();
            $lang = $app->getLanguage();
            $lang->load('plg_privacy_j2commerce', JPATH_ADMINISTRATOR);
            $lang->load('plg_privacy_j2commerce', $parent->getParent()->getPath('source'));

            // Inline styles — Joomla 5 <joomla-alert> strips <style> tags
            $sBox = 'padding:16px 20px;margin:16px 0;border-radius:4px;border-left:4px solid';
            $sInfo = $sBox . ';background:#eff6ff;border-color:#2563eb';
            $sAdvans = $sBox . ';background:#f5f3ff;border-color:#7c3aed';
            $sWarn = $sBox . ';background:#fef3c7;border-color:#d97706';
            $sStep = 'color:#374151;font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px';
            $sTbl = 'width:100%;border-collapse:collapse;margin:12px 0';
            $sTd = 'padding:8px 10px;border:1px solid #d1d5db;vertical-align:top';
            $sTdL = $sTd . ';font-weight:600;width:35%;background:#f9fafb';
            $sCode = 'background:#e5e7eb;padding:2px 6px;border-radius:3px;font-size:13px';

            $message = '';
            $message .= '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:860px">';
            $message .= '<h2 style="margin-bottom:16px">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TITLE') . '</h2>';

            // Step 1
            $message .= '<div style="' . $sInfo . '">';
            $message .= '<div style="' . $sStep . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP1_LABEL') . '</div>';
            $message .= '<h3 style="margin-top:0">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP1_TITLE') . '</h3>';
            $message .= '<p>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP1_DESC') . '</p>';
            $message .= '</div>';

            // Step 2
            $message .= '<div style="' . $sInfo . '">';
            $message .= '<div style="' . $sStep . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP2_LABEL') . '</div>';
            $message .= '<h3 style="margin-top:0">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP2_TITLE') . '</h3>';
            $message .= '<p><span style="' . $sCode . '">System &rarr; Plugins &rarr; Privacy - J2Commerce</span></p>';
            $message .= '<table style="' . $sTbl . '">';
            $message .= '<tr><td style="' . $sTdL . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_RETENTION_YEARS_LABEL') . '</td><td style="' . $sTd . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_RETENTION_HINT') . '</td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_LEGAL_BASIS_LABEL') . '</td><td style="' . $sTd . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_LEGAL_BASIS_HINT') . '</td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_SUPPORT_EMAIL_LABEL') . '</td><td style="' . $sTd . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_SUPPORT_EMAIL_HINT') . '</td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_SHOW_CONSENT_LABEL') . '</td><td style="' . $sTd . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CONSENT_HINT') . '</td></tr>';
            $message .= '</table>';
            $message .= '</div>';

            // Step 3 — Advans IT Solutions GmbH specific
            $message .= '<div style="' . $sAdvans . '">';
            $message .= '<div style="' . $sStep . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP3_LABEL') . '</div>';
            $message .= '<h3 style="margin-top:0">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP3_TITLE') . '</h3>';
            $message .= '<p>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP3_DESC') . '</p>';

            $message .= '<p><strong>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_CREATE') . ':</strong> ';
            $message .= '<span style="' . $sCode . '">Components &rarr; J2Store &rarr; Setup &rarr; Custom Fields &rarr; New</span></p>';
            $message .= '<table style="' . $sTbl . '">';
            $message .= '<tr><td style="' . $sTdL . '">Field Name</td><td style="' . $sTd . '"><span style="' . $sCode . '">is_lifetime_license</span><br><small>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_NAME_HINT') . '</small></td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">Field Label</td><td style="' . $sTd . '">Lifetime License</td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">Field Type</td><td style="' . $sTd . '">Radio</td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">Field Options</td><td style="' . $sTd . '"><span style="' . $sCode . '">Yes</span> / <span style="' . $sCode . '">No</span><br><small>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_OPTIONS_HINT') . '</small></td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">Default Value</td><td style="' . $sTd . '">No</td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">Display in</td><td style="' . $sTd . '">Product</td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">Required</td><td style="' . $sTd . '">No</td></tr>';
            $message .= '<tr><td style="' . $sTdL . '">Published</td><td style="' . $sTd . '">Yes</td></tr>';
            $message .= '</table>';

            $message .= '<p><strong>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_ASSIGN') . ':</strong> ';
            $message .= Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_ASSIGN_DESC') . '</p>';
            $message .= '</div>';

            // Step 4
            $message .= '<div style="' . $sInfo . '">';
            $message .= '<div style="' . $sStep . '">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP4_LABEL') . '</div>';
            $message .= '<h3 style="margin-top:0">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP4_TITLE') . '</h3>';
            $message .= '<p><span style="' . $sCode . '">System &rarr; Scheduled Tasks &rarr; New</span></p>';
            $message .= '<ol style="line-height:1.8">';
            $message .= '<li>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TASK_TYPE') . '</li>';
            $message .= '<li>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TASK_SCHEDULE') . '</li>';
            $message .= '<li>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TASK_ENABLE') . '</li>';
            $message .= '</ol>';
            $message .= '<p>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TASK_DESC') . '</p>';
            $message .= '</div>';

            // Checklist
            $message .= '<div style="' . $sWarn . '">';
            $message .= '<h3 style="margin-top:0">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECKLIST_TITLE') . '</h3>';
            $message .= '<ul style="list-style:none;padding-left:0;line-height:1.8">';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_ENABLED') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_EMAIL') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_RETENTION') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_CONSENT') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_TASK') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_TEST') . '</li>';
            $message .= '</ul>';
            $message .= '</div>';

            // Support
            $message .= '<p style="margin-top:20px;color:#6b7280;font-size:13px">';
            $message .= Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_DOCS') . ' &middot; ';
            $message .= Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_SUPPORT');
            $message .= '</p>';

            $message .= '</div>';

            $app->enqueueMessage($message, 'message');
        }
    }

    /**
     * Register the update site if not already present.
     * Handles the case where the plugin was initially installed without
     * the <updateservers> block in the XML manifest.
     */
    private function ensureUpdateSite(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $updateUrl = 'https://raw.githubusercontent.com/advansit/Joomla/main/j2commerce/plg_privacy_j2commerce/updates/update.xml';

        // Find the extension ID
        $element = 'j2commerce';
        $folder = 'privacy';
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = :element')
            ->where($db->quoteName('folder') . ' = :folder')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->bind(':element', $element)
            ->bind(':folder', $folder);
        $db->setQuery($query);
        $extensionId = (int) $db->loadResult();

        if (!$extensionId) {
            return;
        }

        // Check if update site already exists for this URL
        $query = $db->getQuery(true)
            ->select($db->quoteName('update_site_id'))
            ->from($db->quoteName('#__update_sites'))
            ->where($db->quoteName('location') . ' = :url')
            ->bind(':url', $updateUrl);
        $db->setQuery($query);
        $siteId = (int) $db->loadResult();

        if ($siteId) {
            // Ensure the mapping exists
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__update_sites_extensions'))
                ->where($db->quoteName('update_site_id') . ' = :siteId')
                ->where($db->quoteName('extension_id') . ' = :extId')
                ->bind(':siteId', $siteId, ParameterType::INTEGER)
                ->bind(':extId', $extensionId, ParameterType::INTEGER);
            $db->setQuery($query);

            if (!(int) $db->loadResult()) {
                $db->getQuery(true);
                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__update_sites_extensions'))
                    ->columns([$db->quoteName('update_site_id'), $db->quoteName('extension_id')])
                    ->values(':siteId, :extId')
                    ->bind(':siteId', $siteId, ParameterType::INTEGER)
                    ->bind(':extId', $extensionId, ParameterType::INTEGER);
                $db->setQuery($query);
                $db->execute();
            }
            return;
        }

        // Create update site
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__update_sites'))
            ->columns([
                $db->quoteName('name'),
                $db->quoteName('type'),
                $db->quoteName('location'),
                $db->quoteName('enabled'),
            ])
            ->values(':name, :type, :url, 1')
            ->bind(':name', $name = 'J2Commerce Privacy Plugin')
            ->bind(':type', $type = 'extension')
            ->bind(':url', $updateUrl);
        $db->setQuery($query);
        $db->execute();
        $siteId = (int) $db->insertid();

        // Map update site to extension
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__update_sites_extensions'))
            ->columns([$db->quoteName('update_site_id'), $db->quoteName('extension_id')])
            ->values(':siteId, :extId')
            ->bind(':siteId', $siteId, ParameterType::INTEGER)
            ->bind(':extId', $extensionId, ParameterType::INTEGER);
        $db->setQuery($query);
        $db->execute();
    }
}
