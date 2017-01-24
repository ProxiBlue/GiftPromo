<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Subselect_Free
    extends ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Subselect
{


    public function __construct()
    {
        parent::__construct();
        $this->setType('giftpromo/promo_rule_condition_product_subselect_free')
            ->setValue(null)
            ->setSecondValue(1)
            ->setReplaceGiftOnRemove(true);
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
        try {
            if (!$this->getConditions()) {
                return false;
            }

            $numFree = $this->getSecondValue();
            $skipAddCheapest = false;
            $attr = $this->getAttribute();
            $total = 0;
            $alreadyAssignedToCart = false;
            $ruleBasedCartItems = mage::helper('giftpromo')->getRuleBasedCartItems(true);
            $allVisibleItems = mage::helper('giftpromo')->getAllVisibleItems($object);
            foreach ($allVisibleItems as $item) {
                if (mage::helper('giftpromo')->isAddedAsGift($item)) {
                    $alreadyAssignedToCart = $item;
                    continue;

                }
                $productId = ($item instanceof Mage_Catalog_Model_Product) ? $item->getId()
                    : $item->getProduct()->getId();
                $item->setProduct(mage::getModel('catalog/product')->load($productId));
                if ($this->_validateItems($item)) {
                    $total += $item->getData($attr);
                }
            }

            $isValid = $this->validateAttribute($total);
            if ($isValid) {

                //$giftParentIds = array_keys($ruleBasedCartItems);
                $priceList = array();
                $allVisibleItems = mage::helper('giftpromo')->getAllVisibleItems($object);
                foreach ($allVisibleItems as $item) {
                    if (mage::helper('giftpromo')->isAddedAsGift($item)) {
                        continue; // do not count gift items
                    }
                    if (!$item->getId() || !$item->getRowTotal()) {
                        // cart is not yet ready for this, so skip to calc
                        // on future iteration of totals collection
                        return false;
                    }
                    $priceList[$item->getId()] = $item->getRowTotal();
                }
                // prevent same item with qty, as cannot give same item as free
                if (count($priceList) > 0 && $total > 1) {
                    asort($priceList);
                    $cheapestItems = array_slice($priceList, 0, $numFree, true);
                    foreach ($cheapestItems as $cheapestId => $cheapestItem) {
                        //$cheapestId = array_keys($priceList, min($priceList));
                        //$cheapestId = reset($cheapestItem);
                        // now convert that as a gift item
                        $cheapestItemObject = $object->getItemById($cheapestId);
                        if (is_object($cheapestItemObject)) {
                            if ($alreadyAssignedToCart) {
                                // ok, so we already have a gift for cheapest in cart for this rule.
                                // is the item we are now trying to add in cheaper?
                                $currentGiftPrice = $alreadyAssignedToCart->getProduct()->getPrice();
                                $cheapestPrice = $cheapestItemObject->getRowTotal();
                                $skipAddCheapest = false;
                                if ($currentGiftPrice <= $cheapestPrice) {
                                    // we already have the cheapest in the cart, so increase the QTY!
                                    $alreadyAssignedToCart->setQty($alreadyAssignedToCart->getQty() + 1);
                                    $alreadyAssignedToCart->save();
                                    $skipAddCheapest = true;
                                }
                            }
                            //delete the item from cart now that it was injected
                            $currentQty = $cheapestItemObject->getQty();
                            $newQty = $currentQty - 1;
                            if ($newQty == 0) {
                                if ($cheapestItemObject->getId()) {
                                    $object->removeItem($cheapestItemObject->getId());
                                    if (method_exists($object, 'save')) {
                                        $object->save();
                                    } elseif (method_exists($cheapestItemObject, 'save')) {
                                        $cheapestItemObject->save();
                                    }
                                }
                            } else {
                                $cheapestItemObject->setQty($newQty);
                                if (method_exists($cheapestItemObject, 'save')) {
                                    $cheapestItemObject->save();
                                }
                            }
                            if ($skipAddCheapest == false) {
                                $this->getRule()->injectGiftProduct($cheapestItemObject, true, true, count($priceList));
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            mage::logException($e);

            return false;
        }

        return $isValid;
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
        /** @var Mage_Catalog_Model_Product $product */
        $product = $object->getProduct();

        $valid = $this->_validateConditions($object);
        if (!$valid && $product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
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


    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            Mage::helper('salesrule')->__(
                "If the %s %s %s for a subselection of items"
                . " in cart matching %s of these conditions"
                . " are met, then give the cheapest %s items for free:", $this->getAttributeElement()->getHtml(),
                $this->getOperatorElement()->getHtml(), $this->getValueElement()->getHtml(),
                $this->getAggregatorElement()->getHtml(), $this->getSecondValueElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }

        return $html;
    }

    public function loadOperatorOptions()
    {

        $this->setOperatorOption(
            array(
                '==' => Mage::helper('rule')->__('is'),
                '!=' => Mage::helper('rule')->__('is not'),
                '>=' => Mage::helper('rule')->__('equals or greater than'),
                '<=' => Mage::helper('rule')->__('equals or less than'),
                '>' => Mage::helper('rule')->__('greater than'),
                '<' => Mage::helper('rule')->__('less than'),
                '()' => Mage::helper('rule')->__('is one of'),
                '!()' => Mage::helper('rule')->__('is not one of'),
            )
        );

        return $this;
    }

    public function getSecondValueElement()
    {
        $elementParams = array(
            'name' => 'rule[' . $this->getPrefix() . '][' . $this->getId() . '][second_value]',
            'value' => $this->getSecondValue(),
            'values' => $this->getSecondValueSelectOptions(),
            'value_name' => $this->getSecondValueName(),
            'after_element_html' => $this->getSecondValueAfterElementHtml(),
            'explicit_apply' => $this->getExplicitApply(),
        );

        return $this->getForm()->addField(
            $this->getPrefix() . '__' . $this->getId() . '__second_value',
            $this->getValueElementType(),
            $elementParams
        )->setRenderer($this->getValueElementRenderer());
    }

    public function getSecondValueName()
    {
        $value = $this->getSecondValue();
        if (is_null($value) || '' === $value) {
            return '...';
        }

        if (!empty($valueArr)) {
            $value = implode(', ', $valueArr);
        }
        return $value;
    }

    public function loadArray($arr, $key='conditions')
    {
        parent::loadArray($arr, $key);
        if(array_key_exists('second_value', $arr)) {
            $this->setSecondValue($arr['second_value']);
        }
        return $this;
    }

    public function asArray(array $arrAttributes = array())
    {
        $out = parent::asArray($arrAttributes);
        $out['second_value'] = $this->getSecondValue();

        return $out;
    }

}
