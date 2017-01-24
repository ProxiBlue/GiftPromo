<?php

/**
 * Subtotal rule condition
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Grandtotal
    extends ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Subtotal
{

    const TOTAL_TYPE = 'Grand Total';

    public function __construct()
    {
        Mage_Rule_Model_Condition_Abstract::__construct();
        $this->setType('giftpromo/promo_rule_condition_gandtotal')
            ->setValue(null)
            ->setConditions(array())
            ->setActions(array());
    }


    /**
     * Render this as html
     *
     * @return string
     */
    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            Mage::helper('giftpromo')->__(
                "If the %s %s %s", self::TOTAL_TYPE,
                $this->getOperatorElement()->getHtml(),
                $this->getValueElement()->getHtml()
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
        $this->setQuote($object);
        $totals = $object->getTotals();
        // adjust totals and remove any current gift items price from the total
        $currentGiftItems = Mage::helper('giftpromo')->getAllGiftBasedCartItems();
        if (is_array($totals) && array_key_exists('grand_total', $totals)) {
            $grandtotal = $totals['grand_total']->getValue();
        } else {
            $grandtotal = $object->getGrandtotal();
        }
        if (is_array($totals) && array_key_exists('subtotal', $totals)) {
            $subtotal = $totals['subtotal']->getValue();
        } else {
            $subtotal = $object->getSubtotal();
        }
        if ($grandtotal == 0) {
            $grandtotal = $subtotal;
        }
        foreach ($currentGiftItems as $giftItem) {
            $grandtotal = $grandtotal - $giftItem->getPrice();
        }

        return $this->validateAttribute($grandtotal);
    }

}
