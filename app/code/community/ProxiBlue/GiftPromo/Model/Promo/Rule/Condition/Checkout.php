<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Checkout extends Mage_SalesRule_Model_Rule_Condition_Address
{

    public function loadAttributeOptions()
    {

        $attributes = array(
            'payment_method' => Mage::helper('salesrule')->__('Payment Method'),
            'shipping_method' => Mage::helper('salesrule')->__('Shipping Method'),
            'postcode' => Mage::helper('salesrule')->__('Shipping Postcode'),
            'region' => Mage::helper('salesrule')->__('Shipping Region'),
            'region_id' => Mage::helper('salesrule')->__('Shipping State/Province'),
            'country_id' => Mage::helper('salesrule')->__('Shipping Country')
        );

        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * Validate Address Rule Condition
     *
     * @param Varien_Object $object
     *
     * @return bool
     */
    public function validate(Varien_Object $object)
    {

        $address = $object;
        if (!$address instanceof Mage_Sales_Model_Quote_Address) {
            if (is_callable($object,'isVirtual') && $object->isVirtual()) {
                $address = $object->getBillingAddress();
            } else {
                $address = $object->getShippingAddress();
            }
        }

        if(!is_object($address)) {
            return false;
        }

        switch ($this->getAttribute()) {
            case 'payment_method':
                if (!$address->hasPaymentMethod()) {
                    $address->setPaymentMethod($object->getPayment()->getMethod());
                }
                if(is_null($address->getPaymentMethod())){
                    Mage::helper('giftpromo')->removeRuleCartItems($this->getRule(), $object);
                    return false;
                }
                switch ($this->getOperator()) {
                    case '!=':
                        if ($address->getPaymentMethod() != $this->getValue()) {
                            {
                                return true;
                            }
                        }
                        break;
                    default:
                        if ($address->getPaymentMethod() == $this->getValue()) {
                            {
                                return true;
                            }
                        }
                        break;

                }
                Mage::helper('giftpromo')->removeRuleCartItems($this->getRule(), $object);
                return false;
                break;
            case 'shipping_method':
                if (!$address->hasShippingMethod()) {
                    $address->setShippingMethod($object->getShipping()->getMethod());
                }

                switch ($this->getOperator()) {
                    case '!=':
                        if ($address->getShippingMethod() != $this->getValue()) {
                            {
                                return true;
                            }
                        }
                        break;
                    default:
                        if ($address->getShippingMethod() == $this->getValue()) {
                            {
                                return true;
                            }
                        }
                        break;

                }
                $this->_getHelper()->removeRuleCartItems($this->getRule(), $object);
                return false;
                break;
        }

        return $this->validateAttribute($address->getData($this->getAttribute()));
    }

}
