<?php

/**
 * Events observers to deal with frontent cart and quote gift data
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Checkout_Observer extends ProxiBlue_GiftPromo_Model_Observer
{

    /**
     * Event to add options to gift, after it was added to the cart.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return ProxiBlue_GiftPromo_Model_Checkout_Observer
     */
    public function checkout_cart_product_add_after(Varien_Event_Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            $quoteItem = $observer->getEvent()->getQuoteItem();
            if ($this->_gethelper()->isAddedAsGift($quoteItem)) {
                // update the qty's just to make sure all is as it should be.
                $option = Mage::getModel('sales/quote_item_option')
                    ->setProductId($product->getId())
                    ->setCode('product_type')
                    ->setProduct($product)// needed for EE only ?
                    ->setValue($this->_getHelper()->getGiftProductType($product->getTypeId()));
                $quoteItem->addOption($option);
                $giftProduct = mage::getModel('giftpromo/product')->cloneProduct($product);
                // force any percentage / custom prices.
                $giftedPrice = $giftProduct->getGiftedPrice();
                if (strpos($giftedPrice, '%') !== false) {
                    $realPrice = $giftProduct->getPrice();
                    $giftedPrice = $realPrice - (float)(str_replace('%', '', $giftedPrice) / 100 * $realPrice);
                    $giftProduct->setGiftedPrice($giftedPrice);
                    $giftProduct->setCalculatedFinalPrice($giftedPrice);
                    $quoteItem->setConvertedPrice($giftedPrice);
                    $quoteItem->setBasePrice($giftedPrice);
                    $quoteItem->setPrice($giftedPrice);
                    $quoteItem->setRowTotal($giftedPrice * $quoteItem->getQty());
                    Mage::getSingleton('core/session')->setSkipLimitValidation(true);
                    Mage::getSingleton('core/session')->setSkipSubtotalValidation(true);
                } elseif (isset($giftedPrice)) {
                    $quoteItem->setConvertedPrice($giftedPrice);
                    $quoteItem->setBasePrice($giftedPrice);
                    $quoteItem->setPrice($giftedPrice);
                    $quoteItem->setRowTotal($giftedPrice * $quoteItem->getQty());
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
     * Event to update any gift associated to a product, to the cart.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return ProxiBlue_GiftPromo_Model_Checkout_Observer
     */
    public function checkout_type_onepage_save_order(Varien_Event_Observer $observer)
    {
        /**
         * is this a gift-configurable?
         * if so, get the child order item, and attach it.
         * magento internals expect the quote items order to be
         * - parent
         * - child
         * (really insane coding to depend on an order like that!)
         * however the gifting comes in as
         * - child
         * - parent
         * Thus the routine located in Mage_Sales_Model_Service_Quote::submitOrder
         * cannot set the parentItem of the child, as it looks for the parent item, which
         * has not been created. This here fixes the issue, by working the attachement backwards
         *
         */
        try {
            $order = $observer->getOrder();
            foreach ($order->setData('trigger_recollect', 0)->getAllItems() as $item) {
                if ($item->getParentItemId() && $this->_getHelper()->testGiftTypeCode($item->getProductType())) {
                    $parentItem = $order->getItemByQuoteItemId($item->getQuoteParentItemId());
                    $item->setParentItem($parentItem);
                    $item->unsParentItemId(); // fixed later as we have no id yet for the parent item
                }
            }

            return $this;
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
     * Event to update any gift associated to a product, to the cart.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return ProxiBlue_GiftPromo_Model_Checkout_Observer
     */
    public function controller_action_predispatch_checkout_onepage_index(Varien_Event_Observer $observer)
    {
        try {
            $quote = Mage::getModel('checkout/session')->getQuote();
            $this->_getHelper()->checkForGiftChanges($quote);
        } catch (Exception $e) {
            // log any issues, but allow system to continue.
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }

        return $this;
    }

}
