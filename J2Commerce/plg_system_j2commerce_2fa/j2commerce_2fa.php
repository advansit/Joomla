<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

class PlgSystemJ2commerce_2fa extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function onUserAfterLogin($user, $options = [])
    {
        // Basic 2FA support - preserve session after 2FA login
        if (isset($options['twoFactorAuth']) && $options['twoFactorAuth']) {
            $app = Factory::getApplication();
            $session = $app->getSession();
            
            // Preserve J2Commerce cart data
            if ($this->params->get('preserve_cart', 1)) {
                $cartData = $session->get('j2store.cart', null);
                if ($cartData) {
                    $session->set('j2store.cart.preserved', $cartData);
                }
            }
        }
        
        return true;
    }
}
