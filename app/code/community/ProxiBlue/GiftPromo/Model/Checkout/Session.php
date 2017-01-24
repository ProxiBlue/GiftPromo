<?php

/**
 * Class ProxiBlue_GiftPromo_Model_Checkout_Session
 *
 * Swap merging of quotes.
 * Merge OLD to CURRENT (magento does CURRENT to OLD)
 *
 * This causes issues with selectable gifts as all ids get changed.
 *
 */
class ProxiBlue_GiftPromo_Model_Checkout_Session extends Mage_Checkout_Model_Session
{
    /**
     * Load data for customer quote and merge with current quote
     *
     * @return Mage_Checkout_Model_Session
     */
    public function loadCustomerQuote()
    {

        if (!Mage::getSingleton('customer/session')->getCustomerId()) {
            return $this;
        }

        // are there any gifts in the current quote?
        // no, use core code.
        $giftProducts = mage::helper('giftpromo')->getAllGiftBasedCartItems();
        if (count($giftProducts) == 0) {
            return parent::loadCustomerQuote();
        }

        Mage::dispatchEvent('load_customer_quote_before', array('checkout_session' => $this));

        $customerQuote = Mage::getModel('sales/quote')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomerId());

        if ($customerQuote->getId() && $this->getQuoteId() != $customerQuote->getId()) {
            if ($this->getQuoteId()) {
                $this->getQuote()->merge($customerQuote)
                    ->collectTotals()
                    ->save();
            } else {
                return parent::loadCustomerQuote();
            }
            // clean out old customer quote object
            $customerQuote->delete();

        } else {
            $this->getQuote()->getBillingAddress();
            $this->getQuote()->getShippingAddress();
            $this->getQuote()->setCustomer(Mage::getSingleton('customer/session')->getCustomer())
                ->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
        }

        return $this;
    }

    /**
     * Compatibility with easylife_sharedcart
     *
     * @return string
     */
    protected function _getQuoteIdKey()
    {
        $modules = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array)$modules;
        if (!array_key_exists('Swift_OnepageCheckout', $modulesArray)) {
            if (array_key_exists('Easylife_SharedCart', $modulesArray)) {
                $helper = Mage::helper('sharedcart');
                if (is_object($helper) && Mage::helper('sharedcart')->getIsQuotePersistent()) {
                    return 'quote_id';
                }
            }
        }
        return parent::_getQuoteIdKey();
    }

}
