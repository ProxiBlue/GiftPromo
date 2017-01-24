<?php

/**
 * Helper routines to manage gifts and products
 *
 * @category ProxiBlue
 * @package  ProxiBlue_GiftPromo
 * @author   Lucas van Staden <support@proxiblue.com.au>
 * @license  Copyright ProxiBlue - See EULA on www.proxiblue.com.au
 * @link     www.proxiblue.com.au
 */
class ProxiBlue_GiftPromo_Helper_Data
    extends Mage_Catalog_Helper_Data
{

    /**
     * Get the parent product of given item
     *
     * @param ProxiBlue_GiftPromo_Model_Sales_Quote_Item $item
     *
     * @return \Mage_Catalog_Model_Product|\Varien_Object
     */
    public function getParentOfGift($item, $returnParentProductId = false)
    {
        if ($buyRequest = $this->isAddedAsGift($item)) {
            if (is_object($buyRequest)) {
                if ($returnParentProductId) {
                    return $buyRequest->getParentProductId();
                }
                $parentProduct = Mage::getModel('catalog/product')->load($buyRequest->getParentProductId());
                if ($parentProduct->getId()) {
                    return $parentProduct;
                }
            }
        }

        return false;
    }

    /**
     * Check if cart item was added as a gift item
     *
     * @param object Mage_Sales_Model_Quote_Item|Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Item|Mage_Catalog_Model_Product $item
     *
     * @return boolean
     */
    public function isAddedAsGift($item)
    {
        if ($item instanceof Mage_Sales_Model_Quote_Item) {
            $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
            if (!is_object($infoBuyRequest)) {
                return false;
            }
            $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
        } else {
            if ($item instanceof Mage_Sales_Model_Order_Item) {
                $buyRequest = new Varien_Object($item->getProductOptions());
            } else {
                if ($item instanceof Mage_Catalog_Model_Product) {
                    $infoBuyRequest = $item->getCustomOption('info_buyRequest');
                    if (!is_object($infoBuyRequest)) {
                        return false;
                    }
                    $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
                } else {
                    if (!is_object($item)) {
                        return false;
                    }
                    $infoBuyRequest = $item->getCustomOption('info_buyRequest');
                    if (!is_object($infoBuyRequest)) {
                        return false;
                    }
                    $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
                }
            }
        }
        if (is_object($buyRequest)) {
            if ($buyRequest->getAddedByRule()) {
                return $buyRequest;
            }
        }

        return false;
    }

    public function getTotalGiftQtyInCartByProduct($giftProduct)
    {
        $qty = 0;
        $cart = $this->getCartSession();
        foreach (
            $cart->getQuote()->setData(
                'trigger_recollect',
                0
            )->getAllItems() as $findGiftItem
        ) {
            if (!$this->testGiftTypeCode($findGiftItem->getProductType())) {
                continue;
            }
            if ($findGiftItem->getProductId() == $giftProduct->getId()) {
                $qty += $findGiftItem->getQty();
            }
        }
        Mage::dispatchEvent(
            'gift_product_total_qty_in_cart_by_product',
            array(
                'gift_product' => $giftProduct,
                'qty' => $qty,
            )
        );

        return $qty;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    public function getCartSession()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $object = Mage::getSingleton('adminhtml/sales_order_create');

            return $object;
        } else {
            return Mage::getModel('checkout/cart');
        }
    }

    /**
     * Check if the given product is of a gifted product type
     *
     * @param string $productType
     *
     * @return bool;
     */
    public function testGiftTypeCode($productType)
    {
        if (substr($productType, 0, 5) != ProxiBlue_GiftPromo_Model_Product_Type::TYPE_GIFT) {
            return false;
        }

        return true;
    }

    /**
     * Get the given gift item current qty
     *
     * @param ProxiBlue_GiftPromo_Model_Sales_Quote_Item $quoteGiftItem
     * @param bool $excludeThisGift
     *
     * @return int
     */
    public function getTotalGiftQtyInCart(
        ProxiBlue_GiftPromo_Model_Sales_Quote_Item $quoteGiftItem,
        $excludeThisGift = false
    ) {
        $qty = 0;
        $allVisibleItems = $this->getAllVisibleItems($quoteGiftItem);
        foreach ($allVisibleItems as $findGiftItem) {
            if (!$this->testGiftTypeCode($findGiftItem->getProductType())) {
                continue;
            }
            if ($findGiftItem->getProductId() == $quoteGiftItem->getProductId()) {
                if ($excludeThisGift == true) {
                    $findGiftItemParentId = $this->getParentQuoteItemOfGift(
                        $findGiftItem,
                        true
                    );
                    $quoteGiftItemParentId = $this->getParentQuoteItemOfGift(
                        $quoteGiftItem,
                        true
                    );
                    if ($findGiftItemParentId == $quoteGiftItemParentId) {
                        continue;
                    }
                }
                $qty += $findGiftItem->getQty();
            }
        }
        Mage::dispatchEvent(
            'gift_product_total_qty_in_cart',
            array(
                'gift_quote_item' => $quoteGiftItem,
                'qty' => $qty,
                'exclude_this_gift' => $excludeThisGift
            )
        );

        return $qty;
    }

    /**
     * @param $object
     *
     * @return array
     */
    public function getAllVisibleItems($object)
    {
        $result = $object->setData('trigger_recollect', 0)->getAllVisibleItems();
        if (!is_array($result)) {
            return array();
        }

        return $result;
    }

    /**
     * Get the parent quote item of given item
     *
     * @param ProxiBlue_GiftPromo_Model_Sales_Quote_Item $item
     *
     * @return \Mage_Catalog_Model_Product|\Varien_Object
     */
    public function getParentQuoteItemOfGift($item, $returnParentId = false)
    {
        if ($buyRequest = $this->isAddedAsGift($item)) {
            if ($returnParentId) {
                return $buyRequest->getParentQuoteItemId();
            }
            if ($buyRequest->getParentQuoteItemId()) {
                $parentQuoteItem = $item->getQuote()->getItemById($buyRequest->getParentQuoteItemId());
                if ($parentQuoteItem instanceof Mage_Sales_Model_Quote_Item) {
                    return $parentQuoteItem;
                }
            }
        }

        return false;
    }

    /**
     * Calculate qty rate of gift items
     *
     * @param $quoteGiftItems
     * @param $quote
     *
     * @return float|int
     */
    public function calculateQtyRate(array $giftItems, $quote)
    {
        if ($tempRatioBlock = Mage::getSingleton('checkout/session')->getSkipRatioCheck()) {
            if (time() - (int)$tempRatioBlock < 30) {
                return 0;
            }
        }
        // use to match child items to parent item qty's
        $qtyCache = array();
        $newQty = 1;
        // iterate the cart (non gift) items.
        // if it has a rule applied, then check if the gift associated has a ratio set.
        foreach ($giftItems as $giftItem) {
            foreach ($giftItem as $item) {
                if ($buyRequest = $this->isAddedAsGift($item)) {
                    if ($buyRequest->getSkipQtyRatio()) {
                        return $item->getQty();
                    }
                    if ($parentItemId = $item->getParentItemId()) {
                        if (array_key_exists($parentItemId, $qtyCache)) {
                            $item->setQty($qtyCache[$parentItemId]);
                            if ($item instanceof Mage_Sales_Model_Quote_Item) {
                                $item->save();
                            }
                            continue;
                        }
                    }
                    $giftProduct = $item->getProduct();
                    if (is_null($giftProduct->getGiftedPrice())) {
                        $giftProduct = mage::getModel('giftpromo/product')->cloneProduct($giftProduct);
                    }
                    /**
                     * Override the expected qty from the multiple of rate calculated
                     *
                     * @see ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Subtotal::validateAttribute
                     */
                    if ($override = mage::registry('qty_override')) {
                        $giftProduct->setGiftedRateGiftRate($override);
                    }
                    /**
                     * Override the rate qty. fixes multiple of in subselection when used in qty attribute
                     *
                     * @see ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Subselect::validateAttribute
                     */
                    if ($ratio_override = mage::registry('ratio_override')) {
                        $giftProduct->setGiftedRateProductQty($ratio_override);
                    }
                    $rateQty = $giftProduct->getGiftedRateProductQty();
                    $rateQty = (empty($rateQty))
                        ? 1
                        : $giftProduct->getGiftedRateProductQty();
                    $giftQty = $giftProduct->getGiftedRateGiftRate();
                    if($giftQty == 'x') {
                        continue; // bypass qty rates. Handy when using Cheapest as rules.
                    }
                    $giftQty = (empty($giftQty))
                        ? 1
                        : $giftProduct->getGiftedRateGiftRate();

                    $maxQty = 0;
                    if ($giftProduct->getGiftedQtyMax() > 0) {
                        $maxQty = $giftProduct->getGiftedQtyMax();
                    }

                    // have we reached a max qty?
                    if ($maxQty != 0 && $item->getQty() >= $maxQty) {
                        $item->setQty($maxQty);
                        if ($item instanceof Mage_Sales_Model_Quote_Item) {
                            $item->save();
                        }
                        $qtyCache[$item->getId()] = $item->getQty();
                        continue;
                    }

                    $ruleObject = Mage::getModel('giftpromo/promo_rule')->load($buyRequest->getAddedByRule());
                    if ($ruleObject->getId() && $ruleObject->getGiftAddedProduct()
                        && $ruleObject->getGiftAddedProductMax() > 0
                        && $item->getQty() >= $ruleObject->getGiftAddedProductMax()
                    ) {
                        $item->setQty($ruleObject->getGiftAddedProductMax());
                        if ($item instanceof Mage_Sales_Model_Quote_Item) {
                            $item->save();
                        }
                        $qtyCache[$item->getId()] = $item->getQty();
                        continue;
                    }

                    $parentQuoteItem = $this->getParentQuoteItemOfGift($item);

                    $limitTestQty = 1;
                    if (is_object($parentQuoteItem)) {
                        $limitTestQty = (is_object($parentQuoteItem))
                            ? $parentQuoteItem->getQty()
                            : 1;
                    }
                    if ($override = mage::registry('ratio_override')) {
                        $limitTestQty = $override;
                    }

                    // calculate the rate
                    $qtyTest = $limitTestQty / $rateQty;
                    $qtyTest = floor($qtyTest);
                    $newQty = $qtyTest * $giftQty;
                    if ($newQty < 0) {
                        $newQty = 0;
                    }
                    if ($maxQty > 0 && $newQty >= $maxQty) {
                        $newQty = $maxQty;
                    }
                    $oldQty = $item->getQty();
                    if ($newQty == 0) {
                        if ($item->getId()) {
                            $qtyCache[$item->getId()] = $newQty;
                            if (method_exists(
                                $quote,
                                'removeItem'
                            )) {
                                $quote->removeItem($item->getId());
                            }
                        }
                    } else {
                        if ($newQty != $oldQty && $giftProduct->getGiftedPrice() == 0) {
                            $item->setQty($newQty);
                            if ($item instanceof Mage_Sales_Model_Quote_Item && method_exists(
                                    $item,
                                    'save' && !$item->getSkipSave()

                                )
                            ) {
                                $qtyCache[$item->getId()] = $item->getQty();
                                if ( $item instanceof Mage_Sales_Model_Quote_Item ) {
                                    $item->save();
                                }
                            }
                        }
                    }
                    if ($giftProduct->getGiftedPrice() > 0) {
                        // if item is not free, the adding qty is limited to 1
                        // this is not a free item, so we cannot just increase the qty
                        // we must insert a new line item for the additional qty
                        // allowing the customer to remove it
                        $buyRequest = $this->isAddedAsGift($item);
                        // find all products of this id, and tally up the qty
                        $totalQtyInCart = 0;
                        foreach ($giftItem as $itemQtyTest) {
                            if ($itemQtyTest->getProductId() == $buyRequest->getProduct()) {
                                $totalQtyInCart += $itemQtyTest->getQty();
                            }
                        }
                        if ($totalQtyInCart > $newQty) {
                            // the parent qty has decreased, so we need to remove one of the gift items
                            // simply delete the one that is current in object $itemQtyTest,
                            // as it will be the last one added
                            $itemBuyRequest = $this->isAddedAsGift($itemQtyTest);
                            if (is_object($itemQtyTest) && $itemBuyRequest->getMultipleOfUid() == false) {
                                if (method_exists(
                                    $quote,
                                    'removeItem'
                                )) {
                                    $quote->removeItem($itemQtyTest->getId());
                                }
                            }
                        } elseif ($totalQtyInCart != $newQty) {
                            $currentSelectedGifts = mage::helper('giftpromo')
                                ->getCurrentSelectedGifts();
                            if ($selectedKey = array_search(
                                    $buyRequest->getSelectedGiftItemKey(), $currentSelectedGifts
                                )
                                !== false
                            ) {
                                unset($currentSelectedGifts[$selectedKey]);
                            }
                            mage::helper('giftpromo')->setCurrentSelectedGifts(
                                $currentSelectedGifts
                            );
                        }
                    }
                }
            }
        }

        return $newQty;

    }

    /**
     * Take an array of gift items and add them to the cart
     *
     *
     * @param array $giftProducts
     * @param       $parentItem
     * @param array $params
     */
    public function addGiftItems(array $giftProducts, $parentItem, $params = array())
    {
        Mage::dispatchEvent(
            'gift_product_add_to_cart_before',
            array(
                'gift_products' => $giftProducts,
                'parent' => $parentItem,
                'params' => $params
            )
        );
        $quote = false;
        foreach ($giftProducts as $giftProduct) {
            // block against badly saved gift item.
            if ($giftProduct->getId()) {
                // injected parent product
                if ($giftProduct->getSuperAttribute()) {
                    $params['super_attribute'] = $giftProduct->getSuperAttribute();
                }
                // injected bundles
                if ($giftProduct->getBundleOption()) {
                    $params['bundle_option'] = $giftProduct->getBundleOption();
                    $params['bundle_option_qty'] = $giftProduct->getBundleOptionQty();
                }
                if (array_key_exists('multiple_of', $params)) {
                    $multiplesOfGifts = $this->getMultipleOfGifts();
                    $multiplesOfGifts = array($giftProduct->getId() => $params['multiple_of']) + $multiplesOfGifts;
                    $this->setMultipleOfGifts($multiplesOfGifts);
                }
                // unset parent item, and set to remove on delete
                // this can happen in some free items rules configurations
                if ($giftProduct->getSkipTriggerItem()) {
                    unset($params['parent_quote_item_id']);
                    unset($params['parent_product_id']);
                    $params['remove_on_delete'] = true;
                    $params['skip_qty_ratio'] = true;
                }
                if ($giftProduct->getSkipTriggerItem() == false) {
                    unset($params['remove_on_delete']);
                    unset($params['skip_qty_ratio']);
                }
                if ($giftProduct->getIsInjectedGift()) {
                    $params['is_injected_gift'] = $giftProduct->getIsInjectedGift();
                }
                if (is_object($giftProduct->getGiftParentItem())) {
                    $params['parent_quote_item_id'] = $giftProduct->getGiftParentItem()->getId();
                    $params['parent_product_id'] = $giftProduct->getGiftParentItem()->getProductId();
                }
                if ($giftProduct->getIsSalable()) {
                    $quote = $this->addGiftToCart(
                        $giftProduct,
                        $parentItem,
                        $params
                    );
                } else {
                    // notify about out of stock
                    $messageFactory = Mage::getSingleton('core/message');
                    $message = $messageFactory->error(
                        Mage::helper('giftpromo')->__(
                            "Sorry, gift '%s' is currently out of stock.",
                            htmlentities($giftProduct->getName())
                        )
                    );
                    if (Mage::app()->getStore()->isAdmin()) {
                        if (!$this->isPre16()) {
                            Mage::getSingleton('adminhtml/session_quote')->addUniqueMessages($message);
                        } else {
                            Mage::getSingleton('adminhtml/session_quote')->addMessage($message);
                        }
                    } else {
                        $cart = Mage::getModel('checkout/cart');
                        if (!$this->isPre16()) {
                            $cart->getCheckoutSession()->addUniqueMessages($message);
                        } else {
                            $cart->getCheckoutSession()->addMessage($message);
                        }
                    }
                }
            }
        }
        if (is_object($quote)) {
            $quote->save();
        }
        Mage::dispatchEvent(
            'gift_product_add_to_cart_after',
            array(
                'gift_products' => $giftProducts,
                'parent' => $parentItem,
                'params' => $params
            )
        );
    }

    /**
     * Get the current multipleOf gifts
     *
     * @return array
     */
    public function getMultipleOfGifts($plainRuleId = false)
    {
        $multipleOfGifts = Mage::getSingleton('checkout/session')->getMultipleOfGifts();
        if (!is_array($multipleOfGifts)) {
            $multipleOfGifts = array();
        }
        if ($plainRuleId) {
            foreach ($multipleOfGifts as $key => $selected) {
                $keyPart = explode(
                    '_',
                    $key
                );
                unset($multipleOfGifts[$key]);
                $multipleOfGifts[$keyPart[0]] = $selected;
            }
        }

        return $multipleOfGifts;
    }

    /**
     * Store the given selected gift array
     *
     * @param array $multipleOfGifts
     */

    public function setMultipleOfGifts($multipleOfGifts)
    {
        Mage::getSingleton('checkout/session')->setMultipleOfGifts($multipleOfGifts);
    }

    /**
     * Add the given product as a gift attached to parent
     *
     * @param ProxiBlue_GiftPromo_Model_Product $giftProduct
     * @param Mage_Catalog_Model_Product $parentItem
     * @param array $params
     *
     * @return object
     */
    public function addGiftToCart(ProxiBlue_GiftPromo_Model_Product $giftProduct, $parentItem, $params)
    {
        $cart = $this->getCartSession();
        // catch gift-configurables with no super _attribute set
        if ($giftProduct->getTypeId() == ProxiBlue_GiftPromo_Model_Product_Type_Gift_Configurable::TYPE_CODE
            && !array_key_exists(
                'super_attribute',
                $params
            )
        ) {
            mage::throwException($this->__('Please select gift product options'));
        }
        $giftProduct->addCustomOption(
            'additional_options',
            serialize(
                array(
                    'gifted_message' =>
                        array(
                            'label' => $giftProduct->getGiftedLabel(),
                            'value' => $giftProduct->getGiftedMessage()
                        )
                )
            )
            ,
            $giftProduct
        );
        try {
            if ($parentItem->getGiftAddNormalProduct()) {
                $giftProduct = mage::getModel('catalog/product')->load($giftProduct->getId());
                $cart->addProduct(
                    $giftProduct,
                    array_merge(
                        array(
                            'qty' => 1,
                            'gifted_price' => $giftProduct->getGiftedPrice()
                        ),
                        $params
                    )
                );
            } else {
                $giftedPrice = $giftProduct->getGiftedPrice();
                if (strpos($giftedPrice, '%') !== false) {
                    $realPrice = $giftProduct->getPrice();
                    $giftedPrice = $realPrice - (float)(str_replace('%', '', $giftedPrice) / 100 * $realPrice);
                    $giftProduct->setGiftedPrice($giftedPrice);
                }
                $giftProduct->setCalculatedFinalPrice($giftedPrice);
                $cart->addProduct(
                    $giftProduct,
                    array_merge(
                        array(
                            'qty' => $giftProduct->getQty(),
                            'added_by_rule' => $parentItem->getId(),
                            'gifted_price' => $giftProduct->getGiftedPrice()
                        ),
                        $params
                    )
                );

            }
        } catch (Exception $e) {
            $url = Mage::getSingleton('checkout/session')->getRedirectUrl(true);
            // do not redirect into oneself!
            if (strstr($url, 'giftview')) {
                Mage::throwException($e->getMessage());

                return $this;
            }
            if ($giftProduct->getTypeId() == ProxiBlue_GiftPromo_Model_Product_Type_Gift_Bundle::TYPE_CODE) {
                $giftProduct->setGiftRuleId($parentItem->getId());
                $url = $giftProduct->getProductUrl();
            }
            if ($url) {
                Mage::getSingleton('checkout/session')->setSkipRatioCheck(time());
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
                Mage::app()->getResponse()->sendResponse();
                // force an exit, to allow the redirect to happen.
                exit;
            }
        }

        $messageFactory = Mage::getSingleton('core/message');
        $message = $parentItem->getAddToCartMessage();
        if (trim($message) != '{NONE}') {
            if (empty($message)) {
                $message = $messageFactory->notice(
                    Mage::helper('giftpromo')->__(
                        "'%s' was added to your shopping cart.",
                        htmlentities($giftProduct->getName())
                    )
                );
            } else {
                $message = $messageFactory->notice(
                    str_replace(
                        '{PRODUCT_NAME}', htmlentities($giftProduct->getName()), $parentItem->getAddToCartMessage()
                    )
                );
            }

            if (Mage::app()->getStore()->isAdmin()) {
                Mage::getSingleton('adminhtml/session_quote')->addMessages($message);
            } else {
                if (!$cart->getCheckoutSession()->getSkipGiftNotice()) {
                    if (!$this->isPre16()) {
                        $cart->getCheckoutSession()->addUniqueMessages($message);
                    } else {
                        $cart->getCheckoutSession()->addMessage($message);
                    }
                }
                $cart->getCheckoutSession()->unsSkipGiftNotice();
            }

        }
        //$messageFactory = $parentItem->cartMessage($messageFactory);

        return $cart->getQuote();

    }

    /**
     * @return bool
     */
    public function isPre16()
    {
        $magentoVersion = Mage::getVersionInfo();
        if ($magentoVersion['minor'] < 6) {
            return true;
        }
        // magento professional will return true
        if (method_exists(
            'Mage',
            'getEdition'
        )) {
            $magentoEdition = Mage::getEdition();
            if ($magentoEdition == Mage::EDITION_PROFESSIONAL) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * Get all the gift products in cart that are based on rules
     *
     * @param bool $parentAsKey
     *
     * @return type
     */
    public function getRuleBasedCartItems($parentAsKey = false)
    {
        $items = array();
        $cart = $this->getCartSession();
        Mage::getSingleton('checkout/session')->setSkipTriggerCollect(
            true
        ); // prevents endless loop if session get mucked up.
        foreach (
            $cart->getQuote()->setData(
                'trigger_recollect',
                0
            )->getAllItems() as $findRuleGiftItem
        ) {
            if (!$this->testGiftTypeCode($findRuleGiftItem->getProductType())
                || !$this->wasAddedByRule(
                    $findRuleGiftItem
                )
            ) {
                continue;
            }
            $buyRequest = $this->isAddedAsGift($findRuleGiftItem);
            $ruleUsed = $buyRequest->getAddedByRule();
            if ($ruleUsed) {
                $ruleObject = Mage::getModel('giftpromo/promo_rule')->load($ruleUsed);
                $findRuleGiftItem->setRule($ruleObject);
                if ($parentAsKey && $buyRequest->getParentQuoteItemId()) {
                    $items[$buyRequest->getParentQuoteItemId()][] = $findRuleGiftItem;
                } else {
                    $items[$ruleUsed][] = $findRuleGiftItem;
                }
            }
        }
        Mage::dispatchEvent(
            'gift_product_rule_based_cart_items',
            array(
                'cart' => $cart,
                'gift_items' => $items
            )
        );

        return $items;
    }

    /**
     * @param $item
     *
     * @return bool
     */
    public function wasAddedByRule($item)
    {
        $buyRequest = $this->isAddedAsGift($item);
        if (is_object($buyRequest) && $buyRequest->getAddedByRule()) {
            return true;
        }

        return false;
    }

    /**
     * Get all the gift products in cart that are based on rules
     *
     * @param null|object $quote
     *
     * @return array
     */
    public function getAllGiftBasedCartItems($quote = null)
    {
        $items = array();
        $cart = Mage::getModel('checkout/cart');
        $quote = (!is_null($quote)) ? $quote : $cart->getQuote();
        foreach (
            $quote->setData(
                'trigger_recollect',
                0
            )->getAllItems() as $findGiftItem
        ) {
            if (!$this->testGiftTypeCode($findGiftItem->getProductType())) {
                continue;
            }
            $items[] = $findGiftItem;
        }
        Mage::dispatchEvent(
            'gift_product_all_gift_based_cart_items',
            array(
                'cart' => $cart,
                'gift_items' => $items
            )
        );

        return $items;
    }

    /**
     * Remove the given gift from the given parent as selected
     *
     */
    public function resetCurrentSelectedGift()
    {
        Mage::getSingleton('giftpromo/session')->unsetAll();
        return array();
    }

    /**
     * Store the given selected gift array
     *
     * @param array $currentSelectedGifts
     */
    public function setCurrentSelectedGifts($currentSelectedGifts)
    {
        Mage::dispatchEvent(
            'gift_product_set_selected_gifts',
            array(
                'selected' => $currentSelectedGifts,
            )
        );
        Mage::getSingleton('giftpromo/session')->setCurrentSelectedGifts($currentSelectedGifts);
    }

    /**
     * Reset all selected products for given parent product
     *
     * @param integer $parentItemId
     */
    public function resetCurrentSelectedGiftsForParent($parentItemId)
    {
        $currentSelectedGifts = $this->getCurrentSelectedGifts();
        foreach ($currentSelectedGifts as $selectedKey => $currentSelectedGiftsValue) {
            $keyParts = explode(
                "_",
                $currentSelectedGiftsValue
            );
            $rulePart = array_shift($keyParts);
            if ($parentItemId == $rulePart) {
                unset($currentSelectedGifts[$selectedKey]);
            }
        }

        $this->setCurrentSelectedGifts($currentSelectedGifts);

        return $currentSelectedGifts;
    }

    /**
     * Get the current selected gifts
     *
     * @return array
     */
    public function getCurrentSelectedGifts($plainRuleId = false)
    {
        $currentSelectedGifts = Mage::getSingleton('giftpromo/session')->getCurrentSelectedGifts();
        if ($plainRuleId) {
            foreach ($currentSelectedGifts as $key => $selected) {
                $keyPart = explode(
                    '_',
                    $selected
                );
                unset($currentSelectedGifts[$key]);
                $currentSelectedGifts[] = $keyPart[0];
            }
        }
        Mage::dispatchEvent(
            'gift_product_get_selected_gifts',
            array(
                'selected' => $currentSelectedGifts,
            )
        );

        return $currentSelectedGifts;
    }

    /**
     * @param $productType
     *
     * @return string
     */
    public function getGiftProductType($productType)
    {
        if (!$this->testGiftTypeCode($productType)) {
            return ProxiBlue_GiftPromo_Model_Product_Type::TYPE_GIFT . $productType;
        } else {
            return $productType;
        }
    }

    /**
     * @param $quote
     */
    public function checkForGiftChanges($quote)
    {
        if (mage::registry('skip_gift_check') != true
        ) { // prevent a loop by forcing only one iteration fo rules checking in a given request
            mage::register(
                'skip_gift_check',
                true,
                true
            );
            $address = $quote->getShippingAddress();
            $store = Mage::app()->getStore($quote->getStoreId());
            $validator = Mage::getSingleton('giftpromo/promo_validator');
            $validator->init(
                $store->getWebsiteId(),
                $quote->getCustomerGroupId(),
                $quote->getCouponCode()
            );
            $validator->processGiftRules($address);
        }
    }

    /**
     * @return bool
     */
    public function isPost18()
    {
        $magentoVersion = Mage::getVersionInfo();
        if ($magentoVersion['minor'] > 8) {
            return true;
        }

        return false;
    }

    /**
     * @param $value
     *
     * @return array
     */
    public function getAppliedRuleIds($value)
    {
        if (is_object($value)) {
            $value = $value->getAppliedGiftRuleIds();
        }
        if (!is_string($value)) {
            return array();
        }
        $ids = json_decode(
            $value,
            true
        );
        if (is_null($ids)) {
            return array();
        }

        return array_filter($ids);
    }

    /**
     * Common debugger helper
     *
     * @param string $message
     */
    public function debug($message, $level = 1)
    {
        if (Mage::getStoreConfig('giftpromo/debug/enabled')
            && Mage::getStoreConfig('giftpromo/debug/level') >= $level
        ) {
            if (Mage::getStoreConfig('giftpromo/debug/trace')) {
                $message .= $this->debug_backtrace_string();
            }
            mage::log(
                $message,
                Zend_Log::DEBUG,
                'giftpromo.log',
                true
            );
        }
    }

    private function debug_backtrace_string()
    {
        $stack = '';
        $i = 1;
        $trace = debug_backtrace();
        unset($trace[0]); //Remove call to this function from stack trace
        foreach ($trace as $node) {
            $stack .= "#$i " . $node['file'] . "(" . $node['line'] . "): ";
            if (isset($node['class'])) {
                $stack .= $node['class'] . "->";
            }
            $stack .= $node['function'] . "()" . PHP_EOL;
            $i++;
        }
        return $stack;
    }


    public function canLinkItem($item)
    {
        if ($item->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
            return true;
        }

        return false;

    }

    /**
     * @param $message
     */
    public function insertFactoryErrorMessage($message)
    {
        $messageFactory = Mage::getSingleton('core/message');
        $message = $messageFactory->error($message);
        if (Mage::app()->getStore()->isAdmin()) {
            if (!$this->isPre16()) {
                Mage::getSingleton('adminhtml/session_quote')->addUniqueMessages($message);
            } else {
                Mage::getSingleton('adminhtml/session_quote')->addMessage($message);
            }
        } else {
            $cart = Mage::getModel('checkout/cart');
            if (!$this->isPre16()) {
                $cart->getCheckoutSession()->addUniqueMessages($message);
            } else {
                $cart->getCheckoutSession()->addMessage($message);
            }
        }
    }

    /**
     * remove any products for the given rule object
     *
     * @param $rule
     * @param $quote
     */
    public function removeRuleCartItems($rule, $quote)
    {
        if($quote instanceof Mage_Sales_Model_Quote) {
            // remove any cart items of this rule.
            $ruleBasedCartItems = $this->getRuleBasedCartItems();
            foreach ($ruleBasedCartItems as $ruleKey => $giftItems) {
                if ($ruleKey == $rule->getId()) {
                    foreach ($giftItems as $giftItem) {
                        $quote->removeItem($giftItem->getId());
                        $this->resetCurrentSelectedGiftsForParent($ruleKey);
                    }
                }
            }
        }
    }

}
