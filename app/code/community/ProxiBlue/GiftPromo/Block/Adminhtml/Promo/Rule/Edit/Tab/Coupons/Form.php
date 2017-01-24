<?php

if (mage::helper('giftpromo')->isPre16()) {

    class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Coupons_Form extends Mage_Core_Block_Template
    {

    }

} else {

    /**
     * Coupons generation parameters form
     *
     */
    class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Coupons_Form
        extends Mage_Adminhtml_Block_Promo_Quote_Edit_Tab_Coupons_Form
    {

        /**
         * Prepare coupon codes generation parameters form
         *
         * @return Mage_Adminhtml_Block_Widget_Form
         */
        protected function _prepareForm()
        {
            $form = new Varien_Data_Form();

            /**
             * @var Mage_SalesRule_Helper_Coupon $couponHelper
             */
            $couponHelper = Mage::helper('salesrule/coupon');

            $model = Mage::registry('current_giftpromo_promo_rule');
            $ruleId = $model->getId();

            $form->setHtmlIdPrefix('coupons_');

            $gridBlock = $this->getLayout()->getBlock('giftpromo_promo_rule_edit_tab_coupons_grid');
            $gridBlockJsObject = '';
            if ($gridBlock) {
                $gridBlockJsObject = $gridBlock->getJsObjectName();
            }

            $fieldset = $form->addFieldset(
                'information_fieldset', array('legend' => Mage::helper('salesrule')->__('Coupons Information'))
            );
            $fieldset->addClass('ignore-validate');

            $fieldset->addField(
                'rule_id', 'hidden', array(
                    'name'  => 'rule_id',
                    'value' => $ruleId
                )
            );

            $fieldset->addField(
                'qty', 'text', array(
                    'name'     => 'qty',
                    'label'    => Mage::helper('salesrule')->__('Coupon Qty'),
                    'title'    => Mage::helper('salesrule')->__('Coupon Qty'),
                    'required' => true,
                    'class'    => 'validate-digits validate-greater-than-zero'
                )
            );

            $fieldset->addField(
                'length', 'text', array(
                    'name'     => 'length',
                    'label'    => Mage::helper('salesrule')->__('Code Length'),
                    'title'    => Mage::helper('salesrule')->__('Code Length'),
                    'required' => true,
                    'note'     => Mage::helper('salesrule')->__('Excluding prefix, suffix and separators.'),
                    'value'    => $couponHelper->getDefaultLength(),
                    'class'    => 'validate-digits validate-greater-than-zero'
                )
            );

            $fieldset->addField(
                'format', 'select', array(
                    'label'    => Mage::helper('salesrule')->__('Code Format'),
                    'name'     => 'format',
                    'options'  => $couponHelper->getFormatsList(),
                    'required' => true,
                    'value'    => $couponHelper->getDefaultFormat()
                )
            );

            $fieldset->addField(
                'prefix', 'text', array(
                    'name'  => 'prefix',
                    'label' => Mage::helper('salesrule')->__('Code Prefix'),
                    'title' => Mage::helper('salesrule')->__('Code Prefix'),
                    'value' => $couponHelper->getDefaultPrefix()
                )
            );

            $fieldset->addField(
                'suffix', 'text', array(
                    'name'  => 'suffix',
                    'label' => Mage::helper('salesrule')->__('Code Suffix'),
                    'title' => Mage::helper('salesrule')->__('Code Suffix'),
                    'value' => $couponHelper->getDefaultSuffix()
                )
            );

            $fieldset->addField(
                'dash', 'text', array(
                    'name'  => 'dash',
                    'label' => Mage::helper('salesrule')->__('Dash Every X Characters'),
                    'title' => Mage::helper('salesrule')->__('Dash Every X Characters'),
                    'note'  => Mage::helper('salesrule')->__('If empty no separation.'),
                    'value' => $couponHelper->getDefaultDashInterval(),
                    'class' => 'validate-digits'
                )
            );

            $idPrefix = $form->getHtmlIdPrefix();
            $generateUrl = $this->getGenerateUrl();

            $fieldset->addField(
                'generate_button', 'note', array(
                    'text' => $this->getButtonHtml(
                        Mage::helper('salesrule')->__('Generate'),
                        "generateCouponCodes('{$idPrefix}' ,'{$generateUrl}', '{$gridBlockJsObject}')", 'generate'
                    )
                )
            );

            $this->setForm($form);

            Mage::dispatchEvent('adminhtml_giftpromo_promo_edit_tab_coupons_form_prepare_form', array('form' => $form));

            return $this;
        }

    }

}
