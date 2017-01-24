<?php

/**
 * Renderer fro gift qty max column
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftQtyRate
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Number
{
    protected $_defaultWidth = 100;

    /**
     * Returns value of the row
     *
     * @param Varien_Object $row
     *
     * @return mixed|string
     */
    public function render(Varien_Object $row)
    {
        return
            '<input style="width:80px !important;" type="hidden" class="input-text input-gifted-product-qty-sku"'
            . $this->getColumn()->getValidateClass()
            . '" name="rate_product_qty_sku"
                . value="' . $row->getGiftedRateProductQtySku() . '"/>'
            //. " : "
            . '<input style="width:20px !important;" type="text" class="input-text input-gifted-product-qty"'
            . $this->getColumn()->getValidateClass()
            . '" name="rate_product_qty"
                . value="' . $row->getGiftedRateProductQty() . '"/>'
            . " : "
            . '<input style="width:20px !important;" type="text" class="input-text input-gifted-qty-rate"'
            . $this->getColumn()->getValidateClass()
            . '" name="rate_gift_rate"
                . value="' . $row->getGiftedRateGiftRate() . '"/>';

    }

    /**
     * Renders CSS
     *
     * @return string
     */
    public function renderCss()
    {
        return parent::renderCss() . ' a-left';
    }

}
