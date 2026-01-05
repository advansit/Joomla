<?php
/**
 * @package     J2Commerce Import/Export Component
 * @subpackage  Administrator
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Component\J2CommerceImportExport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

class ExportModel extends BaseDatabaseModel
{
    public function exportData($type)
    {
        switch ($type) {
            case 'products':
                return $this->exportProducts();
            case 'categories':
                return $this->exportCategories();
            case 'variants':
                return $this->exportVariants();
            case 'prices':
                return $this->exportPrices();
            default:
                throw new \Exception('Invalid export type');
        }
    }

    protected function exportProducts()
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'p.j2store_product_id',
                'p.product_source',
                'p.product_source_id',
                'p.product_type',
                'p.visibility',
                'p.enabled',
                'p.taxprofile_id',
                'p.vendor_id',
                'p.manufacturer_id',
                'p.created_on',
                'p.modified_on'
            ])
            ->from($db->quoteName('#__j2store_products', 'p'))
            ->order('p.j2store_product_id ASC');

        $db->setQuery($query);
        return $db->loadAssocList();
    }

    protected function exportCategories()
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
            ->order('id ASC');

        $db->setQuery($query);
        return $db->loadAssocList();
    }

    protected function exportVariants()
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__j2store_variants'))
            ->order('j2store_variant_id ASC');

        $db->setQuery($query);
        return $db->loadAssocList();
    }

    protected function exportPrices()
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__j2store_productprices'))
            ->order('j2store_productprice_id ASC');

        $db->setQuery($query);
        return $db->loadAssocList();
    }
}
