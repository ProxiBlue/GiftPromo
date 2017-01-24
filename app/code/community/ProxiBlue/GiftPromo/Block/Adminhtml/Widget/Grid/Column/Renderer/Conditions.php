<?php

/**
 * Conditions renderer for category/products grid rules
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_Conditions
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    protected $_defaultWidth = 100;

    /**
     * Renders CSS
     *
     * @return string
     */
    public function renderCss()
    {
        return parent::renderCss() . ' a-left';
    }

    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     *
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $ruleModel = Mage::getModel('giftpromo/promo_rule')->load($row->getRuleID());
        $html = '';
        if ($ruleModel->getId()) {
            try {
                $html = $ruleModel->getConditions()->asHtmlRecursive();
            } catch (Exception $e) {
                // null
            }
        }

        return $html;
    }

}
