<?php

/**
 * Shopping cart item render block
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Cart_Item_Renderer_Bundle extends Mage_Bundle_Block_Checkout_Cart_Item_Renderer
{
    /**
     * Can it delete ?
     *
     * @return boolean
     */
    public function canDelete()
    {
        return true;
    }

    /**
     * Get item delete url
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        if ($this->hasDeleteUrl()) {
            return $this->getData('delete_url');
        }

        $buyRequest = Mage::helper('giftpromo')->isAddedAsGift($this->getItem());

        if ($buyRequest) {
            return $this->getUrl(
                'checkout/cart/deleteGift', array(
                    'id'                                                      => $this->getItem()->getId(),
                    'rule_id'                                                 => $buyRequest->getAddedByRule(),
                    Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => $this->helper('core/url')
                        ->getEncodedUrl()
                )
            );
        }
    }

    public function canLinkItem($item)
    {
        return Mage::helper('giftpromo')->canLinkItem($item);
    }

    public function compositeMessage()
    {
        if (mage::getStoreConfig('giftpromo/cart/composite_message')) {
            $additionalMessage = '';
            $parentItem = Mage::helper('giftpromo')->getParentQuoteItemOfGift($this->getItem());
            if ($parentItem !== false) {
                if ($parentItem->getProduct()->isComposite()) {
                    //TODO: Add support for bundles and Grouped
                    if ($parentItem->getProductType() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                        $optionsRendererBlock = Mage::getModel('core/layout')->createBlock(
                            'Mage_Checkout_Block_Cart_Item_Renderer_Configurable',
                            'configurable_options_block', array('template' => 'giftpromo/cart/item/options.phtml')
                        );
                        $optionsRendererBlock->setItem($parentItem);
                        $optionsHtml = $optionsRendererBlock->toHtml();
                        $multiple = ($optionsRendererBlock->getMultiple()) ? 's' : '';
                        $additionalMessage = Mage::helper('giftpromo')->__(
                            'with option%s: %s ', $multiple, $optionsHtml
                        );

                    }
                    $noticeMessage = Mage::helper('giftpromo')->__(
                        'This gift added for "%s" %s ', $parentItem->getProduct()->getName(), $additionalMessage
                    );
                    $object = new Varien_Object();
                    $object->setMessage($noticeMessage);
                    $object->setItem($this->getItem());
                    $object->setAdditionalMessage($additionalMessage);
                    $object->setSkipMessage(false);
                    Mage::dispatchEvent(
                        'gift_product_composite_message',
                        array(
                            'composite_message' => $object
                        )
                    );
                    if ($object->getSkipMessage() == false) {
                        $checkoutSession = $this->getCheckoutSession();
                        $messageFactory = Mage::getSingleton('core/message');
                        $message = $messageFactory->notice($object->getMessage());
                        $checkoutSession->addQuoteItemMessage($object->getItem()->getId(), $message);
                    }
                }
            }
        }
    }

    public function canDoQty($item)
    {
        if($item->getPrice() > 0) {
            return true;
        }
        return false;
    }

}
