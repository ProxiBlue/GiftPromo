<?php

/**
 * Class ProxiBlue_GiftPromo_SalesRule_Model_Validator
 *  determine if a rule can not validate due to coupon blocker
 */
class ProxiBlue_GiftPromo_Model_SalesRule_Validator extends Mage_SalesRule_Model_Validator
{
    protected function _canProcessRule($rule, $address)
    {
        $quote = $address->getQuote();
        $appliedRuleIds = $quote->getAppliedRuleIds();
        $appliedRuleIds = explode(',', $appliedRuleIds);

        if (in_array($rule->getId(), $appliedRuleIds)) {
            if ($quote->getQuoteCurrencyCode() == Mage::app()->getStore()->getCurrentCurrencyCode()) {
                $cartGifts = mage::helper('giftpromo')->getRuleBasedCartItems();
                foreach ($cartGifts as $giftItem) {
                    if(is_object($giftItem)) {
                        $ruleObject = $giftItem->getRule();
                        $blockRules = explode(',', $ruleObject->getBlockRules());
                        if (in_array('{ALL}', $blockRules) || in_array($rule->getId(), $blockRules)) {
                            $message = $ruleObject->getBlockRulesMessage();
                            $message = str_replace('{SHOPPING_CART_RULE_NAME}', $rule->getName(), $message);
                            $message = str_replace('{GIFT_RULE_NAME}', $ruleObject->getRuleName(), $message);
                            if (!empty($message)) {
                                mage::helper('giftpromo')->insertFactoryErrorMessage($message);
                            }
                            $rule->setIsValidForAddress($address, false);
                            //$item->setAppliedRuleIds(join(',',$appliedRuleIds));
                            $currentAppliedAddressRules = explode(',', $address->getAppliedRuleIds());
                            $matched = array_search($rule->getId(), $currentAppliedAddressRules);
                            unset($currentAppliedAddressRules[$matched]);
                            $address->setAppliedRuleIds(implode(',', $currentAppliedAddressRules));
                            $quote->setAppliedRuleIds(implode(',', $currentAppliedAddressRules));
                            $quote->setCouponCode('');
                            $quote->collectTotals()->save();
                            return false;
                        }
                    }
                }
            }
        }
        return parent::_canProcessRule($rule, $address);
    }

    /**
     * This exists for compatibility with Amasty Multi Coupon module
     * Extracted from v 1.21
     */

    protected function _maintainAddressCouponCode($address, $rule)
    {
        // Rule is a part of rules collection, which includes only rules with 'No Coupon' type or with validated coupon.
        // as a result, if the rule uses coupon code(s) ('Specific' or 'Auto' Coupon Type), it always contains validated coupon
        if ($rule->getCouponType() != 1) { // Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON
            $address->setCouponCode($this->getCouponCode());
        }
    }
}
