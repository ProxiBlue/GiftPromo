<?php

/**
 * Promo rules edit form
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('giftpromo_promo_rule_form');
        $this->setTitle(Mage::helper('giftpromo')->__('Rule Information'));
    }

    /**
     * Prepare the form
     *
     * @return object
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array('id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post', 'enctype' => 'multipart/form-data')
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }


}
