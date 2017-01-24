<?php

if (mage::helper('giftpromo')->isPre16()) {
    class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Coupons extends Mage_Core_Block_Template
    {

    }

} else {

    /**
     * "Manage Coupons Codes" Tab
     *
     */
    class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Coupons
        extends Mage_Adminhtml_Block_Promo_Quote_Edit_Tab_Coupons
    {

        /**
         * Check whether we edit existing rule or adding new one
         *
         * @return bool
         */
        protected function _isEditing()
        {
            $giftRule = Mage::registry('current_giftpromo_promo_rule');

            return !is_null($giftRule->getRuleId());
        }

    }

}
