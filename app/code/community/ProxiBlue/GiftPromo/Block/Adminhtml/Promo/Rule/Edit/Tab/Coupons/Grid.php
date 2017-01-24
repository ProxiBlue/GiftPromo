<?php

if (mage::helper('giftpromo')->isPre16()) {

    class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Coupons_Grid extends Mage_Core_Block_Template
    {

    }

} else {

    /**
     * Coupon codes grid
     *
     */
    class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Coupons_Grid
        extends Mage_Adminhtml_Block_Promo_Quote_Edit_Tab_Coupons_Grid
    {

        /**
         * Constructor
         */
        public function __construct()
        {
            parent::__construct();
            $this->setId('couponCodesGrid');
            $this->setUseAjax(true);
        }

        /**
         * Prepare collection for grid
         *
         * @return Mage_Adminhtml_Block_Widget_Grid
         */
        protected function _prepareCollection()
        {
            $giftRule = Mage::registry('current_giftpromo_promo_rule');

            /**
             * @var Mage_SalesRule_Model_Resource_Coupon_Collection $collection
             */
            $collection = Mage::getResourceModel('giftpromo/promo_coupon_collection')
                ->addRuleToFilter($giftRule)
                ->addGeneratedCouponsFilter();

            $this->setCollection($collection);

            return call_user_func(array(get_parent_class(get_parent_class($this)), '_prepareCollection'));
        }

    }

}
