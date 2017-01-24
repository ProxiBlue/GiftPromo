<?php

/**
 * defines the gift product type constant
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Product_Type_Gift_Simple extends Mage_Catalog_Model_Product_Type_Simple
{

    const TYPE_CODE = 'gift-simple';

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
