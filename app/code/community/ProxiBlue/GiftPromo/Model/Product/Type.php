<?php

/**
 * Defines the simple product gift type constant
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Product_Type extends Mage_Catalog_Model_Product_Type
{

    const TYPE_GIFT = 'gift-';

    /**
     * Here as the getTypes method will not be directly overwritten
     *
     * @return type
     */
    static public function getOptionArray()
    {
        $options = array();
        foreach (self::getTypes() as $typeId => $type) {
            $options[$typeId] = Mage::helper('catalog')->__($type['label']);
        }

        return $options;
    }

    /**
     * Remove gift product type from admin display.
     *
     * @return type
     */
    static public function getTypes()
    {
        if (is_null(self::$_types)) {
            $productTypes = Mage::getConfig()->getNode('global/catalog/product/type')->asArray();
            // remove gift type from admin displays
            //if(Mage::app()->getStore()->isAdmin() && array_key_exists(ProxiBlue_GiftPromo_Model_Product_Type::TYPE_GIFT, $productTypes)){
            unset($productTypes[ProxiBlue_GiftPromo_Model_Product_Type_Gift_Simple::TYPE_CODE]);
            unset($productTypes[ProxiBlue_GiftPromo_Model_Product_Type_Gift_Configurable::TYPE_CODE]);
            unset($productTypes[ProxiBlue_GiftPromo_Model_Product_Type_Gift_Downloadable::TYPE_DOWNLOADABLE]);
            unset($productTypes[ProxiBlue_GiftPromo_Model_Product_Type_Gift_Bundle::TYPE_CODE]);
            //}
            foreach ($productTypes as $productKey => $productConfig) {
                $moduleName = 'catalog';
                if (isset($productConfig['@']['module'])) {
                    $moduleName = $productConfig['@']['module'];
                }
                $translatedLabel = Mage::helper($moduleName)->__($productConfig['label']);
                $productTypes[$productKey]['label'] = $translatedLabel;
            }
            self::$_types = $productTypes;
        }

        return self::$_types;
    }

}