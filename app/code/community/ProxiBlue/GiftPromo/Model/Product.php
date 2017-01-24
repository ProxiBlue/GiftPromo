<?php

/**
 * Gift product model
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 *
 */
class ProxiBlue_GiftPromo_Model_Product extends Mage_Catalog_Model_Product
{

    const ADD_METHOD_DIRECT = 0;
    const ADD_METHOD_SELECT = 1;
    const ADD_METHOD_SELECT_ONE = 2;

    private $_checkBoxAllowed = array(ProxiBlue_GiftPromo_Model_Product_Type_Gift_Simple::TYPE_CODE);

    /**
     * Internal holder for helper class
     *
     * @var object
     */
    private $_helper;

    /**
     * Get product url model
     *
     * @return Mage_Catalog_Model_Product_Url
     */
    public function getUrlModel()
    {
        if ($this->_urlModel === null) {
            $this->_urlModel = Mage::getModel('giftpromo/product_url');
        }

        return $this->_urlModel;
    }

    /**
     * Load object data
     *
     * @param   integer $id
     *
     * @return  Mage_Core_Model_Abstract
     */
    public function load($id, $field = null)
    {
        $product = parent::load($id, $field);

        return $this->cloneProduct($product, false);

    }

    /**
     * Clone an existing product to turn it into a gift product type
     *
     * @param Mage_Catalog_Model_Product $product
     * @param bool                       $isGift
     *
     * @return \ProxiBlue_GiftPromo_Model_Product
     */
    public function cloneProduct(Mage_Catalog_Model_Product $product, $reload = true)
    {
        if ($reload) {
            parent::load($product->getId());
        }
        $infoBuyRequest = $product->getCustomOption('info_buyRequest');
        if ($infoBuyRequest) {
            $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
            if ($buyRequest->getAddedByRule()) {
                $ruleModel = Mage::getModel('giftpromo/promo_rule')->load($buyRequest->getAddedByRule());
                $ruleGiftproducts = $ruleModel->getGiftedProducts();
                if (array_key_exists($this->getId(), $ruleGiftproducts)) {
                    $newData = array_merge($this->getData(), $ruleGiftproducts[$this->getId()]);
                    $newData['rule_level_icon_file'] = $ruleModel->getIconFile();
                    $newData['product_level_icon'] = $product->getGiftPromotionIcon();
                    $this->setData($newData);
                    // force to gift type
                    $this->setTypeId(ProxiBlue_GiftPromo_Model_Product_Type::TYPE_GIFT . $this->getTypeId());
                }
            }
            if ($buyRequest->getGiftedPrice()) {
                $this->setGiftedPrice($buyRequest->getGiftedPrice());
                $this->setCalculatedFinalPrice($buyRequest->getGiftedPrice());
                $this->setConvertedPrice($buyRequest->getGiftedPrice());
                $this->setBasePrice($buyRequest->getGiftedPrice());
                $this->setPrice($buyRequest->getGiftedPrice());
            }
        } else {
            if ($ruleModel = mage::registry('current_rule_object')) {
                $ruleGiftproducts = $ruleModel->getGiftedProducts();
                if (is_array($ruleGiftproducts) && array_key_exists($this->getId(), $ruleGiftproducts)) {
                    $newData = array_merge($this->getData(), $ruleGiftproducts[$this->getId()]);
                    $newData['rule_level_icon_file'] = $ruleModel->getIconFile();
                    $newData['product_level_icon'] = $product->getGiftPromotionIcon();
                    $this->setData($newData);
                    // force to gift type
                    $this->setTypeId(ProxiBlue_GiftPromo_Model_Product_Type::TYPE_GIFT . $this->getTypeId());
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve add to cart url
     *
     * @return string
     */
    public function getAddToCartUrl()
    {
        return mage::helper('giftpromo/cart')->getAddUrl($this);
    }

    /**
     * Get product final price
     *
     * @param double $qty
     *
     * @return double
     */
    public function getFinalPrice($qty = null)
    {
        $price = $this->_getData('final_price');
        if ($price !== null) {
            return $price;
        }

        return $this->getPriceModel()->getFinalPrice($qty, $this);
    }

    /**
     * Get the helper class and cache teh object
     *
     * @return object
     */
    private function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::Helper('giftpromo');
        }

        return $this->_helper;
    }

    /**
     * Determine if product type can use multiselect checkbox in selectgifts.phtml display
     * @return bool
     */
    public function canUseSelectListCheckbox(){
        if(Mage::getStoreConfig('giftpromo/cart/selectitems_multiselect')) {
            return in_array($this->getTypeId(), $this->_checkBoxAllowed);
        }
        return false;
    }

    /**
     * Get collection instance
     *
     * @return object
     */
    public function getResourceCollection()
    {
        $collection = Mage::getResourceModel('giftpromo/catalog_product_collection');
        $collection->setStoreId($this->getStoreId());
        return $collection;
    }


}
