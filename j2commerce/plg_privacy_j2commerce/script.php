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

class Plgprivacyj2commerceInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '4.0';
    protected $minimumPhp = '7.4';

    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update') {
            $app = Factory::getApplication();
            $lang = $app->getLanguage();
            $lang->load('plg_privacy_j2commerce', JPATH_ADMINISTRATOR);
            $lang->load('plg_privacy_j2commerce', $parent->getParent()->getPath('source'));

            $css = <<<CSS
<style>
.pj2c-post { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 860px; }
.pj2c-post h2 { margin-bottom: 16px; }
.pj2c-post h3 { margin-top: 0; }
.pj2c-post code { background: #e5e7eb; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
.pj2c-post table { width: 100%; border-collapse: collapse; margin: 12px 0; }
.pj2c-post td { padding: 8px 10px; border: 1px solid #d1d5db; vertical-align: top; }
.pj2c-post td:first-child { font-weight: 600; width: 35%; background: #f9fafb; }
.pj2c-post .pj2c-box { padding: 16px 20px; margin: 16px 0; border-radius: 4px; border-left: 4px solid; }
.pj2c-post .pj2c-warn { background: #fef3c7; border-color: #d97706; }
.pj2c-post .pj2c-info { background: #eff6ff; border-color: #2563eb; }
.pj2c-post .pj2c-advans { background: #f5f3ff; border-color: #7c3aed; }
.pj2c-post .pj2c-step { color: #374151; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.pj2c-post ol, .pj2c-post ul { line-height: 1.8; }
</style>
CSS;

            $message = $css;
            $message .= '<div class="pj2c-post">';
            $message .= '<h2>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TITLE') . '</h2>';

            // Step 1
            $message .= '<div class="pj2c-box pj2c-info">';
            $message .= '<div class="pj2c-step">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP1_LABEL') . '</div>';
            $message .= '<h3>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP1_TITLE') . '</h3>';
            $message .= '<p>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP1_DESC') . '</p>';
            $message .= '</div>';

            // Step 2
            $message .= '<div class="pj2c-box pj2c-info">';
            $message .= '<div class="pj2c-step">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP2_LABEL') . '</div>';
            $message .= '<h3>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP2_TITLE') . '</h3>';
            $message .= '<p><code>System &rarr; Plugins &rarr; Privacy - J2Commerce</code></p>';
            $message .= '<table>';
            $message .= '<tr><td>' . Text::_('PLG_PRIVACY_J2COMMERCE_RETENTION_YEARS_LABEL') . '</td><td>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_RETENTION_HINT') . '</td></tr>';
            $message .= '<tr><td>' . Text::_('PLG_PRIVACY_J2COMMERCE_LEGAL_BASIS_LABEL') . '</td><td>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_LEGAL_BASIS_HINT') . '</td></tr>';
            $message .= '<tr><td>' . Text::_('PLG_PRIVACY_J2COMMERCE_SUPPORT_EMAIL_LABEL') . '</td><td>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_SUPPORT_EMAIL_HINT') . '</td></tr>';
            $message .= '<tr><td>' . Text::_('PLG_PRIVACY_J2COMMERCE_SHOW_CONSENT_LABEL') . '</td><td>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CONSENT_HINT') . '</td></tr>';
            $message .= '</table>';
            $message .= '</div>';

            // Step 3 — Advans IT Solutions GmbH specific
            $message .= '<div class="pj2c-box pj2c-advans">';
            $message .= '<div class="pj2c-step">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP3_LABEL') . '</div>';
            $message .= '<h3>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP3_TITLE') . '</h3>';
            $message .= '<p>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP3_DESC') . '</p>';

            $message .= '<p><strong>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_CREATE') . ':</strong> ';
            $message .= '<code>Components &rarr; J2Store &rarr; Setup &rarr; Custom Fields &rarr; New</code></p>';
            $message .= '<table>';
            $message .= '<tr><td>Field Name</td><td><code>is_lifetime_license</code><br><small>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_NAME_HINT') . '</small></td></tr>';
            $message .= '<tr><td>Field Label</td><td>Lifetime License</td></tr>';
            $message .= '<tr><td>Field Type</td><td>Radio</td></tr>';
            $message .= '<tr><td>Field Options</td><td><code>Yes</code> / <code>No</code><br><small>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_OPTIONS_HINT') . '</small></td></tr>';
            $message .= '<tr><td>Default Value</td><td>No</td></tr>';
            $message .= '<tr><td>Display in</td><td>Product</td></tr>';
            $message .= '<tr><td>Required</td><td>No</td></tr>';
            $message .= '<tr><td>Published</td><td>Yes</td></tr>';
            $message .= '</table>';

            $message .= '<p><strong>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_ASSIGN') . ':</strong> ';
            $message .= Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CUSTOMFIELD_ASSIGN_DESC') . '</p>';
            $message .= '</div>';

            // Step 4
            $message .= '<div class="pj2c-box pj2c-info">';
            $message .= '<div class="pj2c-step">' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP4_LABEL') . '</div>';
            $message .= '<h3>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_STEP4_TITLE') . '</h3>';
            $message .= '<p><code>System &rarr; Scheduled Tasks &rarr; New</code></p>';
            $message .= '<ol>';
            $message .= '<li>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TASK_TYPE') . '</li>';
            $message .= '<li>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TASK_SCHEDULE') . '</li>';
            $message .= '<li>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TASK_ENABLE') . '</li>';
            $message .= '</ol>';
            $message .= '<p>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_TASK_DESC') . '</p>';
            $message .= '</div>';

            // Checklist
            $message .= '<div class="pj2c-box pj2c-warn">';
            $message .= '<h3>' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECKLIST_TITLE') . '</h3>';
            $message .= '<ul style="list-style: none; padding-left: 0;">';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_ENABLED') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_EMAIL') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_RETENTION') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_CONSENT') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_TASK') . '</li>';
            $message .= '<li>[ ] ' . Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_CHECK_TEST') . '</li>';
            $message .= '</ul>';
            $message .= '</div>';

            // Support
            $message .= '<p style="margin-top: 20px; color: #6b7280; font-size: 13px;">';
            $message .= Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_DOCS') . ' &middot; ';
            $message .= Text::_('PLG_PRIVACY_J2COMMERCE_POSTINSTALL_SUPPORT');
            $message .= '</p>';

            $message .= '</div>';

            $app->enqueueMessage($message, 'message');
        }
    }
}
