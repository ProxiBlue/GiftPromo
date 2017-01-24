<?php

/**
 * Gift Product shopping cart item renderer
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Cart_Item_Message extends Mage_Core_Block_Template
{

    protected $_messageData = null;

    public function getItem()
    {
        return $this->getParentBlock()->getItem();
    }

    public function getMessage()
    {
        // does this product conform to the rule?
        $ruleObject = Mage::getModel('giftpromo/promo_rule')->load($this->getMessageData()->getRuleId());
        $allowedCategoryId = $ruleObject->getAllowGiftFromCategory();
        $productCats = $this->getItem()->getProduct()->getCategoryIds();
        if (!in_array($allowedCategoryId, $productCats)) {
            return '';
        }
        return $this->__($this->getMessageData()->getMessage(), $this->getMessageData()->getValue());

    }

    public function getUpgradeUrl()
    {
        return $this->getUrl(
            'checkout/cart/upgradeLineItem',
            array('data' => base64_encode(Mage::helper('core')->encrypt($this->getItem()->getId()))
                  , 'rule_id' => $this->getRuleId()
            )
        ) ;

    }

    private function getMessageData()
    {
        if (is_null($this->_messageData)) {
            $this->_messageData = $this->getItem()->getOptionByCode('cart_message');
            if(is_null($this->_messageData)) {
                $this->_messageData = new Varien_Object;
            }
        }
        return $this->_messageData;
    }

    public function getRuleId()
    {
        return $this->getMessageData()->getRuleId();
    }


}
