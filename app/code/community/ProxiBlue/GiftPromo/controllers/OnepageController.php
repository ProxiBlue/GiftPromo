<?php
/**
 * Gift Promo onepage controller
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 * */
require_once(Mage::getModuleDir(
        'controllers',
        'Mage_Checkout'
    ) . DS . 'OnepageController.php');


class ProxiBlue_GiftPromo_OnepageController extends Mage_Checkout_OnepageController
{

    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     */
    protected function _expireAjax()
    {
        if (!$this->getOnepage()->getQuote()->hasItems()
            || $this->getOnepage()->getQuote()->getHasError()
            || $this->getOnepage()->getQuote()->getIsMultiShipping()
        ) {
            $this->_ajaxRedirectResponse();

            return true;
        }
        $action = $this->getRequest()->getActionName();
        if (Mage::getSingleton('checkout/session')->getCartWasUpdated(true)
            && !in_array($action, array('index', 'progress', 'review', 'savePayment'))
        ) {
            $this->_ajaxRedirectResponse();

            return true;
        }

        return false;
    }

}
