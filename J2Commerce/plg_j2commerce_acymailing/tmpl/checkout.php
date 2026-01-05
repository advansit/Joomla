<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$checkboxLabel = Text::_($params->get('checkbox_label', 'PLG_J2COMMERCE_ACYMAILING_DEFAULT_CHECKBOX_LABEL'));
$checkboxDefault = $params->get('checkbox_default', 0) ? 'checked' : '';
?>

<div class="acymailing-subscription-wrapper">
    <div class="form-group">
        <div class="checkbox">
            <label>
                <input 
                    type="checkbox" 
                    name="acymailing_subscribe" 
                    id="acymailing_subscribe" 
                    value="1" 
                    <?php echo $checkboxDefault; ?>
                >
                <?php echo $checkboxLabel; ?>
            </label>
        </div>
    </div>
</div>
