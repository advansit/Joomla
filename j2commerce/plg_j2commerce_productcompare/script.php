<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class PlgJ2commerceProductcompareInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '4.0';
    protected $minimumPhp = '7.4';

    public function preflight($type, $parent)
    {
        if ($type === 'update') {
            // Remove the old j2store group directory left behind when the plugin
            // group changes from j2store to j2commerce. Joomla installs the new
            // files under plugins/j2commerce/productcompare/ but does not clean
            // up the old plugins/j2store/productcompare/ directory.
            $oldDir = JPATH_PLUGINS . '/j2store/productcompare';
            if (is_dir($oldDir)) {
                \Joomla\CMS\Filesystem\Folder::delete($oldDir);
            }
        }

        return parent::preflight($type, $parent);
    }

    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update') {
            $this->migrateGroupIfNeeded();
            $this->ensureUpdateSite();

            $app = Factory::getApplication();
            $lang = $app->getLanguage();
            $lang->load('plg_j2store_productcompare', JPATH_ADMINISTRATOR);
            $lang->load('plg_j2store_productcompare', $parent->getParent()->getPath('source'));

            $sBox = 'padding:16px 20px;margin:16px 0;border-radius:4px;border-left:4px solid;background:#eff6ff;border-color:#2563eb';

            $message = '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:860px">';
            $message .= '<h2 style="margin-bottom:16px">' . Text::_('PLG_J2COMMERCE_PRODUCTCOMPARE_POSTINSTALL_TITLE') . '</h2>';
            $message .= '<div style="' . $sBox . '">';
            $message .= '<p>' . Text::_('PLG_J2COMMERCE_PRODUCTCOMPARE_POSTINSTALL_ENABLE') . '</p>';
            $message .= '<p>' . Text::_('PLG_J2COMMERCE_PRODUCTCOMPARE_POSTINSTALL_SEARCH') . '</p>';
            $message .= '</div>';
            $message .= '<p style="margin-top:12px;color:#6b7280;font-size:13px">' . Text::_('PLG_J2COMMERCE_PRODUCTCOMPARE_POSTINSTALL_DOCS') . '</p>';
            $message .= '</div>';

            $app->enqueueMessage($message, 'message');
        }
    }

    /**
     * Rename the plugin's folder from j2store to j2commerce in #__extensions.
     * Needed when upgrading from a package that was registered under group="j2store".
     */
    private function migrateGroupIfNeeded(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $this->createDbQuery($db)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('folder') . ' = ' . $db->quote('j2commerce'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('productcompare'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('j2store'));
        $db->setQuery($query);
        $db->execute();
    }

    private function ensureUpdateSite(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $updateUrl = 'https://raw.githubusercontent.com/advansit/Joomla/main/j2commerce/plg_j2commerce_productcompare/updates/update.xml';
        $element = 'productcompare';
        $folder = 'j2commerce';

        $query = $this->createDbQuery($db)
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

        $query = $this->createDbQuery($db)
            ->select($db->quoteName('update_site_id'))
            ->from($db->quoteName('#__update_sites'))
            ->where($db->quoteName('location') . ' = :url')
            ->bind(':url', $updateUrl);
        $db->setQuery($query);
        $siteId = (int) $db->loadResult();

        if ($siteId) {
            $query = $this->createDbQuery($db)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__update_sites_extensions'))
                ->where($db->quoteName('update_site_id') . ' = :siteId')
                ->where($db->quoteName('extension_id') . ' = :extId')
                ->bind(':siteId', $siteId, ParameterType::INTEGER)
                ->bind(':extId', $extensionId, ParameterType::INTEGER);
            $db->setQuery($query);
            if (!(int) $db->loadResult()) {
                $query = $this->createDbQuery($db)
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

        $name = 'J2Store Product Compare';
        $type = 'extension';
        $query = $this->createDbQuery($db)
            ->insert($db->quoteName('#__update_sites'))
            ->columns([$db->quoteName('name'), $db->quoteName('type'), $db->quoteName('location'), $db->quoteName('enabled')])
            ->values(':name, :type, :url, 1')
            ->bind(':name', $name)->bind(':type', $type)->bind(':url', $updateUrl);
        $db->setQuery($query);
        $db->execute();
        $siteId = (int) $db->insertid();

        $query = $this->createDbQuery($db)
            ->insert($db->quoteName('#__update_sites_extensions'))
            ->columns([$db->quoteName('update_site_id'), $db->quoteName('extension_id')])
            ->values(':siteId, :extId')
            ->bind(':siteId', $siteId, ParameterType::INTEGER)
            ->bind(':extId', $extensionId, ParameterType::INTEGER);
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Creates a query object compatible with Joomla 5 (getQuery) and 6 (createQuery).
     */
    private function createDbQuery(\Joomla\Database\DatabaseInterface $db): \Joomla\Database\QueryInterface
    {
        return method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true);
    }
}
