<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Address extends Mage_SalesRule_Model_Rule_Condition_Address
{
    public function loadAttributeOptions()
    {
        $attributes = array(
            'items_qty' => Mage::helper('salesrule')->__('Total Items Quantity'),
            'weight'    => Mage::helper('salesrule')->__('Total Weight')
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
            if ($object->isVirtual()) {
                $address = $object->getBillingAddress();
            } else {
                $address = $object->getShippingAddress();
            }
        }

        switch ($this->getAttribute()) {
            case 'items_qty':
                $totalQty = 0;
                // tally up the qty for all non gift items
                $allVisibleItems = mage::helper('giftpromo')->getAllVisibleItems($object);
                foreach ($allVisibleItems as $item) {
                    if (!mage::helper('giftpromo')->testGiftTypeCode($item->getProductType())) {
                        $totalQty += $item->getQty();
                    }
                }

                return $this->validateAttribute($totalQty);
                break;
            case 'weight':
                $totalWeight = 0;
                // tally up the weight for all non gift items
                $allVisibleItems = mage::helper('giftpromo')->getAllVisibleItems($object);
                foreach ($allVisibleItems as $item) {
                    if (!mage::helper('giftpromo')->testGiftTypeCode($item->getProductType())) {
                        $totalWeight += $item->getWeight();
                    }
                }

                return $this->validateAttribute($totalWeight);
                break;
        }

        return $this->validateAttribute($address->getData($this->getAttribute()));
    }

}
