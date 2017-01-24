<?php

class ProxiBlue_GiftPromo_Block_Cart_Item_Renderer_Downloadable
    extends Mage_Downloadable_Block_Checkout_Cart_Item_Renderer
{
    /**
     * Get the parent product of a gift
     *
     * @return object
     */
    public function getParentOfGift()
    {
        return Mage::helper('giftpromo')->getParentOfGift($this->getItem());
    }

    /**
     * Can it delete ?
     *
     * @return boolean
     */
    public function canDelete()
    {
        $buyRequest = Mage::helper('giftpromo')->isAddedAsGift($this->getItem());
        $ruleObject = Mage::getModel('giftpromo/promo_rule')->load($buyRequest->getAddedByRule());

        return $ruleObject->getAllowGiftSelection();
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

        return $this->getUrl(
            'checkout/cart/deleteGift', array(
                'id'                                                      => $this->getItem()->getId(),
                'rule_id'                                                 => $buyRequest->getAddedByRule(),
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => $this->helper('core/url')->getEncodedUrl()
            )
        );
    }

    public function canLinkItem($item)
    {
        return Mage::helper('giftpromo')->canLinkItem($item);
    }

    public function canDoQty($item)
    {
        if($item->getPrice() > 0) {
            return true;
        }
        return false;
    }

}
