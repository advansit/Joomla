<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_j2store
 *
 * Template override for J2Store MyProfile account tab with AJAX save functionality
 * Copy this file to: templates/YOUR_TEMPLATE/html/com_j2store/myprofile/default_account.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

// Load the AJAX script
$wa->registerAndUseScript('plg_ajax_advans', 'plg_ajax_advans/advans-ajax.js', [], ['defer' => true]);

$user = Factory::getApplication()->getIdentity();
$token = Session::getFormToken();
?>

<div class="j2store-myprofile-account">
    <h3><?php echo Text::_('J2STORE_MYPROFILE_ACCOUNT_DETAILS'); ?></h3>
    
    <form id="advans-profile-form" class="form-horizontal" onsubmit="event.preventDefault(); advansSaveProfile(this);">
        <input type="hidden" name="<?php echo $token; ?>" value="1" />
        
        <div class="control-group mb-3">
            <label class="control-label form-label" for="profile-name">
                <?php echo Text::_('JGLOBAL_USERNAME'); ?>
            </label>
            <div class="controls">
                <input type="text" 
                       id="profile-username" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($user->username); ?>" 
                       disabled 
                       readonly />
                <small class="form-text text-muted">
                    <?php echo Text::_('J2STORE_USERNAME_CANNOT_BE_CHANGED'); ?>
                </small>
            </div>
        </div>
        
        <div class="control-group mb-3">
            <label class="control-label form-label" for="profile-name">
                <?php echo Text::_('JGLOBAL_FULL_NAME'); ?> <span class="text-danger">*</span>
            </label>
            <div class="controls">
                <input type="text" 
                       id="profile-name" 
                       name="name" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($user->name); ?>" 
                       required />
            </div>
        </div>
        
        <div class="control-group mb-3">
            <label class="control-label form-label" for="profile-email">
                <?php echo Text::_('JGLOBAL_EMAIL'); ?> <span class="text-danger">*</span>
            </label>
            <div class="controls">
                <input type="email" 
                       id="profile-email" 
                       name="email" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($user->email); ?>" 
                       required />
            </div>
        </div>
        
        <hr class="my-4" />
        
        <h4><?php echo Text::_('COM_USERS_PROFILE_PASSWORD_CHANGE'); ?></h4>
        <p class="text-muted small">
            <?php echo Text::_('J2STORE_PASSWORD_CHANGE_OPTIONAL'); ?>
        </p>
        
        <div class="control-group mb-3">
            <label class="control-label form-label" for="profile-password1">
                <?php echo Text::_('JGLOBAL_PASSWORD'); ?>
            </label>
            <div class="controls">
                <input type="password" 
                       id="profile-password1" 
                       name="password1" 
                       class="form-control" 
                       autocomplete="new-password"
                       minlength="12" />
                <small class="form-text text-muted">
                    <?php echo Text::_('COM_USERS_MSG_PASSWORD_MINIMUM_CHARACTERS'); ?>
                </small>
            </div>
        </div>
        
        <div class="control-group mb-3">
            <label class="control-label form-label" for="profile-password2">
                <?php echo Text::_('JGLOBAL_PASSWORD_CONFIRM'); ?>
            </label>
            <div class="controls">
                <input type="password" 
                       id="profile-password2" 
                       name="password2" 
                       class="form-control" 
                       autocomplete="new-password"
                       minlength="12" />
            </div>
        </div>
        
        <div class="control-group mt-4">
            <div class="controls">
                <button type="submit" class="btn btn-primary btn-save">
                    <span class="icon-save" aria-hidden="true"></span>
                    <?php echo Text::_('JSAVE'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.j2store-myprofile-account {
    max-width: 600px;
}

.j2store-myprofile-account h3 {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #eee;
}

.j2store-myprofile-account h4 {
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.j2store-myprofile-account .btn-save {
    min-width: 150px;
}

.j2store-myprofile-account .btn-save.loading {
    opacity: 0.7;
    pointer-events: none;
}
</style>
