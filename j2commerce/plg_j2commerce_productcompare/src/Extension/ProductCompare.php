<?php
/**
 * J2Commerce Product Compare Plugin
 * @subpackage  Extension
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\J2Commerce\ProductCompare\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Uri\Uri;

class ProductCompare extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function onAfterDispatch()
    {
        $app = Factory::getApplication();
        
        if ($app->isClient('administrator')) {
            return;
        }

        // Load media files
        $doc = $app->getDocument();
        $doc->addScript('media/plg_j2store_productcompare/js/productcompare.js');
        $doc->addStyleSheet('media/plg_j2store_productcompare/css/productcompare.css');
        
        // Add compare bar HTML
        $this->addCompareBar();
    }

    public function onJ2StoreAfterDisplayProduct($product, $view)
    {
        if (!$this->params->get('show_in_detail', 1)) {
            return '';
        }

        return $this->getCompareButton($product->j2store_product_id);
    }

    public function onJ2StoreAfterDisplayProductList($product)
    {
        if (!$this->params->get('show_in_list', 1)) {
            return '';
        }

        return $this->getCompareButton($product->j2store_product_id);
    }

    protected function getCompareButton($productId)
    {
        $buttonText = Text::_($this->params->get('button_text', 'PLG_J2STORE_PRODUCTCOMPARE_DEFAULT_BUTTON_TEXT'));
        $buttonClass = $this->params->get('button_class', 'btn btn-secondary');

        return sprintf(
            '<button type="button" class="j2store-compare-btn %s" data-product-id="%d">%s</button>',
            $buttonClass,
            $productId,
            $buttonText
        );
    }

    protected function addCompareBar()
    {
        $app = Factory::getApplication();
        $maxProducts = $this->params->get('max_products', 4);
        $ajaxUrl = Uri::base() . 'index.php?option=com_ajax&plugin=productcompare&group=j2store&format=json';

        $html = '
        <div id="j2store-compare-bar" class="j2store-compare-bar" style="display:none;">
            <div class="compare-bar-content">
                <div class="compare-bar-title">' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_COMPARE_PRODUCTS') . '</div>
                <div class="compare-bar-products" id="compare-bar-products"></div>
                <div class="compare-bar-actions">
                    <button type="button" class="btn btn-primary" id="compare-bar-view">
                        ' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_VIEW_COMPARISON') . '
                    </button>
                    <button type="button" class="btn btn-secondary" id="compare-bar-clear">
                        ' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_CLEAR_ALL') . '
                    </button>
                </div>
            </div>
        </div>
        <div id="j2store-compare-modal" class="j2store-compare-modal" style="display:none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_COMPARISON_TITLE') . '</h3>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="modal-body" id="compare-modal-body">
                    <div class="loading">' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_LOADING') . '</div>
                </div>
            </div>
        </div>
        <script>
        window.J2CommerceCompare = window.J2CommerceCompare || {};
        window.J2CommerceCompare.maxProducts = ' . $maxProducts . ';
        window.J2CommerceCompare.ajaxUrl = ' . json_encode($ajaxUrl) . ';
        </script>
        ';

        $app->setBody(str_replace('</body>', $html . '</body>', $app->getBody()));
    }

    /**
     * AJAX endpoint to get product comparison data
     *
     * @return  void
     * @since   1.0.0
     */
    public function onAjaxProductcompare()
    {
        try {
            $app = Factory::getApplication();
            $input = $app->input;
            
            // Get product IDs from request
            $productIds = $input->get('products', [], 'array');
            $productIds = array_map('intval', $productIds);
            
            if (empty($productIds)) {
                throw new \Exception(Text::_('PLG_J2STORE_PRODUCTCOMPARE_ERROR_NO_PRODUCTS'));
            }
            
            if (count($productIds) < 2) {
                throw new \Exception(Text::_('PLG_J2STORE_PRODUCTCOMPARE_ERROR_MIN_PRODUCTS'));
            }
            
            // Get product data
            $products = $this->getProductsData($productIds);
            
            // Generate comparison HTML
            $html = $this->generateComparisonTable($products);
            
            echo new JsonResponse(['html' => $html], '', false);
            $app->close();
            
        } catch (\Exception $e) {
            echo new JsonResponse($e, '', true);
            $app->close();
        }
    }

    /**
     * Get product data from database
     *
     * @param   array  $productIds  Array of product IDs
     * @return  array  Array of product objects
     * @since   1.0.0
     */
    protected function getProductsData(array $productIds): array
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'p.j2store_product_id',
                'p.product_source_id',
                'v.j2store_variant_id',
                'v.sku',
                'v.price',
                'v.stock',
                'v.availability',
                'c.title',
                'c.introtext',
                'c.fulltext',
            ])
            ->from($db->quoteName('#__j2store_products', 'p'))
            ->join('LEFT', $db->quoteName('#__j2store_variants', 'v') . ' ON p.j2store_product_id = v.product_id')
            ->join('LEFT', $db->quoteName('#__content', 'c') . ' ON p.product_source_id = c.id')
            ->where($db->quoteName('p.j2store_product_id') . ' IN (' . implode(',', $productIds) . ')')
            ->where($db->quoteName('p.enabled') . ' = 1')
            ->order($db->quoteName('p.j2store_product_id'));
        
        $db->setQuery($query);
        $products = $db->loadObjectList();
        
        // Get product options/attributes
        foreach ($products as &$product) {
            $product->options = $this->getProductOptions($product->j2store_product_id);
        }
        
        return $products;
    }

    /**
     * Get product options/attributes
     *
     * @param   int  $productId  Product ID
     * @return  array  Array of options
     * @since   1.0.0
     */
    protected function getProductOptions(int $productId): array
    {
        $db = $this->getDatabase();
        
        $query = $db->quoteName('SELECT o.option_name, o.option_value')
            ->from($db->quoteName('#__j2store_product_options', 'o'))
            ->where($db->quoteName('o.product_id') . ' = :productid')
            ->bind(':productid', $productId, \Joomla\Database\ParameterType::INTEGER);
        
        $db->setQuery($query);
        
        try {
            return $db->loadAssocList() ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate comparison table HTML
     *
     * @param   array  $products  Array of product objects
     * @return  string  HTML table
     * @since   1.0.0
     */
    protected function generateComparisonTable(array $products): string
    {
        if (empty($products)) {
            return '<p>' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_NO_PRODUCTS') . '</p>';
        }
        
        $html = '<table class="j2store-comparison-table">';
        
        // Header row with product names
        $html .= '<thead><tr><th>' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_ATTRIBUTE') . '</th>';
        foreach ($products as $product) {
            $html .= '<th>' . htmlspecialchars($product->title ?? '') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        // SKU row
        $html .= '<tr><td>' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_SKU') . '</td>';
        foreach ($products as $product) {
            $html .= '<td>' . htmlspecialchars($product->sku ?? '-') . '</td>';
        }
        $html .= '</tr>';
        
        // Price row
        $html .= '<tr><td>' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_PRICE') . '</td>';
        foreach ($products as $product) {
            $price = $product->price ?? 0;
            $html .= '<td>' . HTMLHelper::_('currency.format', $price) . '</td>';
        }
        $html .= '</tr>';
        
        // Stock row
        $html .= '<tr><td>' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_STOCK') . '</td>';
        foreach ($products as $product) {
            $stock = $product->stock ?? 0;
            $availability = $product->availability ?? '';
            $html .= '<td>' . ($stock > 0 ? Text::_('PLG_J2STORE_PRODUCTCOMPARE_IN_STOCK') : Text::_('PLG_J2STORE_PRODUCTCOMPARE_OUT_OF_STOCK')) . '</td>';
        }
        $html .= '</tr>';
        
        // Description row
        $html .= '<tr><td>' . Text::_('PLG_J2STORE_PRODUCTCOMPARE_DESCRIPTION') . '</td>';
        foreach ($products as $product) {
            $desc = strip_tags($product->introtext ?? '');
            $html .= '<td>' . htmlspecialchars(substr($desc, 0, 200)) . '...</td>';
        }
        $html .= '</tr>';
        
        $html .= '</tbody></table>';
        
        return $html;
    }
}
