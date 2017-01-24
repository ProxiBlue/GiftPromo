<?php

/**
 * Giftpromo Resource Coupon
 *
 */
class ProxiBlue_GiftPromo_Model_Resource_Promo_Coupon extends Mage_SalesRule_Model_Mysql4_Coupon
{ //Mage_SalesRule_Model_Resource_Coupon {

    /**
     * Constructor adds unique fields
     */

    protected function _construct()
    {
        $this->_init('giftpromo/promo_coupon', 'coupon_id');
        $this->addUniqueField(
            array(
                'field' => 'code',
                'title' => Mage::helper('salesrule')->__('Coupon with the same code')
            )
        );
    }

}
