<?php

/**
 * Product promo rule
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 *
 * */
class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Found
    extends ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Combine
{

    public function __construct()
    {
        parent::__construct();
        $this->setType('giftpromo/promo_rule_condition_product_found');
    }

    /**
     * Load value options
     *
     * @return Mage_SalesRule_Model_Rule_Condition_Product_Found
     */
    public function loadValueOptions()
    {
        $this->setValueOption(
            array(
                1 => Mage::helper('giftpromo')->__('FOUND'),
                0 => Mage::helper('giftpromo')->__('NOT FOUND')
            )
        );

        return $this;
    }

    public function asHtml()
    {
        $html
            = $this->getTypeElement()->getHtml() . Mage::helper('giftpromo')->__(
                "If an item is %s in the cart with %s of these conditions true:",
                $this->getValueElement()->getHtml(),
                $this->getAggregatorElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }

        return $html;
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
        mage::helper('giftpromo')->debug(__FUNCTION__ . " in " . __FILE__ . " at " . __LINE__, 10);
        $all = $this->getAggregator() === 'all';
        $true = (bool)$this->getValue();
        $found = false;
        $forcedValidItem = false;
        $allItems = $object->setData(
            'trigger_recollect',
            0
        )->getAllVisibleItems();
        if (is_array($allItems)) {
            foreach ($allItems as $item) {
                mage::helper('giftpromo')->debug(__FUNCTION__ . " in " . __FILE__ . " at " . __LINE__, 10);
                if (Mage::helper('giftpromo')->testGiftTypeCode(
                    $item->getProductType()
                )
                ) {
                    continue;
                }
                // was this item already applied to this rule?
                $appliedGiftRuleIds = mage::Helper('giftpromo')
                    ->getAppliedRuleIds($item);
                if ($object->getSkipForced() !== true
                    && in_array(
                        $this->getRule()->getId(),
                        $appliedGiftRuleIds
                    )
                ) {
                    $forcedValidItem = $item;
                    continue;
                }
                mage::helper('giftpromo')->debug(__FUNCTION__ . " in " . __FILE__ . " at " . __LINE__, 10);
                $found = $all;
                foreach ($this->getConditions() as $cond) {
                    mage::helper('giftpromo')->debug(__FUNCTION__ . " in " . __FILE__ . " at " . __LINE__, 10);
                    $validated = $cond->validate($item);
                    // if we have a false, and the parent object
                    // is a configurable, then force validation of the selected
                    // child product
                    if ($validated == false
                        && $item->getProductType()
                        == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                    ) {
                        $item->getProduct()->setSku($item->getSku());
                        $validated = $cond->validate($item);
                    }
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
                $object->setGiftTriggerItem($item);

                return true;
            } // not found and we're making sure it doesn't exist
            elseif (!$found && !$true) {
                $object->setGiftTriggerItem($item);

                return true;
            }
            //if nothing else validate, but one of the items had previously validated (thus is still actually valid)
            //then force a true with this item as the validated item, else it will get removed in the validator.
            if ($forcedValidItem) {
                $object->setGiftTriggerItem($forcedValidItem);

                return true;
            }
            mage::helper('giftpromo')->debug(__FUNCTION__ . " in " . __FILE__ . " at " . __LINE__, 10);

            return false;
        }
    }

    /**
     * validate with a count
     *
     * @param Varien_Object $object Quote
     *
     * @return boolean
     */
    public function validateCount(Varien_Object $object)
    {
        $counted = array();
        $allVisibleItems = mage::helper('giftpromo')->getAllVisibleItems($object);
        foreach ($allVisibleItems as $item) {
            if (Mage::helper('giftpromo')->testGiftTypeCode($item->getProductType())) {
                continue;
            }
            foreach ($this->getConditions() as $cond) {
                $validated = $cond->validate($item);
                if ($validated) {
                    if (!array_key_exists($item->getProductId(), $counted)) {
                        $counted[$item->getProductId()] = $item;
                    }
                }
            }
        }

        return $counted;
    }


}
