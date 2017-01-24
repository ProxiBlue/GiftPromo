<?php
/**
 * Product controller - view gift products
 *
 */

require_once(Mage::getModuleDir(
        'controllers',
        'Mage_Catalog'
    ) . DS . 'ProductController.php');

class ProxiBlue_GiftPromo_ProductController extends Mage_Catalog_ProductController
{

    public function popupAction()
    {
        return parent::viewAction();
    }

    /**
     * Product view action
     */
    public function giftviewAction()
    {
        // Prepare helper and params
        $viewHelper = Mage::helper('giftpromo/product_view');

        try {
            // security, get the key, decrypt the data
            $key = $this->getRequest()->getParam('key', false);
            if (!$key) {
                mage::throwException('attempting to view a gift without the key values');
            }
            $decrypted = unserialize(Mage::helper('core')->decrypt($key));
            if (!is_array($decrypted)) {
                mage::throwException('something fishy with the gift view key data');
            }
            // is the quote current, and active
            $quote = Mage::getSingleton('checkout/session')->getQuote();

            if (array_key_exists('quoteid', $decrypted)
                && Mage::getSingleton('checkout/session')->getQuoteId() != $decrypted['quoteid']) {
                mage::throwException(
                    'attempting to view a gift page from a non matching quote object. Maybe a shared link.'
                );
            }
            $productId = (int)$decrypted['itemid'];
            // test if this product is an active gift, in an active gifting rule, that can validate
            $ruleObject = Mage::getModel('giftpromo/promo_rule')->load($decrypted['giftruleid']);
            $address = $quote->getShippingAddress();
            $store = Mage::app()->getStore($quote->getStoreId());
            $validator = Mage::getSingleton('giftpromo/promo_validator');
            $validator->init(
                $store->getWebsiteId(),
                $quote->getCustomerGroupId(),
                $quote->getCouponCode()
            );
            if ($validator->canProcessRule(
                $ruleObject,
                $address
            )
            ) {
                if ($ruleObject->validate($quote)) {
                    $params = new Varien_Object();
                    Mage::register('current_rule_object', $ruleObject, true);
                    Mage::register('current_gift_item_key', $decrypted['giftitemkey'], true);
                    if (array_key_exists('parentid', $decrypted)) {
                        Mage::register('current_gift_parent_item_id', $decrypted['parentid'], true);
                    }
                    // Render page
                    $viewHelper->prepareAndRender($productId, $this, $params);

                    return $this;
                }
            }

            mage::throwException(
                'an attempt to view a gift view page did not work. gift may no longer validate as a gift?'
            );

        } catch (Exception $e) {
            if ($e->getCode() == $viewHelper->ERR_NO_PRODUCT_LOADED) {
                if (isset($_GET['store']) && !$this->getResponse()->isRedirect()) {
                    $this->_redirect('');
                } elseif (!$this->getResponse()->isRedirect()) {
                    $this->_forward('noRoute');
                }
            } else {
                Mage::logException($e);
                $this->_forward('noRoute');
            }
        }

        return $this;
    }

}
