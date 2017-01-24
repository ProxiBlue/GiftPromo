<?php

/**
 * Gift Product shopping cart item renderer
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Cart_Item_Renderer_Configurable
    extends ProxiBlue_GiftPromo_Block_Cart_Item_Renderer_Abstract
{

    /**
     * Get product customize options
     *
     * @return array || false
     */
    public function getProductOptions()
    {
        /* @var $helper Mage_Catalog_Helper_Product_Configuration */
        $helper = Mage::helper('giftpromo/product_configuration');

        return $helper->getCustomOptions($this->getItem());
    }

    /**
     * Get list of all otions for product
     *
     * @return array
     */
    public function getOptionList()
    {
        /* @var $helper Mage_Catalog_Helper_Product_Configuration */
        $helper = Mage::helper('giftpromo/product_configuration');
        $options = $helper->getConfigurableOptions($this->getItem());

        return $options;
    }
}
