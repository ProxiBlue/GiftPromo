<?php

/**
 * Sales orders controller
 *
 ** @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
require_once(Mage::getModuleDir('controllers', 'Mage_Sales') . DS . 'OrderController.php');

class ProxiBlue_GiftPromo_Sales_OrderController extends Mage_Sales_OrderController
{

    /**
     * Action for reorder
     */
    public function reorderAction()
    {
        if (!$this->_loadValidOrder()) {
            return;
        }
        $order = Mage::registry('current_order');

        $cart = Mage::getSingleton('checkout/cart');

        /* @var $cart Mage_Checkout_Model_Cart */

        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            try {
                // do not try and re-add gift types, as they will be re-attached if still available via parent adding.
                if (Mage::helper('giftpromo')->testGiftTypeCode($item->getProductType())) {
                    continue;
                }
                $cart->addOrderItem($item);
            } catch (Mage_Core_Exception $e) {
                if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
                    Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
                } else {
                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                }
                $this->_redirect('*/*/history');
            } catch (Exception $e) {
                Mage::getSingleton('checkout/session')->addException(
                    $e, Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
                );
                $this->_redirect('checkout/cart');
            }
        }

        $cart->save();
        $this->_redirect('checkout/cart');
    }

}
