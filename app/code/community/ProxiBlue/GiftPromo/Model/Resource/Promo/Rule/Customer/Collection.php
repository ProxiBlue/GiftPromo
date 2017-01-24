<?php

class ProxiBlue_GiftPromo_Model_Resource_Promo_Rule_Customer_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{ //Mage_Core_Model_Resource_Db_Collection_Abstract {

    /**
     * Collection constructor
     *
     */

    protected function _construct()
    {
        parent::_construct();
        $this->_init('giftpromo/promo_rule_customer');
    }

}
