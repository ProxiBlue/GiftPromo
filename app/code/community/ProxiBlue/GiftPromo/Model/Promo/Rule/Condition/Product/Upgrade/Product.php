<?php


class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Upgrade_Product
    extends Mage_SalesRule_Model_Rule_Condition_Product
{

    /**
     * Default operator options getter
     * Provides all possible operator options
     *
     * @return array
     */
    public function getDefaultOperatorOptions()
    {
        if (null === $this->_defaultOperatorOptions) {
            $this->_defaultOperatorOptions = array(
                '=='  => Mage::helper('rule')->__('is'),
                '!='  => Mage::helper('rule')->__('is not'),
                '{}'  => Mage::helper('rule')->__('contains'),
                '!{}' => Mage::helper('rule')->__('does not contain'),
            );
        }

        return $this->_defaultOperatorOptions;
    }

    /**
     * Validate Product Rule Condition
     *
     * @param Varien_Object $object
     *
     * @return bool
     */
    public function validate(Varien_Object $object)
    {
        // must load the product as it may not contain all loaded attributes to test with

        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->load($object->getProductId());
        $product
            ->setQuoteItemQty($object->getQty())
            ->setQuoteItemPrice($object->getPrice())// possible bug: need to use $object->getBasePrice()
            ->setQuoteItemRowTotal($object->getBaseRowTotal());
        // fix issue with magento EE 1.12
        // class inheritance is different, and the call parent will reset the value
        // a simple workaround is to set the product as an object of itself and also
        // to set the base values to what we need, else they get replaced
        $product
            ->setQty($object->getQty())
            ->setPrice($object->getPrice())// possible bug: need to use $object->getBasePrice()
            ->setBaseRowTotal($object->getBaseRowTotal());
        $product->setProduct($product);

        return parent::validate($product);
    }

}
