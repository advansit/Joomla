<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_j2store_cart
 *
 * Template override for J2Store minicart with AJAX delete functionality
 * Copy this file to: templates/YOUR_TEMPLATE/html/mod_j2store_cart/detailcartonhover.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

// Load the AJAX script
$wa->registerAndUseScript('plg_ajax_advans', 'plg_ajax_advans/advans-ajax.js', [], ['defer' => true]);

$items = $cart->getItems();
$count = count($items);
$show_tax = $params->get('show_tax', 1);
$show_weight = $params->get('show_weight', 0);
$show_quantity = $params->get('show_quantity', 1);
$show_subtotal = $params->get('show_subtotal', 1);
$show_checkout_button = $params->get('show_checkout_button', 1);
$show_cart_button = $params->get('show_cart_button', 1);
$show_product_image = $params->get('show_product_image', 1);
$image_type = $params->get('image_type', 'thumbnail');
$image_width = $params->get('image_width', 50);
$image_height = $params->get('image_height', 50);

// Get CSRF token
$token = Session::getFormToken();
?>

<div class="j2store-minicart-wrapper">
    <?php if ($count > 0): ?>
        <div class="j2store-minicart-items">
            <?php foreach ($items as $item): ?>
                <div class="minicart-item" data-cartitem-id="<?php echo (int) $item->j2store_cartitem_id; ?>">
                    <?php if ($show_product_image && !empty($item->product_image)): ?>
                        <div class="minicart-item-image">
                            <img src="<?php echo Uri::root() . $item->product_image; ?>" 
                                 alt="<?php echo htmlspecialchars($item->product_name); ?>"
                                 width="<?php echo $image_width; ?>" 
                                 height="<?php echo $image_height; ?>" />
                        </div>
                    <?php endif; ?>
                    
                    <div class="minicart-item-details">
                        <div class="minicart-item-name">
                            <?php echo htmlspecialchars($item->product_name); ?>
                        </div>
                        
                        <?php if (!empty($item->options)): ?>
                            <div class="minicart-item-options">
                                <?php echo $item->options; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($show_quantity): ?>
                            <div class="minicart-item-qty">
                                <?php echo Text::_('J2STORE_CART_QUANTITY'); ?>: <?php echo (int) $item->quantity; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="minicart-item-price">
                            <?php echo $item->price_display; ?>
                        </div>
                    </div>
                    
                    <div class="minicart-item-actions">
                        <button type="button" 
                                class="btn btn-sm btn-danger minicart-remove-btn"
                                onclick="advansRemoveCartItem(<?php echo (int) $item->j2store_cartitem_id; ?>, this)"
                                title="<?php echo Text::_('J2STORE_CART_REMOVE_ITEM'); ?>">
                            <span class="icon-trash" aria-hidden="true"></span>
                            <span class="visually-hidden"><?php echo Text::_('J2STORE_CART_REMOVE_ITEM'); ?></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($show_subtotal): ?>
            <div class="minicart-subtotal">
                <span class="subtotal-label"><?php echo Text::_('J2STORE_CART_SUBTOTAL'); ?>:</span>
                <span class="subtotal-value"><?php echo $cart->getSubtotalDisplay(); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($show_tax): ?>
            <div class="minicart-tax">
                <span class="tax-label"><?php echo Text::_('J2STORE_CART_TAX'); ?>:</span>
                <span class="tax-value"><?php echo $cart->getTaxDisplay(); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="minicart-total">
            <span class="total-label"><?php echo Text::_('J2STORE_CART_TOTAL'); ?>:</span>
            <span class="total-value"><?php echo $cart->getTotalDisplay(); ?></span>
        </div>
        
        <div class="minicart-buttons">
            <?php if ($show_cart_button): ?>
                <a href="<?php echo Route::_('index.php?option=com_j2store&view=carts'); ?>" 
                   class="btn btn-secondary">
                    <?php echo Text::_('J2STORE_VIEW_CART'); ?>
                </a>
            <?php endif; ?>
            
            <?php if ($show_checkout_button): ?>
                <a href="<?php echo Route::_('index.php?option=com_j2store&view=checkout'); ?>" 
                   class="btn btn-primary">
                    <?php echo Text::_('J2STORE_CHECKOUT'); ?>
                </a>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="minicart-empty">
            <p><?php echo Text::_('J2STORE_CART_EMPTY'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.minicart-item {
    display: flex;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    transition: opacity 0.3s, transform 0.3s;
}

.minicart-item-image {
    flex-shrink: 0;
    margin-right: 10px;
}

.minicart-item-image img {
    border-radius: 4px;
}

.minicart-item-details {
    flex-grow: 1;
    min-width: 0;
}

.minicart-item-name {
    font-weight: 600;
    margin-bottom: 4px;
}

.minicart-item-options {
    font-size: 0.85em;
    color: #666;
}

.minicart-item-qty {
    font-size: 0.85em;
    color: #666;
}

.minicart-item-price {
    font-weight: 500;
    color: #333;
}

.minicart-item-actions {
    flex-shrink: 0;
    margin-left: 10px;
}

.minicart-remove-btn {
    padding: 4px 8px;
}

.minicart-remove-btn.loading {
    opacity: 0.5;
    pointer-events: none;
}

.minicart-subtotal,
.minicart-tax,
.minicart-total {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.minicart-total {
    font-weight: 700;
    font-size: 1.1em;
    border-top: 2px solid #333;
    margin-top: 10px;
    padding-top: 10px;
}

.minicart-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.minicart-buttons .btn {
    flex: 1;
    text-align: center;
}

.minicart-empty {
    text-align: center;
    padding: 20px;
    color: #666;
}
</style>
