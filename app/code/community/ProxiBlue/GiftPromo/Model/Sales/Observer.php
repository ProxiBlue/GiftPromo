<?php

/**
 * Events observers to deal with frontent sales adjustments
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Sales_Observer
    extends ProxiBlue_GiftPromo_Model_Observer
{

    /**
     * Event to update sales collection.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return ProxiBlue_GiftPromo_Model_Checkout_Observer
     */
    public function sales_order_item_collection_load_before(Varien_Event_Observer $observer)
    {
        try {
            if (Mage::getStoreConfig('giftpromo/orders/last_ordered_enabled')) {
                $event = $observer->getEvent();
                $orderItemCollection = $event->getOrderItemCollection();
                $select = $orderItemCollection->getSelect();
                $order = $select->getPart('order');
                if (is_array($order) && count($order) > 0) {
                    foreach ($order as $key => $expression) {
                        // TODO: find a better way !
                        // if there is an order directive of RAND(),
                        // then this is a call for the sidebar
                        if ($expression == 'RAND()') {
                            //adjust the where to skip gift based products
                            $select->where("product_type NOT LIKE 'gift-%'");
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // log any issues, but allow system to continue.
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Event to adjust gift in cart to a gift item type
     *
     * @param Varien_Event_Observer $observer
     *
     * @return ProxiBlue_GiftPromo_Model_Checkout_Observer
     */
    public function sales_quote_item_set_product(Varien_Event_Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $quoteItem = $event->getQuoteItem();
            if(!$event->getProduct() instanceof Mage_Catalog_Model_Product) {
                // ensure the product object inside the QuoteItem is a full object, containing all product data
                // else some rules may not validate correctly
                $product = Mage::getModel('catalog/product')->load($quoteItem->getProduct()->getId());
                $quoteItem->setData('product', $product); // cannot use setProduct as it will cause a loop.
            } else {
                $quoteItem->setData('product', $event->getProduct()); // cannot use setProduct as it will cause a loop.
            }
            if ($buyRequest = $this->_getHelper()->isAddedAsGift($quoteItem)) {
                $quoteItem->setProductType($this->_getHelper()->getGiftProductType($quoteItem->getProductType()));
                $quoteItem->getProduct()->setTypeId(
                    $this->_getHelper()->getGiftProductType($quoteItem->getProduct()->getTypeId())
                );
                if ($this->_getHelper()->wasAddedByRule($quoteItem)) {
                    if ($buyRequest instanceof Varien_Object) {
                        if ($quoteItem->getId()) {
                            $appliedGiftRuleIds = $this->_getHelper()->getAppliedRuleIds(
                                $quoteItem->getAppliedGiftRuleIds()
                            );
                            $appliedGiftRuleIds[$quoteItem->getId()] = $buyRequest->getAddedByRule();
                            $quoteItem->setAppliedGiftRuleIds(json_encode($appliedGiftRuleIds));
                            $appliedQuoteGiftRuleIds = $this->_getHelper()->getAppliedRuleIds(
                                $quoteItem->getQuote()->getAppliedGiftRuleIds()
                            );
                            $appliedQuoteGiftRuleIds[$quoteItem->getId()] = $buyRequest->getAddedByRule();
                            $quoteItem->getQuote()->setAppliedGiftRuleIds(json_encode($appliedQuoteGiftRuleIds));
                        }
                    }
                }

                return $this;
            }
        } catch (Exception $e) {
            // log any issues, but allow system to continue.
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }
        // do not return anything, else it mucks up things later.
    }

    /**
     * Event to remove any gift associated to a product, from the cart, when the parent is removed
     *
     * @param Varien_Event_Observer $observer
     *
     * @return ProxiBlue_GiftPromo_Model_Observer
     */
    public function sales_quote_remove_item(Varien_Event_Observer $observer)
    {
        try {
            $quoteItem = $observer->getQuoteItem();
            if ($buyRequest = $this->_getHelper()->isAddedAsGift($quoteItem)) {

                    if ($buyRequest instanceof Varien_Object) {
                        $currentSelectedGifts = $this->_getHelper()->getCurrentSelectedGifts();
                        if (array_search($buyRequest->getSelectedGiftItemKey(), $currentSelectedGifts) !== false) {
                            $selectedKey = array_search($buyRequest->getSelectedGiftItemKey(), $currentSelectedGifts);
                            mage::helper('giftpromo')->debug(
                                "UNSET SELECTED GIFT: "
                                . $buyRequest->getSelectedGiftItemKey(),
                                1
                            );
                            unset($currentSelectedGifts[$selectedKey]);
                            $this->_getHelper()->setCurrentSelectedGifts(
                                $currentSelectedGifts
                            );
                        }
                    }


                $quoteItem->getQuote()->save();
                $cart = Mage::getSingleton('checkout/cart');
                $cart->setQuote($quoteItem->getQuote());

                return $this;
            }
            $quote = $quoteItem->getQuote();
            foreach (
                $quote->setData(
                    'trigger_recollect',
                    0
                )->getAllItems() as $item
            ) {
                $buyRequest
                    = $this->_getHelper()->isAddedAsGift($item);
                if (($this->_getHelper()->getParentQuoteItemOfGift(
                        $item,
                        true
                    ) == $quoteItem->getId())
                ) {
                    $quote->removeItem($item->getId());
                    // clear session selected data
                    $this->_getHelper()->resetCurrentSelectedGiftsForParent($quoteItem->getId());
                    //clear the AppliedGiftRuleId for this product.
                    $appliedGiftRuleIds = $appliedQuoteGiftRuleIds = $this->_getHelper()
                        ->getAppliedRuleIds($quote->getAppliedGiftRuleIds());
                    unset($appliedGiftRuleIds[$item->getId()]);
                    $appliedGiftRuleIds = json_encode($appliedGiftRuleIds);
                    $quote->setAppliedGiftRuleIds($appliedGiftRuleIds);
                } elseif (is_object($buyRequest) && $buyRequest->getRemoveOnDelete()) {
                    $quote->removeItem($item->getId());
                    // clear session selected data
                    $this->_getHelper()->resetCurrentSelectedGiftsForParent($quoteItem->getId());
                    //clear the AppliedGiftRuleId for this product.
                    $appliedGiftRuleIds = $appliedQuoteGiftRuleIds = $this->_getHelper()
                        ->getAppliedRuleIds($quote->getAppliedGiftRuleIds());
                    unset($appliedGiftRuleIds[$item->getId()]);
                    $appliedGiftRuleIds = json_encode($appliedGiftRuleIds);
                    $quote->setAppliedGiftRuleIds($appliedGiftRuleIds);
                    // and now re-insert this item, so it can be re-evaluated
                    $cart = $this->_getHelper()->getCartSession();
                    // only action this if the rule is of type
                    $ruleModel = mage::getModel('giftpromo/promo_rule')->load($buyRequest->getAddedByRule());
                    if ($ruleModel->getFlag('replace_gift_on_remove')) {
                        try {
                            $replaceProduct = mage::getModel('catalog/product')->load($item->getProduct()->getid());
                            $cart->addProduct(
                                $replaceProduct,
                                array(
                                    'qty' => $item->getQty()
                                )
                            );
                        } catch (Exception $e) {
                            // log any issues, but allow system to continue.
                            Mage::logException($e);
                            if (Mage::getIsDeveloperMode()) {
                                die($e->getMessage());
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // log any issues, but allow system to continue.
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }
        $this->_getHelper()->resetCurrentSelectedGiftsForParent($quoteItem->getId());

        return $this;
    }

    /**
     * Append gift product additional data to order item options
     *
     * @param Varien_Event_Observer $observer
     *
     * @return ProxiBlue_GiftPromo_Model_Observer
     */
    public function sales_convert_quote_item_to_order_item(Varien_Event_Observer $observer)
    {
        try {
            $orderItem = $observer->getEvent()->getOrderItem();
            $item = $observer->getEvent()->getItem();
            if ($this->_helper->testGiftTypeCode($orderItem->getProductType()) && !$item->getParentItem()) {
                $quoteItem = $observer->getEvent()->getItem();
                try {
                    $options = unserialize($orderItem->getData('product_options'));
                } catch (Exception $e) {
                    $options = array();
                }
                $allOptions = $quoteItem->getOptions();
                foreach ($allOptions as $optionData) {
                    $unserialised = @unserialize($optionData->getValue());
                    if ($unserialised == false) {
                        $options = array_merge(
                            $options,
                            array($optionData->getCode() => $optionData->getValue())
                        );
                    } else {
                        $options = array_merge(
                            $options,
                            array($optionData->getCode() => $unserialised)
                        );
                    }
                }
                $orderItem->setProductOptions($options);
                Mage::getSingleton('checkout/session')->unsCurrentSelectedGifts();
            } else {
                if ($item->getParentItem()) {
                    $quoteItem = $observer->getEvent()->getItem();
                    $orderItem->setParentItemId($item->getParentItem()->getId());
                }
            }
        } catch (Exception $e) {
            // log any issues, but allow system to continue.
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Event to update the composite item parentItemId, which is not recorded due to the same item order in order object
     *
     * @param Varien_Event_Observer $observer
     *
     * @return ProxiBlue_GiftPromo_Model_Checkout_Observer
     */
    public function sales_model_service_quote_submit_after(Varien_Event_Observer $observer)
    {
        try {
            $order = $observer->getOrder();
            foreach (
                $order->setData(
                    'trigger_recollect',
                    0
                )->getAllItems() as $item
            ) {
                if ($item->getParentItem() && $this->_getHelper()->testGiftTypeCode($item->getProductType())) {
                    $item->setParentItemId($item->getParentItem()->getId());
                    $item->save();
                }
            }
        } catch (Exception $e) {
            // log any issues, but allow system to continue.
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Adjust rules and coupons after order was placed.
     *
     * @param type $observer
     *
     * @return \ProxiBlue_GiftPromo_Model_Sales_Observer
     */
    public function sales_order_place_after($observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            return $this;
        }

        // lookup rule ids
        $ruleIds = $this->_getHelper()->getAppliedRuleIds($order->getAppliedGiftRuleIds());

        $ruleIds = array_unique($ruleIds);

        $ruleCustomer = null;
        $customerId = $order->getCustomerId();

        // use each rule (and apply to customer, if applicable)
        foreach ($ruleIds as $ruleId) {
            if (!$ruleId) {
                continue;
            }
            $rule = Mage::getModel('giftpromo/promo_rule');
            $rule->load($ruleId);
            if ($rule->getId()) {
                $rule->setTimesUsed($rule->getTimesUsed() + 1);
                $rule->save();

                if ($customerId) {
                    $ruleCustomer = Mage::getModel('giftpromo/promo_rule_customer');
                    $ruleCustomer->loadByCustomerRule(
                        $customerId,
                        $ruleId
                    );

                    if ($ruleCustomer->getId()) {
                        $ruleCustomer->setTimesUsed($ruleCustomer->getTimesUsed() + 1);
                    } else {
                        $ruleCustomer
                            ->setCustomerId($customerId)
                            ->setRuleId($ruleId)
                            ->setTimesUsed(1);
                    }
                    $ruleCustomer->save();
                }
            }
        }

        $coupon = Mage::getModel('giftpromo/promo_coupon');
        $coupon->load(
            $order->getCouponCode(),
            'code'
        );
        if ($coupon->getId()) {
            $coupon->setTimesUsed($coupon->getTimesUsed() + 1);
            $coupon->save();
            if ($customerId) {
                $couponUsage = Mage::getResourceModel('giftpromo/promo_coupon_usage');
                $couponUsage->updateCustomerCouponTimesUsed(
                    $customerId,
                    $coupon->getId()
                );
            }
        }

        // clear out any leftover cache entries for this sale
        $useCache = Mage::app()->useCache('giftpromo_product_valid');
        if ($useCache) {
            $quote = $order->getQuote();
            $cacheID = ($quote->getId()) ? $quote->getId() : 0;
            $cacheModel = Mage::app()->getCache();
            // clear out any old cache for this.
            mage::helper('giftpromo')->debug(
                "Clearing cache for GIFTPROMO_PRODUCT_VALID_{$cacheID}", 5
            );
            $cacheModel->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array('GIFTPROMO_PRODUCT_VALID_' . $cacheID)
            );
        }

        // reset giftpromo session data
        Mage::getSingleton('giftpromo/session')->clear();
    }

    /**
     * Remove any gift products from a merged in quote
     *
     * @param type $observer
     *
     * @return \ProxiBlue_GiftPromo_Model_Sales_Observer
     */
    public function load_customer_quote_before($observer)
    {
        $removed = false;
        $quote = Mage::getModel('sales/quote')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomerId());
        if (is_object($quote) && $quote->getReservedOrderId() == false) {
            foreach (
                $quote->setData(
                    'trigger_recollect',
                    0
                )->getAllItems() as $quoteItem
            ) {
                if ($this->_getHelper()->isAddedAsGift($quoteItem)) {
                    $quote->removeItem($quoteItem->getId());
                    $quoteItem->delete();

                    $removed = true;
                }
            }
        }
        if ($removed) {
            $quote->save();
        }

        return $this;
    }

    /**
     * Add coupon's rule name to order data
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Mage_SalesRule_Model_Observer
     */
    public function addRuleNameToOrder($observer)
    {
        $order = $observer->getOrder();
        $couponCode = $order->getCouponCode();

        if (empty($couponCode)) {
            return $this;
        }

        /**
         * @var Mage_SalesRule_Model_Coupon $couponModel
         */
        $couponModel = Mage::getModel('giftpromo/promo_coupon');
        $couponModel->loadByCode($couponCode);

        $ruleId = $couponModel->getRuleId();

        if (empty($ruleId)) {
            return $this;
        }

        /**
         * @var Mage_SalesRule_Model_Rule $ruleModel
         */
        $ruleModel = Mage::getModel('giftpromo/promo_rule');
        $ruleModel->load($ruleId);

        $order->setCouponRuleName($ruleModel->getRuleName() . ' (Gift Promotions) ');

        return $this;
    }

    /**
     * When merging quotes from a login, magento core takes the old quote (gotten from the login)
     * and makes it the current quote object.
     * It clones / copies the items from the old active quote object to this 'new' object.
     * The end result is that the item ids have been changed.
     *
     * This code transposes the selected gifts session array to the new ids, thus fixing the bug where you
     * could again select gifts after login.
     *
     * @param $observer
     */
    public function sales_quote_merge_after($observer)
    {
        $quote = $observer->getQuote();
        foreach (
            $quote->setData(
                'trigger_recollect',
                0
            )->getAllItems() as $quoteItem
        ) {
            if (!$this->_getHelper()->isAddedAsGift($quoteItem)) {
                //does it exist in the selected list?
                $oldItemId = $quoteItem->getOrigData('item_id');
                if ($oldItemId) {
                    $currentSelectedGifts = mage::Helper('giftpromo')->getCurrentSelectedGifts();
                    foreach ($currentSelectedGifts as $parentGiftItemKey => $parentGiftItem) {
                        if (strpos($parentGiftItem, $oldItemId)) {
                            if (is_null($quoteItem->getId())) {
                                $quote->save();
                            }
                            $keyParts = explode('_', $parentGiftItem);
                            $keyParts[1] = $quoteItem->getId();
                            $newKeyParts = implode('_', $keyParts);
                            unset($currentSelectedGifts[$parentGiftItemKey]);
                            $currentSelectedGifts[] = $newKeyParts;
                            Mage::helper('giftpromo')->setCurrentSelectedGifts($currentSelectedGifts);
                            $quote->save();
                            // now also update the gift product accordingly
                            // cannot simply use quote->getProductById, as there could be non gift
                            // item in cart of the same product
                            $giftProducts = mage::helper('giftpromo')->getAllGiftBasedCartItems($quote);
                            foreach ($giftProducts as $giftItem) {
                                if ($giftItem->getProductId() == $quoteItem->getProductId()) {
                                    $infoBuyRequest = $giftItem->getOptionByCode('info_buyRequest');
                                    $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
                                    $buyRequest->setSelectedGiftItemKey($newKeyParts);
                                    $buyRequest->setParentQuoteItemId($quoteItem->getId());
                                    $infoBuyRequest->setValue(serialize($buyRequest->getData()));
                                    $infoBuyRequest->save();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

}
