<?php

/**
 * GiftPromo Model Resource Coupon_Collection
 *
 */
class ProxiBlue_GiftPromo_Model_Resource_Promo_Coupon_Collection
    extends Mage_SalesRule_Model_Mysql4_Coupon_Collection //Mage_SalesRule_Model_Resource_Coupon_Collection
{
    /**
     * Add rule to filter
     *
     * @param Mage_SalesRule_Model_Rule|int $rule
     *
     * @return Mage_SalesRule_Model_Resource_Coupon_Collection
     */
    public function addRuleToFilter($rule)
    {
        if ($rule instanceof ProxiBlue_GiftPromo_Model_Promo_Rule) {
            $ruleId = $rule->getId();
        } else {
            $ruleId = (int)$rule;
        }

        $this->addFieldToFilter('rule_id', $ruleId);

        return $this;
    }

    /**
     * Constructor
     *
     */
    protected function _construct()
    {
        $this->_init('giftpromo/promo_coupon');
    }

}
