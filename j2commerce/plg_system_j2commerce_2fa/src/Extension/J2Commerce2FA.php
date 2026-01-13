<?php
namespace Advans\Plugin\System\J2Commerce2FA\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

class J2Commerce2FA extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function onUserAfterLogin($user, $options = [])
    {
        try {
            $app = Factory::getApplication();
            $session = $app->getSession();
            
            $is2FA = isset($options['twoFactorAuth']) && $options['twoFactorAuth'];
            
            if ($is2FA) {
                if ($this->params->get('preserve_cart', 1)) {
                    $this->preserveCartData($session);
                }
                
                if ($this->params->get('preserve_session', 1)) {
                    $this->preserveSessionData($session);
                }
            } else {
                if ($this->params->get('preserve_guest_cart', 1)) {
                    $this->transferGuestCart($session, $user);
                }
            }
            
            if ($this->params->get('regenerate_session', 1)) {
                $session->restart();
            }
        } catch (\Exception $e) {
            if ($this->params->get('debug', 0)) {
                Factory::getApplication()->enqueueMessage(
                    'J2Commerce 2FA Plugin Error: ' . $e->getMessage(),
                    'warning'
                );
            }
        }
        
        return true;
    }

    private function preserveCartData($session)
    {
        $cartData = $session->get('j2store.cart', null);
        if ($cartData) {
            $session->set('j2store.cart.preserved', $cartData);
        }
        
        $j2commerceCart = $session->get('j2commerce.cart', null);
        if ($j2commerceCart) {
            $session->set('j2commerce.cart.preserved', $j2commerceCart);
        }
    }

    private function preserveSessionData($session)
    {
        $returnUrl = $session->get('return_url', null);
        if ($returnUrl) {
            $session->set('return_url.preserved', $returnUrl);
        }
        
        $checkoutData = $session->get('checkout.data', null);
        if ($checkoutData) {
            $session->set('checkout.data.preserved', $checkoutData);
        }
    }

    private function transferGuestCart($session, $user)
    {
        $guestCart = $session->get('j2store.cart.guest', null);
        
        if ($guestCart && isset($user['id'])) {
            $session->set('j2store.cart.user_' . $user['id'], $guestCart);
            $session->clear('j2store.cart.guest');
        }
    }
}
