<?php

/**
 * Sales quote item
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 **/
class ProxiBlue_GiftPromo_Model_Sales_Quote_Item extends Mage_Sales_Model_Quote_Item
{

    /**
     * Check product representation in item
     *
     * handles checking for the gift products in the cart, to allow multiple qty when the same gift is added
     * handles adding a gift to the cart if the gift item is in cart but not as a gift
     *
     * @param   Mage_Catalog_Model_Product $product
     *
     * @return  bool
     */
    public function representProduct($product)
    {
        // bundles and non free are always as single line items
        if ($product->getTypeId() == ProxiBlue_GiftPromo_Model_Product_Type_Gift_Bundle::TYPE_CODE
            || $product->getGiftedPrice() > 0
        ) {
            return false;
        }
        $itemProduct = $this->getProduct();
        $infoBuyRequest = $product->getCustomOption('info_buyRequest');
        if ($infoBuyRequest instanceof Mage_Catalog_Model_Product_Configuration_Item_Option) {
            $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
            if ($buyRequest->getAddedByRule()) {
                $itemInfoBuyRequest = $this->getOptionByCode('info_buyRequest');
                $itemBuyRequest = new Varien_Object(unserialize($itemInfoBuyRequest->getValue()));
                if ($buyRequest->getAddedByRule() != $itemBuyRequest->getAddedByRule()) {
                    return false;
                }
            }
        }
        if (!$product || $itemProduct->getId() != $product->getId()
            || $itemProduct->getTypeId() != $product->getTypeId()
        ) {
            return false;
        }

        return parent::representProduct($product);
    }
}

