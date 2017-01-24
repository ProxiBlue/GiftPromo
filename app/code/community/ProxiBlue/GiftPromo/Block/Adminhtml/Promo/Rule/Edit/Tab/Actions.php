<?php

/**
 * Promo rule actions tab
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Actions extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('giftpromo/promo/actions.phtml');
    }

    /**
     * Prepare content for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('giftpromo')->__('Gift Products');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('giftpromo')->__('Gift Products');
    }

    /**
     * Returns status flag about this tab can be showen or not
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
     * Return JSON string of the selected products
     *
     * @return string
     */
    public function getProductsJson()
    {
        $model = Mage::registry('current_giftpromo_promo_rule');
        $products = array();
        parse_str($model->getGiftpromo(), $products);
        if (!empty($products)) {
            return Mage::helper('core')->jsonEncode($products);
        }

        return '{}';
    }

    /**
     * Attach the gift product grid to the layout as a child
     *
     * @return object
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid', $this->getLayout()->createBlock(
            'giftpromo/adminhtml_promo_rule_edit_tab_actions_giftpromo_grid',
            'giftpromo_promo_rule_edit_tab_actions_giftpromo_grid'
        )
        );

        return parent::_prepareLayout();
    }

    /**
     * Prepare the form for the actions tab
     *
     * @return object
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('current_giftpromo_promo_rule');

        //$form = new Varien_Data_Form(array('id' => 'edit_form1', 'action' => $this->getData('action'), 'method' => 'post'));
        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset(
            'action_fieldset', array('legend' => Mage::helper('giftpromo')->__('Multiple Gift Options'))
        );

        $fieldset->addField(
            'allow_gift_selection', 'select', array(
                'label'   => Mage::helper('giftpromo')->__('Allow Gift Selection'),
                'title'   => Mage::helper('giftpromo')->__('Allow Gift Selection'),
                'name'    => 'allow_gift_selection',
                'options' => array(
                    '1' => Mage::helper('giftpromo')->__('Yes'),
                    '0' => Mage::helper('giftpromo')->__('No'),
                ),
                'note'    => 'If you are gifting any <strong>configurable</strong> or <strong>bundle</strong> product types,
                          this option must be set to YES.<br/>
                          If you are using a <strong>Cheapest Free Rule</strong>, this must be set to NO'
            )
        );

        $fieldset->addField(
            'allow_gift_selection_count', 'text', array(
                'label'   => Mage::helper('giftpromo')->__('Total gifts allowed to select'),
                'title'   => Mage::helper('giftpromo')->__('Total gifts allowed to select'),
                'name'    => 'allow_gift_selection_count',
                'default' => 1,
                'note'    => 'Limit the number of gifts that can be selected.<br/>Default is 1<br/>Use -1 to match cart item quantity'
            )
        );

        $fieldset->addField(
            'gift_add_product_multi', 'checkbox', array(
                'label'   => Mage::helper('giftpromo')->__('Allow a selectable gift to be added multiple times'),
                'title'   => Mage::helper('giftpromo')->__('Allow a selectable gift to be added multiple times'),
                'name'    => 'gift_add_product_multi',
                'onclick' => "this.value = this.checked ? 1 : 0;",
                'note'    => 'If gifts are selectable, allow the same gift to be added multiple times, counting towards the total of allowed to select'

            )
        );

        $fieldset->addField(
            'gift_added_product', 'checkbox', array(
                'label'   => Mage::helper('giftpromo')->__('Gift Products Added To Cart'),
                'title'   => Mage::helper('giftpromo')->__('Gift Products Added To Cart'),
                'name'    => 'gift_added_product',
                'onclick' => "this.value = handleGiftAddedProductClick(this)",
                'note'    => 'Buy X get X free. Any product added to cart will also be added as a gift.<br/>
                              Ratio will be 1:1<br/>Can use Selectable Lists'

            )
        );

        $fieldset->addField(
            'gift_added_product_max', 'text', array(
                'label' => Mage::helper('giftpromo')->__('Gift Products Added To Cart Max Qty'),
                'title' => Mage::helper('giftpromo')->__('Gift Products Added To Cart Max Qty'),
                'name'  => 'gift_added_product_max',
                'note'  => 'Max Qty allowed for Buy X get X free.'

            )
        );

        $fieldset->addField(
            'gift_add_normal_product', 'checkbox', array(
                'label'   => Mage::helper('giftpromo')->__('Use normal product types'),
                'title'   => Mage::helper('giftpromo')->__('Use normal product types'),
                'name'    => 'gift_add_normal_product',
                'onclick' => "this.value = handleGiftProductTypeClick(this)",
                'note'    => 'Only use if you know what you are doing.<br/>This option will add the products to cart, as NORMAL magento products, not as gifts.<br/>Gifting rules will not consider the products as gifts, and all rules will apply again.'

            )
        );

        $fieldset->addField(
            'block_qty_changes', 'select', array(
                'label'   => Mage::helper('giftpromo')->__('Block QTY Changes in cart'),
                'title'   => Mage::helper('giftpromo')->__('Block QTY Changes in cart'),
                'name'    => 'block_qty_changes',
                'options' => array(
                    '1' => Mage::helper('giftpromo')->__('Yes'),
                    '0' => Mage::helper('giftpromo')->__('No'),
                ),
                'note'    => 'Normally if a gift is not 0 priced, it can have qty changed. Block this, so no gift can have qty changed.'
            )
        );

        $fieldset->addField(
            'giftpromo', 'hidden', array(
                'name' => 'giftpromo',
            )
        );

        $form->getElement('gift_added_product')->setIsChecked($model->getGiftAddedProduct());
        $form->getElement('gift_add_product_multi')->setIsChecked($model->getGiftAddProductMulti());
        $form->getElement('gift_add_normal_product')->setIsChecked($model->getGiftAddNormalProduct());

        $form->setValues($model->getData());

        if ($model->isReadonly()) {
            foreach ($fieldset->getElements() as $element) {
                $element->setReadonly(true, true);
            }
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

}
