<?php

/**
 * Edit gift products promo rule grid container
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_promo_rule';
        $this->_blockGroup = 'giftpromo';
        $this->_headerText = Mage::helper('giftpromo')->__('Manage Gift Promotions');
        $this->_addButtonLabel = Mage::helper('giftpromo')->__('Add New Promotion');
        parent::__construct();

    }
}
