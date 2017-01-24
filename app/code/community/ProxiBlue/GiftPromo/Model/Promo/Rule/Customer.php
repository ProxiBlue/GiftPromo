<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Customer extends Mage_Core_Model_Abstract
{
    public function loadByCustomerRule($customerId, $ruleId)
    {
        $this->_getResource()->loadByCustomerRule($this, $customerId, $ruleId);

        return $this;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->_init('giftpromo/promo_rule_customer');
    }
}
