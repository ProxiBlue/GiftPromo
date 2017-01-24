<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Customer_Conditions extends Mage_Rule_Model_Condition_Combine
{

    public function __construct()
    {
        parent::__construct();
        $this->setType('giftpromo/promo_rule_condition_customer_conditions')
            ->setValue(null);
    }

    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            Mage::helper('rule')->__(
                'If  %s these customer conditions are %s:', $this->getAggregatorElement()->getHtml(),
                $this->getValueElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }

        return $html;
    }

    public function asString($format = '')
    {
        $str = Mage::helper('rule')->__("If these customer conditions are %s:", $this->getValueName());

        return $str;
    }

    /**
     * Conditions child rules
     * Current supported:
     * Cart Subtotal
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions, array(
                array('value' => 'giftpromo/promo_rule_condition_customer_conditions_count',
                      'label' => Mage::helper('giftpromo')->__('Number of times gift was given')),
                array('value' => 'giftpromo/promo_rule_condition_customer_conditions_period',
                      'label' => Mage::helper('giftpromo')->__('Period since registration occured')),
                //array('value'=> 'giftpromo/promo_rule_condition_customer_action_register', 'label'=>Mage::helper('giftpromo')->__('Customer Registration')),
                array('value' => 'giftpromo/promo_rule_condition_customer_conditions_numsales',
                      'label' => Mage::helper('giftpromo')->__('Number of sucessful sales')),
                array('value' => 'giftpromo/promo_rule_condition_customer_conditions_subscription',
                      'label' => Mage::helper('giftpromo')->__('Subscription to NewsLetter')),
            )
        );

        return $conditions;
    }

    public function validate(Varien_Object $object)
    {
        $all = $this->getAggregator() === 'all';
        $true = (bool)$this->getValue();
        $found = false;
        foreach ($object->setData('trigger_recollect', 0)->getAllItems() as $item) {
            if (Mage::helper('giftpromo')->testGiftTypeCode($item->getProductType())) {
                continue;
            }
            $found = $all;
            foreach ($this->getConditions() as $cond) {
                $validated = $cond->validate($item);
                if (($all && !$validated) || (!$all && $validated)) {
                    $found = $validated;
                    break;
                }
            }
            if (($found && $true) || (!$true && $found)) {
                break;
            }
        }
        // found an item and we're looking for existing one
        if ($found && $true) {
            return true;
        } // not found and we're making sure it doesn't exist
        elseif (!$found && !$true) {
            return true;
        }

        return false;
    }

}
