<?php

/**
 * The main tab in promo rules
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Prepare content for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('giftpromo')->__('Rule Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('giftpromo')->__('Rule Information');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare the form
     *
     * @return object
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('current_giftpromo_promo_rule');
        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset(
            'base_fieldset', array('legend' => Mage::helper('giftpromo')->__('General Information'))
        );

        if ($model->getId()) {
            $fieldset->addField(
                'rule_id', 'hidden', array(
                    'name' => 'rule_id',
                )
            );
        }

        $fieldset->addField(
            'is_active', 'select', array(
                'label' => Mage::helper('giftpromo')->__('Status'),
                'title' => Mage::helper('giftpromo')->__('Status'),
                'name' => 'is_active',
                'required' => true,
                'options' => array(
                    '1' => Mage::helper('giftpromo')->__('Active'),
                    '0' => Mage::helper('giftpromo')->__('Inactive'),
                ),
            )
        );

        $fieldset->addField(
            'rule_name', 'text', array(
                'name' => 'rule_name',
                'label' => Mage::helper('giftpromo')->__('Rule Name'),
                'title' => Mage::helper('giftpromo')->__('Rule Name'),
                'required' => true,
                'note' => Mage::helper('giftpromo')->__(
                    'You can use the placeholder {{PRICE:XXX}} where xxx is the base currency value, without any formatting.
                    <br/>The placeholder will automaticlaly be price formatted, and currency converted.<br/>
                    Example: {{PRICE:200}}, will display $200.00 if base currency is USD, and AUD230.00 if AUD is selected store currency'
                )
            )
        );

        $fieldset->addField(
            'description', 'textarea', array(
                'name' => 'description',
                'label' => Mage::helper('giftpromo')->__('Description'),
                'title' => Mage::helper('giftpromo')->__('Description'),
                'style' => 'height: 100px;',
                'note' => Mage::helper('giftpromo')->__(
                    'You can use the description as the add to cart heading on selectable gifts.
            Simply place the placeholder text {RULE_DESCRIPTION} as the field value in the admin configuration "Message in cart for selectable gifts"
            <br/>Refer to System->Configuration->Giftpromotions->Cart settings'
                ),
            )
        );

        $fieldset->addField(
            'add_to_cart_message', 'textarea', array(
                'name' => 'add_to_cart_message',
                'label' => Mage::helper('giftpromo')->__('Add To Cart Message'),
                'title' => Mage::helper('giftpromo')->__('Add To Cart Message'),
                'style' => 'height: 100px;',
                'note' => Mage::helper('giftpromo')->__(
                    'The message to use when adding a promotion item to the cart.<br/>
                     If left blank, the default message of "{PRODUCT_NAME} was added to your shopping cart." will be used<br/>
                     You can use a placeholder of {PRODUCT_NAME} which will display the product name.<br/>
                     You can suppress any add to cart message using {NONE} in the field.'
                ),
            )
        );

        $fieldset->addField(
            'qualify_message', 'textarea', array(
                'name' => 'qualify_message',
                'label' => Mage::helper('giftpromo')->__('Checkout Gift Qualification Message'),
                'title' => Mage::helper('giftpromo')->__('Checkout Gift Qualification Message'),
                'style' => 'height: 100px;',
                'note' => Mage::helper('giftpromo')->__(
                    'The message to use when noting to customer that they qualify for a promotion, whilst in checkout.<br/>
                     Will be affixed with the go to cart link.<br/>
                     If left blank, a generic message will be used.<br/>
                     use "{NO MESSAGE}" to exclude from noting qualification in checkout for this promotion'
                ),
            )
        );

        if (!$model->getId()) {
            $model->setData('is_active', '1');
        }

        if (Mage::app()->isSingleStoreMode()) {
            $websiteId = Mage::app()->getStore(true)->getWebsiteId();
            $fieldset->addField(
                'website_ids', 'hidden', array(
                    'name' => 'website_ids[]',
                    'value' => $websiteId
                )
            );
            $model->setWebsiteIds($websiteId);
        } else {
            if (mage::helper('giftpromo')->isPre16()) {
                $fieldset->addField(
                    'website_ids', 'multiselect', array(
                        'name' => 'website_ids[]',
                        'label' => Mage::helper('catalogrule')->__('Websites'),
                        'title' => Mage::helper('catalogrule')->__('Websites'),
                        'required' => true,
                        'values' => Mage::getSingleton('adminhtml/system_config_source_website')->toOptionArray(),
                    )
                );
            } else {

                $field = $fieldset->addField(
                    'website_ids', 'multiselect', array(
                        'name' => 'website_ids[]',
                        'label' => Mage::helper('giftpromo')->__('Websites'),
                        'title' => Mage::helper('giftpromo')->__('Websites'),
                        'required' => true,
                        'values' => Mage::getSingleton('adminhtml/system_store')->getWebsiteValuesForForm()
                    )
                );
                $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
                $field->setRenderer($renderer);
            }
        }

        $customerGroups = Mage::getResourceModel('customer/group_collection')->load()->toOptionArray();
        $found = false;

        foreach ($customerGroups as $group) {
            if ($group['value'] == 0) {
                $found = true;
            }
        }
        if (!$found) {
            array_unshift(
                $customerGroups, array(
                    'value' => 0,
                    'label' => Mage::helper('giftpromo')->__('NOT LOGGED IN')
                )
            );
        }

        $fieldset->addField(
            'customer_ids', 'multiselect', array(
                'name' => 'customer_ids[]',
                'label' => Mage::helper('giftpromo')->__('Customer Groups'),
                'title' => Mage::helper('giftpromo')->__('Customer Groups'),
                'required' => true,
                'values' => Mage::getResourceModel('customer/group_collection')->toOptionArray(),
            )
        );

        $couponTypeField = $fieldset->addField(
            'coupon_type', 'select', array(
                'name' => 'coupon_type',
                'label' => Mage::helper('giftpromo')->__('Coupon'),
                'required' => true,
                'options' => Mage::getModel('giftpromo/promo_rule')->getCouponTypes(),
            )
        );

        $couponCodeField = $fieldset->addField(
            'coupon_code', 'text', array(
                'name' => 'coupon_code',
                'label' => Mage::helper('giftpromo')->__('Coupon Code'),
                'required' => true,
            )
        );

        if (!mage::helper('giftpromo')->isPre16()) {
            $autoGenerationCheckbox = $fieldset->addField(
                'use_auto_generation', 'checkbox', array(
                    'name' => 'use_auto_generation',
                    'label' => Mage::helper('giftpromo')->__('Use Auto Generation'),
                    'note' => Mage::helper('giftpromo')->__(
                        'If you select and save the rule you will be able to generate multiple coupon codes.'
                    ),
                    'onclick' => 'handleCouponsTabContentActivity(); handleCouponGenClick(this); this.value = this.checked ? 1 : 0;',
                    'checked' => (int)($model->getUseAutoGeneration() == 1) ? 'checked' : '',
                    'default' => 0
                )
            );

            $autoGenerationCheckbox->setRenderer(
                $this->getLayout()->createBlock('giftpromo/adminhtml_promo_rule_edit_tab_main_renderer_checkbox')
            );

            $fieldset->addField(
                'generate_coupon', 'select', array(
                    'label' => Mage::helper('giftpromo')->__('Generate (and email) a coupon'),
                    'title' => Mage::helper('giftpromo')->__('Generate (and email) a coupon'),
                    'name' => 'generate_coupon',
                    'options' => array(
                        '0' => Mage::helper('giftpromo')->__('No'),
                        ProxiBlue_GiftPromo_Model_Coupons::COUPON_ON_ACCOUNT_CREATE => Mage::helper('giftpromo')->__('On Account Create'),
                        ProxiBlue_GiftPromo_Model_Coupons::COUPON_ON_NEWSLETTER => Mage::helper('giftpromo')->__('On NewsLetter Subscription'),
                        ProxiBlue_GiftPromo_Model_Coupons::COUPON_ON_BDAY => Mage::helper('giftpromo')->__('On Customer Birthday'),
                    ),
                    'note' => 'Generate, and email, a coupon upon selected action',
                    'onclick' => 'handleGenerateOption(this);'
                )
            );

            $fieldset->addField(
                'coupon_gen_to', 'date', array(
                    'name' => 'coupon_gen_to',
                    'label' => Mage::helper('giftpromo')->__('Last day coupon can be generated'),
                    'title' => Mage::helper('giftpromo')->__('Last day coupon can be generated'),
                    'image' => $this->getSkinUrl('images/grid-cal.gif'),
                    'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
                    'format' => $dateFormatIso,
                    'note' => 'The last day coupons can be generated. After this date coupons can still be redeemed if the gifting is still active',

                )
            );

            $fieldset->addField(
                'coupon_email_code', 'select', array(
                    'label' => Mage::helper('giftpromo')->__('Email template for coupon generation'),
                    'title' => Mage::helper('giftpromo')->__('Email template for coupon generation'),
                    'name' => 'coupon_email_code',
                    'values' => Mage::getSingleton('adminhtml/system_config_source_email_template')->toOptionArray(),
                    'note' => 'Use this template for email send from coupon generation'
                )
            );

            $fieldset->addField(
                'coupon_prefix', 'text', array(
                    'name' => 'coupon_prefix',
                    'label' => Mage::helper('giftpromo')->__('Coupon Prefix'),
                    'note' => Mage::helper('giftpromo')->__(
                        'Prefix to append to generated coupon codes'
                    ),
                )
            );

            $fieldset->addField(
                'coupon_suffix', 'text', array(
                    'name' => 'coupon_suffix',
                    'label' => Mage::helper('giftpromo')->__('Coupon Suffix'),
                    'note' => Mage::helper('giftpromo')->__(
                        'Suffix to prepend to generated coupon codes'
                    ),
                )
            );

            $fieldset->addField(
                'coupon_length', 'text', array(
                    'name' => 'coupon_length',
                    'label' => Mage::helper('giftpromo')->__('Coupon Length'),
                    'note' => Mage::helper('giftpromo')->__(
                        'Coupon Code Length'
                    ),
                )
            );

            $couponHelper = Mage::helper('salesrule/coupon');

            $fieldset->addField(
                'coupon_format', 'select', array(
                    'label' => Mage::helper('salesrule')->__('Coupon Format'),
                    'name' => 'coupon_format',
                    'options' => $couponHelper->getFormatsList(),
                    'required' => false,
                    'value' => $couponHelper->getDefaultFormat()
                )
            );


        }


        $usesPerCouponField = $fieldset->addField(
            'uses_per_coupon', 'text', array(
                'name' => 'uses_per_coupon',
                'label' => Mage::helper('giftpromo')->__('Uses per Coupon'),
                'note' => Mage::helper('giftpromo')->__(
                    'The number of times a coupon can be used. 0 means unlimited.'
                ),
            )
        );

        $couponFieldUsagePerCustomer = $fieldset->addField(
            'coupon_uses_per_customer', 'text', array(
                'name' => 'coupon_uses_per_customer',
                'label' => Mage::helper('giftpromo')->__('Uses per Coupon per Customer'),
                'note' => Mage::helper('giftpromo')->__(
                    'The number of times a coupon can be used by a logged in customer.
            0 means unlimited.'
                ),
            )
        );

        $fieldset->addField(
            'uses_per_customer', 'text', array(
                'name' => 'uses_per_customer',
                'label' => Mage::helper('giftpromo')->__('Uses per Customer'),
                'note' => Mage::helper('giftpromo')->__(
                    'The number of times a logged in customer can use the rule. 0 means unlimited.'
                ),
            )
        );

        $fieldset->addField(
            'usage_limit', 'text', array(
                'name' => 'usage_limit',
                'label' => Mage::helper('giftpromo')->__('Usage Limit'),
                'note' => Mage::helper('giftpromo')->__(
                    'The number of times this rule can be used. 0 means unlimited.'
                ),
            )
        );

        $fieldset->addField(
            'from_date', 'date', array(
                'name' => 'from_date',
                'label' => Mage::helper('giftpromo')->__('From Date'),
                'title' => Mage::helper('giftpromo')->__('From Date'),
                'image' => $this->getSkinUrl('images/grid-cal.gif'),
                'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
                'format' => $dateFormatIso
            )
        );
        $fieldset->addField(
            'to_date', 'date', array(
                'name' => 'to_date',
                'label' => Mage::helper('giftpromo')->__('To Date'),
                'title' => Mage::helper('giftpromo')->__('To Date'),
                'image' => $this->getSkinUrl('images/grid-cal.gif'),
                'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
                'format' => $dateFormatIso
            )
        );

        $fieldset->addField(
            'uses_per_rule', 'text', array(
                'name' => 'uses_per_rule',
                'label' => Mage::helper('giftpromo')->__('Uses per Order'),
                'note' => Mage::helper('giftpromo')->__(
                    'The number of times this rule can be used to add item(s) to the cart.<br/>
                 Set to the value of items gifted, or multiples thereof.<br/>
                 Example: If you gift 2 items, and want rule used only once, set to 2.<br/>
                 If you want rule used twice, set to 4, three times, set to 6 etc. (multiples of gift items)<br/>
                 0 means unlimited.'
                ),
            )
        );

        $fieldset->addField(
            'block_rules', 'text', array(
                'name' => 'block_rules',
                'label' => Mage::helper('giftpromo')->__('Block Shopping Cart Rules'),
                'note' => Mage::helper('giftpromo')->__(
                    'Comma separated list of Shipping Cart Rule Ids that may not apply if this promotion has
                    applied to cart.<br/>Enter {ALL} to block ALL shopping cart rules!
                    <br/>This feature does not work with Upgrade Rule!'
                ),
            )
        );

        $fieldset->addField(
            'block_rules_message', 'textarea', array(
                'name' => 'block_rules_message',
                'label' => Mage::helper('giftpromo')->__('Message if Cart Rule is blocked'),
                'title' => Mage::helper('giftpromo')->__('Message if Cart Rule is blocked'),
                'style' => 'height: 100px;',
                'note' => Mage::helper('giftpromo')->__(
                    'If you block a SHopping Cart Rule, display this message to let the user know why the
                    rule is not valid / no longer valid.<br/>
                    Placeholders: {SHOPPING_CART_RULE_NAME} and {GIFT_RULE_NAME}<br/>
                    If no message specified, none will be displayed.'
                ),
            )
        );


        $fieldset->addField(
            'sort_order', 'text', array(
                'name' => 'sort_order',
                'label' => Mage::helper('giftpromo')->__('Priority'),
                'note' => 'Higher priority rules run first, then the lower priority rules run after.<br/>Rules set with no, or 0 priority level will always run first!'
            )
        );

        $fieldset->addField(
            'stop_rules_processing', 'select', array(
                'label' => Mage::helper('giftpromo')->__('Stop Further Gift Rules Processing'),
                'title' => Mage::helper('giftpromo')->__('Stop Further Gift Rules Processing'),
                'name' => 'stop_rules_processing',
                'options' => array(
                    '1' => Mage::helper('giftpromo')->__('Yes'),
                    '0' => Mage::helper('giftpromo')->__('No'),
                ),
                'note' => 'If yes, rules with lower priority will not run, and their products removed from cart.',
            )
        );

        $fieldset->addField(
            'keep_validated_on_stop', 'select', array(
                'label' => Mage::helper('giftpromo')->__('Keep validate gifts after stop processing'),
                'title' => Mage::helper('giftpromo')->__('Keep validate gifts after stop processing'),
                'name' => 'keep_validated_on_stop',
                'required' => false,
                'options' => array(
                    '1' => Mage::helper('giftpromo')->__('Yes'),
                    '0' => Mage::helper('giftpromo')->__('No'),
                ),
                'note' => 'If yes, rules with lower priority will not have their cart items removed, if a higher
                priority rule, with selectable gifts, validates. This restores priority behaviour
                as was prior to version 2.26. Most people will not need to enable this option.',
            )
        );

        $fieldset->addField(
            'icon_file', 'image', array(
                'label' => Mage::helper('giftpromo')->__('Gift Icon Image'),
                'required' => false,
                'name' => 'icon_file',
                'note' => 'Set the gift icon image for gift products from this rule.
                Will have precedence over Global icon, but Product level icons has precedence over this value.'
            )
        );

        $form->setValues($model->getData());

        $this->setForm($form);

        if (!mage::helper('giftpromo')->isPre16()) {
            $this->setChild(
                'form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
                ->addFieldMap($couponTypeField->getHtmlId(), $couponTypeField->getName())
                ->addFieldMap($couponCodeField->getHtmlId(), $couponCodeField->getName())
                ->addFieldMap($autoGenerationCheckbox->getHtmlId(), $autoGenerationCheckbox->getName())
                ->addFieldMap($usesPerCouponField->getHtmlId(), $usesPerCouponField->getName())
                ->addFieldMap($couponFieldUsagePerCustomer->getHtmlId(), $couponFieldUsagePerCustomer->getName())
                ->addFieldDependence(
                    $couponCodeField->getName(), $couponTypeField->getName(),
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                )
                ->addFieldDependence(
                    $autoGenerationCheckbox->getName(), $couponTypeField->getName(),
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                )
                ->addFieldDependence(
                    $usesPerCouponField->getName(), $couponTypeField->getName(),
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                )
                ->addFieldDependence(
                    $couponFieldUsagePerCustomer->getName(), $couponTypeField->getName(),
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                )
            );
        } else {
            // field dependencies
            $this->setChild(
                'form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
                ->addFieldMap($couponTypeField->getHtmlId(), $couponTypeField->getName())
                ->addFieldMap($couponCodeField->getHtmlId(), $couponCodeField->getName())
                ->addFieldMap($usesPerCouponField->getHtmlId(), $usesPerCouponField->getName())
                ->addFieldMap($couponFieldUsagePerCustomer->getHtmlId(), $couponFieldUsagePerCustomer->getName())
                ->addFieldDependence(
                    $couponCodeField->getName(), $couponTypeField->getName(),
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                )
                ->addFieldDependence(
                    $usesPerCouponField->getName(), $couponTypeField->getName(),
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                )
                ->addFieldDependence(
                    $couponFieldUsagePerCustomer->getName(), $couponTypeField->getName(),
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                )
            );
        }

        Mage::dispatchEvent('adminhtml_giftpromo_promo_rule_edit_tab_main_prepare_form', array('form' => $form));

        return parent::_prepareForm();
    }

}
