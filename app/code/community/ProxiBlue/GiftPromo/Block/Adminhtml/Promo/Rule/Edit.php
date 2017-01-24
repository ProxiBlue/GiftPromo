<?php

/**
 * Edit gift products promo rule
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Block group name
     *
     * @var string
     */
    protected $_blockGroup = 'giftpromo';

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Continue" button
     */
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_promo_rule';


        parent::__construct();

        $this->_addButton(
            'save_and_continue_edit', array(
            'class'   => 'save',
            'label'   => Mage::helper('giftpromo')->__('Save and Continue Edit'),
            'onclick' => 'editForm.submit($(\'edit_form\').action + \'back/edit/\')',
        ), 10
        );

        $this->setData('form_action_url', $this->getUrl('*/*/save'));
    }

    /**
     * Getter for form header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        $rule = Mage::registry('current_giftpromo_promo_rule');
        if ($rule->getRuleId()) {
            return Mage::helper('giftpromo')->__("Edit Rule '%s'", $this->escapeHtml($rule->getRuleName()));
        } else {
            return Mage::helper('giftpromo')->__('New Rule');
        }
    }

    /**
     * Retrieve products JSON
     *
     * @return string
     */
    public function getProductsJson()
    {
        return '{}';
    }
}
