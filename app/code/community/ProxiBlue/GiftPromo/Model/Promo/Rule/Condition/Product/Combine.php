<?php

/**
 * Conditions combine
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 *
 * */
class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Combine extends Mage_Rule_Model_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('giftpromo/rule_condition_product_combine');
    }

    public function getNewChildSelectOptions()
    {
        $productCondition = Mage::getModel('giftpromo/promo_rule_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $pAttributes = array();
        $iAttributes = array();
        foreach ($productAttributes as $code => $label) {
            if (strpos($code, 'quote_item_') === 0) {
                $iAttributes[] = array('value' => 'salesrule/rule_condition_product|' . $code, 'label' => $label);
            } else {
                $pAttributes[] = array('value' => 'salesrule/rule_condition_product|' . $code, 'label' => $label);
            }
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions, array(
                array('value' => 'salesrule/rule_condition_product_combine',
                      'label' => Mage::helper('catalog')->__('Conditions Combination')),
                array('label' => Mage::helper('catalog')->__('Cart Item Attribute'), 'value' => $iAttributes),
                array('label' => Mage::helper('catalog')->__('Product Attribute'), 'value' => $pAttributes),
            )
        );

        return $conditions;
    }

    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }

        return $this;
    }
}
