<?php

/**
 * defines the gift product type constant
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Product_Type_Gift_Configurable extends Mage_Catalog_Model_Product_Type_Configurable
{

    const TYPE_CODE = 'gift-configurable';

    public function getIsSalable($product = null)
    {
        $salable = $this->getProduct($product)->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        if ($salable && !is_null($this->getProduct($product)->getData('is_salable'))
            && $this->getProduct($product)->hasData('is_salable')
        ) {
            $salable = $this->getProduct($product)->getData('is_salable');
        } elseif ($salable && $this->isComposite()) {
            $salable = null;
        }

        return (boolean)(int)$salable;
    }

}