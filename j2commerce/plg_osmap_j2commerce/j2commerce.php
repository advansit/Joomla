<?php
/**
 * OSMap Plugin for J2Commerce
 *
 * Adds all enabled J2Commerce products to the OSMap sitemap.
 * Products are discovered via their published=-2 menu items which J2Commerce
 * uses for SEF URL generation.
 *
 * @package     Advans\Plugin\OSMap\J2Commerce
 * @author      Advans IT Solutions GmbH
 * @copyright   Copyright (C) 2026 Advans IT Solutions GmbH. All rights reserved.
 * @license     GNU GPL v3
 */

use Alledia\OSMap\Plugin\Base;
use Alledia\OSMap\Sitemap\Collector;
use Alledia\OSMap\Sitemap\Item;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class PlgOsmapJ2commerce extends Base
{
    public function getComponentElement(): string
    {
        return 'com_j2store';
    }

    /**
     * Called by OSMap for each menu item that belongs to com_j2store (J2Commerce).
     * Emits one node per enabled product.
     *
     * Uses the product's own published=-2 menu item as Itemid — identical to
     * how J2Commerce's router generates SEF URLs (/de/shop/product-alias).
     */
    public function getTree(Collector $collector, Item $parent, Registry $params): void
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('m.id'),
                $db->quoteName('m.alias'),
                $db->quoteName('m.browserNav'),
                $db->quoteName('a.modified'),
                $db->quoteName('a.title'),
                $db->quoteName('a.id', 'article_id'),
            ])
            ->from($db->quoteName('#__menu', 'm'))
            ->join('INNER', $db->quoteName('#__content', 'a')
                . ' ON m.link LIKE CONCAT(' . $db->quote('%id=') . ', a.id)'
                . ' AND m.link LIKE ' . $db->quote('%com_content%view=article%'))
            ->join('INNER', $db->quoteName('#__j2store_products', 'p')
                . ' ON p.product_source_id = a.id'
                . ' AND p.product_source = ' . $db->quote('com_content')
                . ' AND p.enabled = 1')
            ->where([
                'm.published = -2',
                'm.parent_id = ' . (int) $parent->id,
                'm.client_id = 0',
            ])
            ->order('a.title ASC');

        $products = $db->setQuery($query)->loadObjectList();

        foreach ($products as $product) {
            $node = (object) [
                'id'         => $product->id,
                'name'       => $product->title,
                'uid'        => 'j2commerce.product.' . $product->id,
                'modified'   => $product->modified,
                'browserNav' => $parent->browserNav,
                'priority'   => $params->get('priority', '0.8'),
                'changefreq' => $params->get('changefreq', 'weekly'),
                'link'       => 'index.php?option=com_content&view=article&id=' . $product->article_id . '&Itemid=' . $product->id,
                'expandible' => false,
            ];

            $collector->printNode($node);
        }
    }
}
