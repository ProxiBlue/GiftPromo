<?php

/**
 * GiftPromo Validator Model
 *
 * Allows dispatching before and after events for each controller action
 *
 * @category   Giftpromo
 * @package    ProxibLue_GiftPromo
 * @author     Lucas van Staden (sales@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Promo_Validator
    extends Mage_Core_Model_Abstract
{

    /**
     * Rule source collection
     *
     * @var Mage_SalesRule_Model_Mysql4_Rule_Collection
     */
    protected $_rules;
    protected $_roundingDeltas = array();
    protected $_baseRoundingDeltas = array();

    /**
     * Defines if method Mage_SalesRule_Model_Validator::reset() wasn't called
     * Used for clearing applied rule ids in Quote and in Address
     *
     * @var bool
     */
    protected $_isFirstTimeResetRun = true;

    /**
     * Information about item totals for rules.
     *
     * @var array
     */
    protected $_rulesItemTotals = array();

    /**
     * Store information about addresses which cart fixed rule applied for
     *
     * @var array
     */
    protected $_cartFixedRuleUsedForAddress = array();

    protected $_helper = null;

    /**
     * Init validator
     * Init process load collection of rules for specific website,
     * customer group and coupon code
     *
     * @param   int $websiteId
     * @param   int $customerGroupId
     * @param   string $couponCode
     *
     * @return  Mage_SalesRule_Model_Validator
     */
    public function init($websiteId, $customerGroupId, $couponCode)
    {
        $this->setWebsiteId($websiteId)
            ->setCustomerGroupId($customerGroupId)
            ->setCouponCode($couponCode);

        return $this;
    }

    /**
     * Reset quote and address applied rules
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return Mage_SalesRule_Model_Validator
     */
    public function reset(Mage_Sales_Model_Quote_Address $address)
    {
        if ($this->_isFirstTimeResetRun) {
            $address->setAppliedRuleIds('');
            $address->getQuote()->setAppliedRuleIds('');
            $this->_isFirstTimeResetRun = false;
        }

        return $this;
    }

    /**
     * Apply discounts to shipping amount
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     *
     * @return  Mage_SalesRule_Model_Validator
     */
    public function processGiftRules($address)
    {
        // is the cart ready?
        $allVisibleItems = $this->_getHelper()->getAllVisibleItems($address->getQuote());
        foreach ($allVisibleItems as $item) {
            if ($this->_getHelper()->testGiftTypeCode($item->getProductType())) {
                continue;
            }
            if (!$item->getId()) {
                return $this;
            }

        }

        Mage::getSingleton('checkout/session')->setSkipTriggerCollect(
            true
        ); // prevents endless loop if session get mucked up.
        $rulesCollection = Mage::getModel('giftpromo/promo_rule')->getCollection();
        $rulesArray = $rulesCollection->asArray();
        if (isset($rulesArray[0])) {
            $zeroRules = $rulesArray[0];
            unset($rulesArray[0]);
            array_unshift($rulesArray, $zeroRules);
        }

        $skipOtherRules = false;
        foreach ($rulesArray as $rules) {
            if ($skipOtherRules) {
                break;
            }
            foreach ($rules as $ruleObject) {
                //$ruleObject = Mage::getModel('giftpromo/promo_rule')->load($rule->getId());
                $address->getQuote()->setGiftTriggerItem(false);
                if ($this->canProcessRule($ruleObject, $address)) {
                    $validTest = $ruleObject->validate($address->getQuote());
                    if (!$ruleObject->getAllowGiftSelection() && $validTest) {
                        $giftProducts = $ruleObject->getItemsArray();
                        $giftItemsInCart = $ruleObject->getGiftCartItems($address->getQuote());
                        $triggerItem = $address->getQuote()->getGiftTriggerItem();
                        if (is_object($triggerItem)) {
                            foreach ($giftItemsInCart as $giftKey => $currentCartGiftItem) {
                                $currentGiftAddedBy = $this->_getHelper()->getParentQuoteItemOfGift(
                                    $currentCartGiftItem,
                                    true
                                );
                                if ($triggerItem->getId() != $currentGiftAddedBy) {
                                    unset($giftItemsInCart[$giftKey]);
                                }
                            }
                        }
                        $giftDiff = array_diff_key(
                            $giftProducts,
                            $giftItemsInCart
                        );
                        if (count($giftDiff) != 0) {
                            Mage::dispatchEvent(
                                'gift_product_gifts_changed_rule',
                                array(
                                    'quote' => $address->getQuote(),
                                    'gift_diff' => $giftDiff,
                                    'in_cart' => $giftItemsInCart,
                                    'gift_products' => $giftProducts
                                )
                            );
                            $params = array();

                            if (mage::registry('qty_override')) {
                                $params['multiple_of'] = array($ruleObject->getId(), mage::registry('qty_override'));
                            }

                            if (is_object($triggerItem)) {
                                if (!$triggerItem->getId()) {
                                    try {
                                        try {
                                            $triggerItem->save();
                                        } catch (Exception $e) {
                                            // if this is the first item, or session timeout quote object may not saved
                                            $address->getQuote()->save();
                                            $triggerItem->save();
                                        }
                                    } catch (Exception $e) {
                                        mage::logException($e);
                                    }
                                }
                                $params = array(
                                    'parent_quote_item_id' => $triggerItem->getId(),
                                    'parent_product_id' => $triggerItem->getProductId());
                                $triggerItem->setAppliedGiftRuleIds(
                                    json_encode(
                                        array(
                                            $triggerItem->getId() => $ruleObject->getId())
                                    )
                                );
                                try {
                                    $triggerItem->save();
                                } catch (Exception $e) {
                                    // if this is the first item, or session timeout quote object may not saved
                                    $address->getQuote()->save();
                                    $triggerItem->save();
                                }
                            } else {
                                // flag all gifting items, that do not have parent items
                                // to be removed if any cart item is removed.
                                // this will force them to be re-evaluated, and added back to cart if still
                                // valid
                                $params = array_merge(
                                    $params, array(
                                        'remove_on_delete' => true
                                    )
                                );
                            }
                            try {
                                $this->_getHelper()->addGiftItems(
                                    $giftDiff,
                                    $ruleObject,
                                    $params
                                );
                            } catch (Exception $e) {
                                if (Mage::app()->getStore()->isAdmin()) {
                                    Mage::getSingleton('adminhtml/session_quote')->addError(
                                        $e->getMessage()
                                    );
                                } else {
                                    if (!$this->_getHelper()->isPre16()) {
                                        $messageFactory = Mage::getSingleton('core/message');
                                        $message = $messageFactory->error(
                                            $e->getMessage()
                                        );
                                        Mage::getModel('checkout/cart')->getCheckoutSession()->addUniqueMessages(
                                            $message
                                        );
                                    } else {
                                        Mage::getModel('checkout/cart')->getCheckoutSession()->addMessage(
                                            $e->getMessage()
                                        );
                                    }
                                }
                                mage::getModel('checkout/session')->setBusyGiftCollecting(false);
                            }
                        }
                        // if valid test if we need to stop further rules from processing
                        if ($ruleObject->getStopRulesProcessing($rules, $address->getQuote())) {
                            $skipOtherRules = true;
                        }
                    } elseif ($ruleObject->getAllowGiftSelection()) {
                        if (!$ruleObject->validate($address->getQuote())) {
                            // clear any stored session data for selected gift
                            $currentSelectedGifts = $this->_getHelper()->getCurrentSelectedGifts();
                            foreach ($currentSelectedGifts as $currentSelectedGiftsKey => $currentSelectedGiftsValue) {
                                $keyParts = explode(
                                    "_",
                                    $currentSelectedGiftsValue
                                );
                                $rulePart = array_shift($keyParts);
                                if ($ruleObject->getId() == $rulePart) {
                                    unset($currentSelectedGifts[$currentSelectedGiftsKey]);
                                    $this->_getHelper()->setCurrentSelectedGifts($currentSelectedGifts);
                                }
                            }
                            if (Mage::getSingleton('core/session')->getSkipLimitValidation() != true) {
                                if (!$ruleObject->getNotValidDueToUsageLimit()) {
                                    // clear out any items in cart for this rule
                                    $ruleBasedCartItems = $this->_getHelper()->getRuleBasedCartItems(true);
                                    foreach ($ruleBasedCartItems as $ruleCartItem) {
                                        foreach ($ruleCartItem as $cartItemKey => $cartItem) {
                                            $buyRequest = $this->_getHelper()->isAddedAsGift($cartItem);
                                            $ruleId = $buyRequest->getAddedByRule();
                                            if ($ruleId == $ruleObject->getId()) {
                                                $address->getQuote()->removeItem($cartItem->getId());
                                            }
                                        }
                                    }
                                }
                            }
                            Mage::getSingleton('core/session')->setSkipLimitValidation(false);
                        } else {
                            if ($ruleObject->getStopRulesProcessing($rules, $address->getQuote()) && $validTest) {
                                $skipOtherRules = true;
                            }
                        }
                        $giftItemsInCart = $ruleObject->getGiftCartItems($address->getQuote());
                        $removed = false;
                        $currentRuleSelectedGift = false;
                        if (count($giftItemsInCart) != 0) {
                            foreach ($giftItemsInCart as $cartItem) {
                                $infoBuyRequest = $cartItem->getOptionByCode('info_buyRequest');
                                $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
                                $currentSelectedGifts = $this->_getHelper()->getCurrentSelectedGifts();
                                if (array_search(
                                        $buyRequest->getSelectedGiftItemKey(),
                                        $currentSelectedGifts
                                    ) !== false
                                ) {
                                    $selectedKey = array_search(
                                        $buyRequest->getSelectedGiftItemKey(),
                                        $currentSelectedGifts
                                    );
                                    $parts = explode('_', $currentSelectedGifts[$selectedKey]);
                                    $currentRuleSelectedGift = array_pop($parts);
                                }
                                if ($currentRuleSelectedGift != false
                                    && $currentRuleSelectedGift != $cartItem->getProductId()
                                ) {
                                    $removed = true;
                                    $address->getQuote()->removeItem($cartItem->getId());
                                }
                            }
                            if ($removed) {
                                $address->getQuote()->save();
                            }
                        }
                    } else {
                        $ruleBasedCartItems = $this->_getHelper()->getRuleBasedCartItems(true);
                        foreach ($ruleBasedCartItems as $ruleCartItem) {
                            foreach ($ruleCartItem as $cartItemKey => $cartItem) {
                                $buyRequest = $this->_getHelper()->isAddedAsGift($cartItem);
                                $ruleId = $buyRequest->getAddedByRule();
                                if ($ruleId == $ruleObject->getId()) {
                                    $address->getQuote()->removeItem($cartItem->getId());
                                }
                            }
                        }
                    }
                } else {
                    // is there an item in the cart with this rule that does not validate?
                    // if so remove the item(s)
                    if (mage::registry('skip_extra_check_one') != true) {
                        mage::register(
                            'skip_extra_check_one',
                            true,
                            true
                        );
                        $ruleBasedCartItems = $this->_getHelper()->getRuleBasedCartItems(true);
                        foreach ($ruleBasedCartItems as $ruleCartItem) {
                            foreach ($ruleCartItem as $cartItemKey => $cartItem) {
                                $buyRequest = $this->_getHelper()->isAddedAsGift($cartItem);
                                $ruleId = $buyRequest->getAddedByRule();
                                if ($ruleId == $ruleObject->getId()) {
                                    $address->getQuote()->removeItem($cartItem->getId());
                                }
                            }
                        }
                    }
                }
            }
        }
        // and test all rule based cart items for inactive rules
        if (mage::registry('skip_extra_check_two') != true
            && Mage::getSingleton('core/session')->getSkipInactiveRuleTest() == false
        ) {
            mage::register(
                'skip_extra_check_two',
                true,
                true
            );
            $ruleBasedCartItems = $this->_getHelper()->getRuleBasedCartItems(true);
            $lastRuleId = 0;
            foreach ($ruleBasedCartItems as $ruleCartItem) {
                foreach ($ruleCartItem as $cartItemKey => $cartItem) {
                    $buyRequest = $this->_getHelper()->isAddedAsGift($cartItem);
                    $ruleId = $buyRequest->getAddedByRule();
                    if ($ruleId != $lastRuleId) {
                        $ruleObject = $cartItem->getRule();
                    }
                    if ($ruleObject->getIsActive() == 0) {
                        $address->getQuote()->removeItem($cartItem->getId());
                    }
                    if (!$ruleObject->validate($address->getQuote())) {
                        $address->getQuote()->removeItem($cartItem->getId());
                        $appliedGiftRuleIds = $this->_getHelper()
                            ->getAppliedRuleIds($address->getQuote()->getAppliedGiftRuleIds());
                        unset($appliedGiftRuleIds[$cartItem->getId()]);
                        $appliedGiftRuleIds = json_encode($appliedGiftRuleIds);
                        $address->getQuote()->setAppliedGiftRuleIds($appliedGiftRuleIds);
                    }
                    if ($buyRequest->getIsInjectedGift() && !$ruleObject->validate($address->getQuote())) {
                        $address->getQuote()->removeItem($cartItem->getId());
                        $appliedGiftRuleIds = $this->_getHelper()
                            ->getAppliedRuleIds($address->getQuote()->getAppliedGiftRuleIds());
                        unset($appliedGiftRuleIds[$cartItem->getId()]);
                        $appliedGiftRuleIds = json_encode($appliedGiftRuleIds);
                        $address->getQuote()->setAppliedGiftRuleIds($appliedGiftRuleIds);
                    }
                    $lastRuleId = $ruleId;
                }
            }
        }

        Mage::getSingleton('core/session')->setSkipInactiveRuleTest(false);
        $quoteGiftItems = $this->_getHelper()->getRuleBasedCartItems();

        Mage::helper('giftpromo')->calculateQtyrate(
            $quoteGiftItems,
            $address->getQuote()
        );

        return $this;
    }

    /**
     * Check if rule can be applied for specific address/quote/customer
     *
     * @param   Mage_SalesRule_Model_Rule $rule
     * @param   Mage_Sales_Model_Quote_Address $address
     *
     * @return  bool
     */
    public function canProcessRule($rule, $address)
    {
        $rule->setIsValid(true);
        $rule->setIsValidForAddress($address, true);
        /**
         * check per coupon usage limit
         */
        if ($rule->getCouponType() != ProxiBlue_GiftPromo_Model_Promo_Rule::COUPON_TYPE_NO_COUPON) {
            $quote = $address->getQuote();
            if (!is_object($quote) || !$quote instanceof Mage_Sales_Model_Quote) {
                return false;
            }
            $couponCode = $address->getQuote()->getCouponCode();
            /**
             * Compatibility with Magegiant_GiantAffiliate 0.1.1
             * Allows same coupon to be used between affiliates and giftpromo
             */
            if (is_null($couponCode)
                && $affiliateCouponCode = Mage::getSingleton('checkout/session')->getData('affiliate_coupon_code')
            ) {
                $couponCode = $affiliateCouponCode;
            }

            if (strlen($couponCode)) {
                /**
                 * Compatibility with Amasty Coupon Code 1.2.1
                 */
                $couponCodes = explode(',', $couponCode);
                $rule->setIsValid(false);
                $rule->setIsValidForAddress($address, false);
                foreach ($couponCodes as $couponCode) {

                    $coupon = Mage::getModel('giftpromo/promo_coupon');
                    $coupon->load(
                        $couponCode,
                        'code'
                    );
                    if ($coupon->getId() && $coupon->getRuleId() == $rule->getId() && $rule->validate($quote)) {
                        // check entire usage limit
                        if ($coupon->getUsageLimit() > 0) {
                            $rule->setIsValid(true);
                            if ($coupon->getTimesUsed() < $coupon->getUsageLimit()) {
                                $rule->setIsValid(true);
                            } else {
                                $rule->setIsValid(false);
                                $this->_getHelper()->insertFactoryErrorMessage(
                                    Mage::helper('giftpromo')->__(
                                        "Sorry, coupon '%s' has reached its overall usage limits.",
                                        $coupon->getCode()
                                    )
                                );

                                Mage::dispatchEvent(
                                    'gift_product_rule_max_coupon_usage_reached',
                                    array(
                                        'rule' => $rule,
                                        'coupon' => $coupon
                                    )
                                );
                            }
                        } else {
                            $rule->setIsValid(true);
                        }

                        // check per customer usage limit
                        $customerId = $address->getQuote()->getCustomerId();
                        if ($customerId && $coupon->getUsagePerCustomer() > 0) {
                            $couponUsage = new Varien_Object();
                            Mage::getResourceModel('giftpromo/promo_coupon_usage')->loadByCustomerCoupon(
                                $couponUsage,
                                $customerId,
                                $coupon->getId()
                            );
                            if (!$couponUsage->getCouponId()) {
                                // never used, so is valid
                                $rule->setIsValid(true);
                            } elseif ($couponUsage->getCouponId()
                                && $couponUsage->getTimesUsed() < $coupon->getUsagePerCustomer()
                            ) {
                                $rule->setIsValid(true);
                            } else {
                                $rule->setIsValid(false);
                                $this->_getHelper()->insertFactoryErrorMessage(
                                    Mage::helper('giftpromo')->__(
                                        "Sorry, coupon '%s' has reached its customer usage limits.",
                                        $coupon->getCode()
                                    )
                                );

                                Mage::dispatchEvent(
                                    'gift_product_rule_max_coupon_usage_reached',
                                    array(
                                        'rule' => $rule,
                                        'coupon' => $coupon
                                    )
                                );
                            }
                        }
                        if ($rule->getIsValid()) {
                            $address->getQuote()->setCouponCode($couponCode);
                            $address->setCouponCode($couponCode);
                        }

                    }
                }
            } else {
                // requires a coupon, but none entered, thus fails
                $rule->setIsValid(false);
                // reset any current seleted gifts for this rule
                $currentSelectedGifts = $this->_getHelper()->getCurrentSelectedGifts();
                foreach ($currentSelectedGifts as $currentSelectedGiftsKeys => $currentSelectedGiftsValue) {
                    $keyParts = explode(
                        "_",
                        $currentSelectedGiftsValue
                    );
                    $rulePart = array_shift($keyParts);
                    if ($rule->getId() == $rulePart) {
                        unset($currentSelectedGifts[$currentSelectedGiftsKeys]);
                        $this->_getHelper()->setCurrentSelectedGifts($currentSelectedGifts);
                    }
                }

                $this->_getHelper()->removeRuleCartItems($rule, $address->getQuote());

                return false;
            }

        }

        /**
         * check per rule usage limit
         */
        if ($rule->getUsesPerCustomer() > 0) {
            $customerId = $address->getQuote()->getCustomerId();
            if (!$customerId) {
                // not yet attached to quote, but maybe is logged in?
                $loggedInCustomer = Mage::helper('customer')->getCustomer();
                if (is_object($loggedInCustomer)) {
                    $customerId = $loggedInCustomer->getId();
                }
            }
            if ($customerId) {
                $ruleCustomer = Mage::getModel('giftpromo/promo_rule_customer');
                $ruleCustomer->loadByCustomerRule($customerId, $rule->getId());
                if ($ruleCustomer->getId()) {
                    if ($ruleCustomer->getTimesUsed() >= $rule->getUsesPerCustomer()) {
                        $rule->setIsValidForAddress($address, false);
                        $rule->setIsValid(false);

                        Mage::dispatchEvent(
                            'gift_product_rule_max_customer_usage_reached',
                            array(
                                'rule' => $rule,
                                'times_used' => $ruleCustomer->getTimesUsed(),
                                'customer_id' => $customerId
                            )
                        );

                        return false;
                    }
                }
            }
        }
        if ($rule->getUsageLimit() > 0) {
            if ($rule->getTimesUsed() >= $rule->getUsageLimit()) {
                $rule->setIsValidForAddress($address, false);
                $rule->setIsValid(false);

                Mage::dispatchEvent(
                    'gift_product_rule_usage_limit_reached',
                    array(
                        'rule' => $rule,
                        'times_used' => $rule->getTimesUsed()
                    )
                );

                return false;
            }
        }

        // check how many times a gift has been applied.
        $rule->setIsValidForAddress($address, $rule->getIsValid());
        return $rule->getIsValid();
    }

    /**
     * Get the helper class and cache teh object
     *
     * @return object
     */
    private function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::Helper('giftpromo');
        }

        return $this->_helper;
    }

    /**
     * Get rules collection for current object state
     *
     * @return Mage_SalesRule_Model_Mysql4_Rule_Collection
     */
    protected function _getRules()
    {
        $key = $this->getWebsiteId() . '_' . $this->getCustomerGroupId() . '_' . $this->getCouponCode();

        return $this->_rules[$key];
    }

    /**
     * Get address object which can be used for discount calculation
     *
     * @param   Mage_Sales_Model_Quote_Item_Abstract $item
     *
     * @return  Mage_Sales_Model_Quote_Address
     */
    protected function _getAddress(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        if ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
            $address = $item->getAddress();
        } elseif ($item->getQuote()->getItemVirtualQty() > 0) {
            $address = $item->getQuote()->getBillingAddress();
        } else {
            $address = $item->getQuote()->getShippingAddress();
        }

        return $address;
    }

}
