<?php

class ProxiBlue_GiftPromo_Model_Resource_Promo_Coupon_Usage extends Mage_SalesRule_Model_Mysql4_Coupon_Usage
{ //Mage_SalesRule_Model_Resource_Coupon_Usage {
    /**
     * Constructor
     *
     */
    protected function _construct()
    {
        $this->_init('giftpromo/promo_coupon_usage', '');
    }

}
