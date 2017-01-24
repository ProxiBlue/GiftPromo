<?php

class ProxiBlue_GiftPromo_Helper_Product_Configuration extends Mage_Catalog_Helper_Product_Configuration
//    implements Mage_Catalog_Helper_Product_Configuration_Interface
{
    const XML_PATH_CONFIGURABLE_ALLOWED_TYPES = 'global/catalog/product/type/configurable/allow_product_types';

    /**
     * Retrieves product options list
     *
     * @param Mage_Catalog_Model_Product_Configuration_Item_Interface $item
     *
     * @return array
     */
    public function getOptions(Mage_Catalog_Model_Product_Configuration_Item_Interface $item)
    {
        $typeId = $item->getProduct()->getTypeId();
        switch ($typeId) {
            case ProxiBlue_GiftPromo_Model_Product_Type_Gift_Configurable::TYPE_CODE:
                return $this->getConfigurableOptions($item);
                break;
//            case Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE:
//                return $this->getGroupedOptions($item);
//                break;
        }

        return $this->getCustomOptions($item);
    }

    /**
     * Retrieves configuration options for configurable product
     *
     * @param Mage_Catalog_Model_Product_Configuration_Item_Interface $item
     *
     * @return array
     */
    public function getConfigurableOptions(Mage_Catalog_Model_Product_Configuration_Item_Interface $item)
    {
        $product = $item->getProduct();
        $typeId = $product->getTypeId();
        if ($typeId != ProxiBlue_GiftPromo_Model_Product_Type_Gift_Configurable::TYPE_CODE) {
            Mage::throwException($this->__('Wrong product type to extract configurable options.'));
        }
        $attributes = $product->getTypeInstance(true)
            ->getSelectedAttributesInfo($product);

        return array_merge($attributes, $this->getCustomOptions($item));
    }

}
