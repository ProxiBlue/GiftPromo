<?php


class ProxiBlue_GiftPromo_Model_Promo_Coupon extends Mage_SalesRule_Model_Coupon
{
    /**
     * Coupon's owner rule instance
     *
     * @var Mage_SalesRule_Model_Rule
     */
    protected $_rule;

    /**
     * Set rule instance
     *
     * @param  Mage_SalesRule_Model_Rule
     *
     * @return Mage_SalesRule_Model_Coupon
     */
    public function setGiftPromoRule(ProxiBlue_GiftPromo_Model_Promo_Rule $rule)
    {
        $this->_rule = $rule;

        return $this;
    }

    protected function _construct()
    {
        $this->_init('giftpromo/promo_coupon');
    }

    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        if (!$this->getRuleId() && $this->_rule instanceof ProxiBlue_GiftPromo_Model_Promo_Rule) {
            $this->setRuleId($this->_rule->getId());
        }

        return call_user_func(array(get_parent_class(get_parent_class($this)), '_beforeSave'));
    }

}
