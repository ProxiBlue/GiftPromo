<?php

class ProxiBlue_GiftPromo_Helper_Productimg extends Mage_ConfigurableSwatches_Helper_Productimg
{

    /**
     * Create the separated index of product images
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array|null                 $preValues
     *
     * @return Mage_ConfigurableSwatches_Helper_Data
     */
    public function indexProductImages($product, $preValues = null)
    {
        // temp change gift-configurable to normal so swatches will index images
        if ($product->getTypeId() == ProxiBlue_GiftPromo_Model_Product_Type_Gift_Configurable::TYPE_CODE) {
            $product->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);
            parent::indexProductImages($product, $preValues);
            $product->setTypeId(ProxiBlue_GiftPromo_Model_Product_Type_Gift_Configurable::TYPE_CODE);
        } else {
            parent::indexProductImages($product, $preValues);
        }
    }

}
