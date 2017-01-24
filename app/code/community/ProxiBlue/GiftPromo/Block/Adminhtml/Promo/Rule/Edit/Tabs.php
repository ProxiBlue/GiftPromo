<?php

/**
 * The main tab display container for promo rules
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('giftpromo_promo_rule_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('giftpromo')->__('Gift Promotions Rule'));
    }

    /**
     * inject the coupons block programatically as pre 1.6 do not have coupons generation
     */
    public function _beforeToHtml()
    {
        if (!mage::helper('giftpromo')->isPre16()) {
            $this->addTab('coupons_section', 'giftpromo_promo_rule_edit_tab_coupons');
        }
        parent::_beforeToHtml();
    }

}
