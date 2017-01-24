<?php

/**
 * Get gifts products attached to currnt viewed product
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Product_List_Icon
    extends ProxiBlue_GiftPromo_Block_Product_List_Abstract
{
    private $_enabled = null;

    /**
     * Get assigned gift icon, or global icon if none assigned
     *
     * @param int $size
     *
     * @return Varien_Object
     */
    public function getGiftIcon($size = 50)
    {
        //$_product = $this->getProduct()->load($this->getProduct()->getId());
        $image = false;
        try {
            $_resource = $this->getProduct()->getResource();
            $gift_promotion_icon = $_resource->getAttributeRawValue($this->getProduct()->getId()
                , 'gift_promotion_icon', Mage::app()->getStore());
            if($gift_promotion_icon && $gift_promotion_icon != 'no_selection') {
                $result = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog' . DS . 'product'
                    . DS . $gift_promotion_icon;
                return $result;
            }
        } catch (Exception $e) {
            mage::logException($e);
            // fail silently.
        }
        if (is_object($image)) {
            return $image;
        }

        foreach ($this->_itemCollection as $_item) {
            /**
             * this is not perfect.
             *
             * If a product validates to multiple rules, which icon to display?
             * At present I am using teh first found items rule icon.
             * Will see how that goes
             *
             **/
            if($_item->getRuleLevelIconFile() && $_item->getRuleLevelIconFile() != 'no_selection') {
                $result = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $_item->getRuleLevelIconFile();
                return $result;
            }
        }

        return $this->getSkinUrl('images/giftpromo/gift-icon.png');
    }

    /**
     * Prepare the data collection
     *
     * @return \ProxiBlue_GiftPromo_Block_Product_List_Icon
     */
    protected function _prepareData()
    {
        try {
            if ($this->isEnabled()) {
                $this->_itemCollection = Mage::helper('giftpromo/gifticon')
                    ->testItemHasValidGifting($this->getProduct(), false);
            }

            return $this;
        } catch (Exception $e) {
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }
    }

    /**
     * Is icons enabled?
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (is_null($this->_enabled)) {
            $this->_enabled = Mage::getStoreConfig('giftpromo/catalog/icons_enabled');
        }
        return $this->_enabled;
    }

}
