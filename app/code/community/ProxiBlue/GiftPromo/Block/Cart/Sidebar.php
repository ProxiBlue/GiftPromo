<?php

class ProxiBlue_GiftPromo_Block_Cart_Sidebar extends Mage_Checkout_Block_Cart_Sidebar
{
    /**
     * Check if can apply msrp to totals
     *
     * @return bool
     */
    public function canApplyMsrp()
    {
        if (mage::registry('giftpromo_busy') != true) {
            if (!$this->getQuote()->hasCanApplyMsrp() && Mage::helper('catalog')->isMsrpEnabled()) {
                $this->getQuote()->collectTotals();
            }
        }
        return $this->getQuote()->getCanApplyMsrp();
    }

}
