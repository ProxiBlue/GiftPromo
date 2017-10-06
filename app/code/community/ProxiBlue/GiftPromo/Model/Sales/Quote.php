<?php

/**
 * Sales quote
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 * */
class ProxiBlue_GiftPromo_Model_Sales_Quote extends Mage_Sales_Model_Quote
{
    /**
     * Trigger collect totals after loading, if required
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _afterLoad()
    {
        // collect totals and save me, if required
        if (1 == $this->getData('trigger_recollect')
            && !Mage::getSingleton('checkout/session')->getSkipTriggerCollect()
        ) {
            $this->_preventSaving = true;
            $this->collectTotals()->save();
        }
        Mage::getSingleton('checkout/session')->setSkipTriggerCollect(false);

        return call_user_func(array(get_parent_class(get_parent_class($this)), '_afterLoad'));
    }


    /**
     * Compatibility with easylife_sharedcart + Swift OnepageCheckout
     *
     * @return array;
     */
    public function getSharedStoreIds()
    {
        $modules = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array)$modules;
        if (!array_key_exists('Swift_OnepageCheckout', $modulesArray)) {
            if (array_key_exists('Easylife_SharedCart', $modulesArray)) {
                if (Mage::helper('sharedcart')->getIsQuotePersistent()) {//if behavior is not diasabled
                    $ids = Mage::getModel('core/store')->getCollection()->getAllIds();
                    unset($ids[0]);//remove admin just in case
                    return $ids;
                }
            }
        }
        return parent::getSharedStoreIds();
    }

}
