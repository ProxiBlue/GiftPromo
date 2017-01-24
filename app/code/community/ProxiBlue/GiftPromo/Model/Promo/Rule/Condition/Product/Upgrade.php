<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Upgrade
    extends Mage_SalesRule_Model_Rule_Condition_Product_Subselect
{

    protected $_quoteObject = null;

    public function __construct()
    {
        parent::__construct();
        $this->setType('giftpromo/promo_rule_condition_product_upgrade')
            ->setValue(null);
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
            // possible this came from catalog
            if ($item instanceof Mage_Catalog_Model_Product) {
                //make sure the product is fully populated for validation.
                $item->load($item->getId());
                $item->setProduct($item);
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

        // do we have the right number of products counted?
        // Prevents a single product from validating
        // if it is at the given qty by itself.
        $conditions = $this->getRule()->getConditions()->getConditions();
        if (is_array($conditions)) {
            foreach ($conditions as $condition) {
                if ($condition instanceof ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Upgrade) {
                    $productConditions = $condition->getConditions();
                    break;
                }
            }
        }
        if (!is_array($productConditions)) {
            $productConditions = array();
        }
        if (count($totals) < count($productConditions)) {
            // no products passed validation
            return false;
        }
        // only products that validate as in teh rule will appear in totals
        $this->_quoteObject = $object;
        $this->validateAttribute($totals);

        // forced false, so gifting does not actually happen.
        // there is no gift to add, as the upgrade was possibly already actioned, or not valid

        return false;
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
        $product = Mage::getModel('catalog/product')->load($object->getProductId());
        $object->setProduct($product);
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

    public function validateAttribute($validatedItems)
    {
        $validatedValue = array_sum($validatedItems);
        $value = $this->getValueParsed();
        if ($value > 0) {
            $upgradeQty = floor($validatedValue / $value);
            if ($upgradeQty > 0) {
                // do the upgrade
                // but make the iteration the lowest qty of the available items
                arsort($validatedItems);
                $upgraded = 0;
                $upgradeToQty = 0;
                while ($upgraded < $upgradeQty) {
                    foreach ($validatedItems as $itemId => $qty) {
                        $upgradeItem = $this->_quoteObject->getItemById($itemId);
                        if (is_object($upgradeItem)) {
                            $newQty = $upgradeItem->getQty() - 1;
                            if ($newQty == 0) {
                                if ($upgradeItem->getId()) {
                                    $this->_quoteObject->removeItem($upgradeItem->getId());
                                    // one of the upgrade items has reached 0, so quit upgrading
                                    $upgraded = $upgradeQty;
                                }
                            } else {
                                $upgradeItem->setQty($newQty);
                                if (method_exists(
                                    $upgradeItem,
                                    'save'
                                )) {
                                    $upgradeItem->save();
                                }
                            }
                        }
                    }
                    $upgraded++;
                    $upgradeToQty++;
                }
                if ($upgraded > 0) {
                    // inject the new item
                    $ruleObject = $this->getRule();
                    $giftProducts = $ruleObject->getItemsArray();

                    try {
                        foreach ($giftProducts as $giftProduct) {
                            $product = mage::getModel('catalog/product')->load($giftProduct->getId());
                            $cart = mage::helper('giftpromo')->getCartSession();
                            $product->addCustomOption(
                                'additional_options',
                                serialize(
                                    array(
                                        'gifted_message' =>
                                            array(
                                                'label' => $giftProduct->getGiftedLabel(),
                                                'value' => $giftProduct->getGiftedMessage()
                                            )
                                    )
                                )
                                ,
                                $product
                            );
                            $cart->addProduct(
                                $product,
                                array_merge(
                                    array(
                                        'qty' => $upgradeToQty,
                                    ),
                                    array()
                                )
                            );

                        }
                    } catch (Exception $e) {
                        if (Mage::app()->getStore()->isAdmin()) {
                            Mage::getSingleton('adminhtml/session_quote')->addError(
                                $e->getMessage()
                            );
                        } else {
                            if (!mage::helper('giftpromo')->isPre16()) {
                                $messageFactory = Mage::getSingleton('core/message');
                                $message = $messageFactory->error(
                                    $e->getMessage()
                                );
                                Mage::getModel('checkout/cart')->getCheckoutSession()->addUniqueMessages($message);
                            } else {
                                Mage::getModel('checkout/cart')->getCheckoutSession()->addMessage($e->getMessage());
                            }
                        }
                        mage::getModel('checkout/session')->setBusyGiftCollecting(false);
                    }


                    $this->_quoteObject->save();
                }
            }
        }

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
                '>='  => Mage::helper('rule')->__('is equals or greater than'),
                '<='  => Mage::helper('rule')->__('is equals or less than'),
                '>'   => Mage::helper('rule')->__('is greater than'),
                '<'   => Mage::helper('rule')->__('is less than'),
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

    public function loadAttributeOptions()
    {
        $this->setAttributeOption(
            array(
                'qty' => Mage::helper('giftpromo')->__('combined quantity')
            )
        );

        return $this;
    }

    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            Mage::helper('giftpromo')->__(
                "If the  %s for the following cart items, %s %s:", $this->getAttributeElement()->getHtml(),
                $this->getOperatorElement()->getHtml(), $this->getValueElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }

        return $html;
    }

    public function getNewChildSelectOptions()
    {
        $productCondition = Mage::getModel('salesrule/rule_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $pAttributes = array();
        foreach ($productAttributes as $code => $label) {
            if ($code == 'sku') {
                $pAttributes[] = array('value' => 'giftpromo/promo_rule_condition_product_upgrade_product|' . $code,
                                       'label' => $label);
            }
        }
        $conditions = array(
            array('label' => Mage::helper('catalog')->__('Product Attribute'), 'value' => $pAttributes),
        );

        return $conditions;
    }


}
