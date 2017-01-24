<?php

class ProxiBlue_GiftPromo_Model_Resource_Promo_Rule_Customer extends Mage_Core_Model_Mysql4_Abstract
{ //Mage_Core_Model_Resource_Db_Abstract {

    /**
     * Get rule usage record for a customer
     *
     * @param Mage_SalesRule_Model_Rule_Customer $rule
     * @param int                                $customerId
     * @param int                                $ruleId
     *
     * @return Mage_SalesRule_Model_Resource_Rule_Customer
     */
    public function loadByCustomerRule($rule, $customerId, $ruleId)
    {
        $read = $this->_getReadAdapter();
        $select = $read->select()->from($this->getMainTable())
            ->where('customer_id = :customer_id')
            ->where('rule_id = :rule_id');
        $data = $read->fetchRow($select, array(':rule_id' => $ruleId, ':customer_id' => $customerId));
        if (false === $data) {
            // set empty data, as an existing rule object might be used
            $data = array();
        }
        $rule->setData($data);

        return $this;
    }

    /**
     * constructor
     *
     */

    protected function _construct()
    {
        $this->_init('giftpromo/promo_rule_customer', 'rule_customer_id');
    }

}
