<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Subselect
    extends Mage_SalesRule_Model_Rule_Condition_Product_Subselect
{

    protected $_quoteObject = null;

    public function __construct()
    {
        parent::__construct();
        $this->setType('giftpromo/promo_rule_condition_product_subselect')
            ->setValue(null);
    }

    public function loadAttributeOptions()
    {
        $this->setAttributeOption(array(
            'qty'  => Mage::helper('salesrule')->__('total quantity'),
            'base_row_total'  => Mage::helper('salesrule')->__('total amount (excl tax)'),
            'base_row_total_incl_tax'  => Mage::helper('salesrule')->__('total amount (incl tax)')
        ));
        return $this;
    }

    /**
     * validate
     *
     * @param Varien_Object $object Quote
     *
     * @return boolean
     */
    public function validate(Varien_Object $object)
    {
        if (!$this->getConditions()) {
            return false;
        }
        $attr = $this->getAttribute();
        $totals = array();
        $giftpromoHelper = mage::helper('giftpromo');
        $allVisibleItems = $giftpromoHelper->getAllVisibleItems($object);
        foreach ($allVisibleItems as $item) {
            if ($giftpromoHelper->testGiftTypeCode($item->getProductType())) {
                continue;
            }
            $result = $this->_validateItems($item);
            if ($result) {
                if (!array_key_exists($item->getId(), $totals)) {
                    if (!$item->getId()) {
                        // we are not ready for this. item has not yet added to cart
                        return false;
                    }
                    $totals[$item->getId()] = 0;
                }
                $totals[$item->getId()] = $totals[$item->getId()] + $item->getData($attr);
            }
        }

        $this->_quoteObject = $object;
        $result = $this->validateAttribute($totals);

        return $result;
    }

    /**
     * Validate a condition with the checking of the child value
     *
     * @param Varien_Object $object
     *
     * @return bool
     */
    public function _validateItems(Varien_Object $object)
    {
        $valid = $this->_validateConditions($object);
        if (!$valid && $object->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            $children = $object->getChildren();
            $valid = $children && $this->_validateItems($children[0]);
        }

        return $valid;
    }

    public function _validateConditions(Varien_Object $object)
    {
        if (!$this->getConditions()) {
            return true;
        }

        $all = $this->getAggregator() === 'all';
        $true = (bool)$this->getValue();

        foreach ($this->getConditions() as $cond) {
            $validated = $cond->validate($object);

            if ($all && $validated !== $true) {
                return false;
            } elseif (!$all && $validated === $true) {
                return true;
            }
        }

        return $all ? true : false;
    }


    public function validateAttribute($validatedItems)
    {
        //ensure any class children that expects to pass non array gets to parent original method
        if (!is_array($validatedItems)) {
            return parent::validateAttribute($validatedItems);
        }
        $validatedValue = array_sum($validatedItems);
        $value = $this->getValueParsed();
        /**
         * Comparison operator
         */
        $op = $this->getOperatorForValidate();

        // if operator requires array and it is not, or on opposite, return false
        if ($this->isArrayOperatorType() xor is_array($validatedValue)) {
            return false;
        }


        switch ($op) {
            case '---':
                if ($validatedValue > 0) {
                    $qty = floor($validatedValue / $value);
                    if ($qty > 0) {
                        mage::register('qty_override', $qty, true);
                        if ($this->getAttribute() == 'qty') {
                            mage::register('ratio_override', $validatedValue, true);
                        }

                        return true;
                    }
                }
                break;
            default:
                return parent::validateAttribute($validatedValue);
                break;
        }

        return false;
    }


    public function loadValueOptions()
    {
        $this->setValueOption(array());

        return array();
    }

    public function loadOperatorOptions()
    {

        $this->setOperatorOption(
            array(
                '=='  => Mage::helper('rule')->__('is'),
                '!='  => Mage::helper('rule')->__('is not'),
                '>='  => Mage::helper('rule')->__('equals or greater than'),
                '<='  => Mage::helper('rule')->__('equals or less than'),
                '>'   => Mage::helper('rule')->__('greater than'),
                '<'   => Mage::helper('rule')->__('less than'),
                '()'  => Mage::helper('rule')->__('is one of'),
                '!()' => Mage::helper('rule')->__('is not one of'),
                '---' => Mage::helper('rule')->__('is multiples of')
            )
        );

        return $this;
    }

    public function getFlag($string)
    {
        return $this->getData($string);
    }

    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            Mage::helper('salesrule')->__(
                "If the %s %s %s for a subselection of items in cart matching %s of these conditions:",
                $this->getAttributeElement()->getHtml(), $this->getOperatorElement()->getHtml(),
                $this->getValueElement()->getHtml(), $this->getAggregatorElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }

        return $html;
    }
}
