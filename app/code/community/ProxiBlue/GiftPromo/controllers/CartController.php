<?php

/**
 * Gift Promo cart controller
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 * */
require_once(Mage::getModuleDir(
        'controllers',
        'Mage_Checkout'
    ) . DS . 'CartController.php');

/**
 * Shopping cart controller
 */
class ProxiBlue_GiftPromo_CartController
    extends Mage_Checkout_CartController
{

    /**
     * Internal holder for helper class
     *
     * @var object
     */
    private $_helper;

    /**
     * Add product to shopping cart action
     */
    public function addGiftAction()
    {
        $cart = $this->_getCart();
        try {
            Mage::getSingleton('core/session')->setSkipRules(false);
            if ($multiProducts = $this->getRequest()->getParam('multi_product')) {
                Mage::getSingleton('core/session')->setSkipRules(true);
                $ruleIds = $this->getRequest()->getParam('multi_rule_id');
                $multiSelectedGiftItemKey = $this->getRequest()->getParam('multi_selected_gift_item_key');
                $multiGiftParentItemId = $this->getRequest()->getParam('multi_gift_parent_item_id');
                foreach ($multiProducts as $key => $product) {
                    $this->getRequest()->setParam('product', $product);
                    $this->getRequest()->setParam('rule_id', $ruleIds[$key]);
                    $this->getRequest()->setParam('selected_gift_item_key', $multiSelectedGiftItemKey[$key]);
                    $this->getRequest()->setParam('gift_parent_item_id', $multiGiftParentItemId[$key]);
                    mage::helper('giftpromo')->debug('multi-add -> start: ' . $key, 1);
                    $product = $this->_initProduct();
                    if (!$product) {
                        continue;
                    }
                    $params = $this->getRequest()->getParams();
                    unset($params['multi_rule_id']);
                    unset($params['multi_selected_gift_item_key']);
                    unset($params['multi_gift_parent_item_id']);
                    $this->_addGiftAction($cart, $params, $product);
                    $cart->getQuote()->setTotalsCollectedFlag(true);
                    $cart->save();
                    mage::helper('giftpromo')->debug('multi-add -> done: ' . $key, 1);
                }
                Mage::getSingleton('core/session')->setSkipRules(false);
                Mage::getSingleton('core/session')->setSkipInactiveRuleTest(true);
                mage::helper('giftpromo')->debug('multi-add complete', 1);
            } else {
                $product = $this->_initProduct();
                if (!$product) {
                    $this->_goBack();
                    return $this;
                }
                $params = $this->getRequest()->getParams();
                $this->_addGiftAction($cart, $params, $product);
            }

            $cart->getQuote()->setTotalsCollectedFlag(true);
            $cart->save(); // changed here

            /**
             * Allow checkout add to cart to also work on ratios
             */
            if ($this->getRequest()->getParam('is_checkout_review')) {
                $quoteGiftItems = $this->_getHelper()->getRuleBasedCartItems();
                Mage::helper('giftpromo')->calculateQtyrate(
                    $quoteGiftItems,
                    $cart->getQuote()
                );
            }


            $this->_getSession()->setCartWasUpdated(true);

            if (array_key_exists('view_page', $params)) {
                $this->_goBack();
            } elseif (array_key_exists('_redirect_url', $params)) {
                $this->_redirectUrl(base64_decode($params['_redirect_url']));
            } else {
                $this->getResponse()->setHeader(
                    'Content-type',
                    'application/json'
                )->setBody($this->_success(1));
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->setSkipRules(false);
            if ($this->_getSession()->getUseNotice(true)) {
                $this->_getSession()->addNotice(
                    Mage::helper('core')->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(
                    explode(
                        "\n",
                        $e->getMessage()
                    )
                );
                foreach ($messages as $message) {
                    $this->_getSession()->addError(
                        Mage::helper('core')->escapeHtml($message)
                    );
                }
            }

            if (array_key_exists('view_page', $params)) {
                $this->_goBack();
            } elseif (array_key_exists('_redirect_url', $params)) {
                $this->_redirectUrl(base64_decode($params['_redirect_url']));
            } else {
                $this->getResponse()->setHeader(
                    'Content-type',
                    'application/json'
                )->setBody($this->_error(0));
            }

        } catch (Exception $e) {
            Mage::getSingleton('core/session')->setSkipRules(false);
            $this->_getSession()->addException(
                $e,
                $this->__('Cannot add the item to shopping cart.')
            );
            $this->_redirectReferer(
                Mage::helper('checkout/cart')->getCartUrl()
            );
            Mage::logException($e);
            if (array_key_exists('view_page', $params)) {
                $this->_goBack();
            } elseif (array_key_exists('_redirect_url', $params)) {
                $this->_redirectUrl(base64_decode($params['_redirect_url']));
            } else {
                $this->getResponse()->setHeader(
                    'Content-type',
                    'application/json'
                )->setBody($this->_error(0));
            }
        }

        return $this;

    }

    /**
     * Actual worker function to add gift to cart
     *
     * @return $this
     */
    public function _addGiftAction($cart, $params, $product)
    {
        if (!isset($params['view_page'])
            && isset($params['super_attribute'])
            && is_array($params['super_attribute'])
        ) {
            $params['super_attribute'] = array_pop(
                $params['super_attribute']
            );
        }
        if (isset($params['qty'])) {
            $filter = new Zend_Filter_LocalizedToNormalized(
                array(
                    'locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            $params['qty'] = $filter->filter($params['qty']);
        } else {
            $params['qty'] = 1;
        }
        if (isset($params['qty_override'])) {
            $params['multiple_of'] = (int)$params['qty_override'];
            mage::register('qty_override', (int)$params['qty_override'], true);
            // make it unique, so any other add to carts will add a ne wline item
            $params['multiple_of_uid'] = rand(5, 100000000) . rand(5, 100000000);
        }

        // load the rule if given
        if ($this->getRequest()->getParam('rule_id')) {
            $ruleObject = Mage::getModel('giftpromo/promo_rule')->load(
                $this->getRequest()->getParam('rule_id')
            );
            if ($ruleObject->getAllowGiftSelection()
                && $ruleObject->validate($cart->getQuote())
            ) {
                $giftProducts = $ruleObject->getItemsArray();
                $giftProductToAdd = $giftProducts[$product->getId()];
                // flag as current selected gift
                $currentSelectedGifts = $this->_getHelper()->getCurrentSelectedGifts();
                // are we trying to add the same gift, and it is allowed?
                if (in_array($this->getRequest()->getParam('selected_gift_item_key'), $currentSelectedGifts)
                    && $ruleObject->getGiftAddProductMulti()
                ) {
                    $params['multiple_of_uid'] = rand(5, 100000000) . rand(5, 100000000);
                    $giftProductToAdd->setSkipTriggerItem(true);
                } else {
                    // are we trying to replace the same gift?
                    $selectedKey = array_search(
                        $this->getRequest()->getParam('selected_gift_item_key'), $currentSelectedGifts
                    );
                    if ($selectedKey !== False
                        && $giftProductToAdd->getId() == $currentSelectedGifts[$selectedKey]
                        && $giftProductToAdd->getGiftedPrice() == 0
                    ) {
                        $this->_getSession()->addNotice(
                            Mage::helper('core')->escapeHtml(
                                $this->__(
                                    'Cannot add the item to shopping cart. You already have this item in your cart.'
                                )
                            )
                        );

                        $this->_goBack();

                        return $this;
                    }
                }
                $currentSelectedGifts[] = $this->getRequest()->getParam('selected_gift_item_key');
                $currentSelectedGiftsParts = explode(
                    "_",
                    $this->getRequest()->getParam('selected_gift_item_key')
                );
                if (is_array($currentSelectedGiftsParts)) {
                    if (count($currentSelectedGiftsParts) == 3) {
                        $params['parent_quote_item_id']
                            = $currentSelectedGiftsParts[1];
                    } else {
                        $params['parent_quote_item_id'] = array_pop(
                            $currentSelectedGiftsParts
                        );
                    }
                }
                if (array_key_exists('gift_parent_item_id', $params)) {
                    $params['parent_quote_item_id'] = $params['gift_parent_item_id'];
                }
                try {
                    $this->_getHelper()->addGiftItems(
                        array(
                            $giftProductToAdd),
                        $ruleObject,
                        $params
                    );
                    $this->_getHelper()->setCurrentSelectedGifts(
                        $currentSelectedGifts
                    );
                } catch (Exception $e) {
                    //something wrong, clear selected data
                    $this->_getHelper()->resetCurrentSelectedGift();
                    Mage::getSingleton('core/session')->setSkipRules(false);
                    //throw exception forward
                    Mage::throwException($e->getMessage());
                }
            }
        } else {
            if (mage::getIsDeveloperMode()) {
                die(
                'Giftpromo Rule param for gifting not passed to controller.
                    - Could not add from select gifts'
                );
            }
            mage::log(
                'Giftpromo Rule param for gifting not passed to controller.
                    - Could not add from select gifts'
            );
            Mage::getSingleton('core/session')->setSkipRules(false);
        }

        return $this;
    }

    /**
     * Set back redirect url to response
     *
     * @return Mage_Checkout_CartController
     * @throws Mage_Exception
     */
    protected function _goBack()
    {
        if ($this->getRequest()->getParam('is_checkout_review')) {
            $this->getResponse()->setHeader(
                'Content-type', 'application/json', true
            );
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($this->getRequest()->getPost())
            );
            // else review update does not work
            $this->_getSession()->setCartWasUpdated(false);
        } else {
            parent::_goBack();
        }

        return $this;
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
     * Success wrappper
     *
     * @var String
     *
     * @return string
     */
    protected function _success($content)
    {
        return Zend_Json::encode(
            array(
                "error" => false,
                "content" => $content
            )
        );
    }

    /**
     * Generate a JSON error object
     *
     * @param string $content
     */
    protected function _error($content)
    {
        return Zend_Json::encode(
            array(
                "error" => true,
                "content" => $content
            )
        );
    }

    /**
     * Delete gift
     */
    public function deleteGiftAction()
    {
        $params = $this->getRequest()->getParams();
        mage::helper('giftpromo')->debug(
            "REMOVE A GIFT DATA: " .
            print_r($params, true),
            1
        );
        if (array_key_exists(
                'rule_id',
                $params
            )
            && array_key_exists(
                'id',
                $params
            )
        ) {
            $cartItem = $this->_getQuote()->getItemById($params['id']);
            if (is_object($cartItem)) {
                $infoBuyRequest = $cartItem->getOptionByCode('info_buyRequest');
                $buyRequest = new Varien_Object(
                    unserialize($infoBuyRequest->getValue())
                );
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

            try {
                $this->_getCart()->removeItem($params['id']);
                $this->_getCart()->getQuote()->setTotalsCollectedFlag(true);
                $this->_getCart()->save(); // changed here;
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    $this->__('Cannot remove the item.')
                );
                Mage::logException($e);
            }
            $this->_redirectReferer(Mage::getUrl('*/*'));
        } else {
            $this->_forward('delete');
        }

        return $this;
    }

    /**
     * Simply return the number of items in the cart
     */
    public function getTopCartQtyAction()
    {
        $cartQty = (int)Mage::Helper('checkout/cart')->getItemsCount();
        if (is_integer($cartQty)) {
            $this->getResponse()->setHeader(
                'Content-type',
                'application/json'
            )->setBody($this->_success($cartQty));
        } else {
            $this->getResponse()->setHeader(
                'Content-type',
                'application/json'
            )->setBody($this->_error($cartQty));
        }

        return $this;
    }

    /**
     * Render the cart display skippping cache (top cart slider on all pages)
     */
    public function getTopCartAction()
    {
        $this->loadLayout();
        $layout = Mage::getSingleton('core/layout');
        $block = $layout->getBlock('cart_sidebar');
        if (is_object($block)) {
            $html = $block->toHtml();
            $this->getResponse()->setHeader(
                'Content-type',
                'application/json'
            )->setBody($this->_success($html));
        }

        return $this;
    }

    /**
     * Empty customer's shopping cart
     */
    protected function _emptyShoppingCart()
    {
        $this->_getHelper()->resetCurrentSelectedGift();
        $cart = $this->_getCart();
        $quote = $cart->getQuote();
        $quote->setAppliedGiftRuleIds(json_encode(array()));
        //ensure all gifts are removed.
        foreach ($this->_getHelper()->getAllGiftBasedCartItems() AS $giftitem) {
            $quote->removeItem($giftitem->getId());
        }
        return parent::_emptyShoppingCart();
    }

}
