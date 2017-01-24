<?php

/**
 * Renderer fro gift qty max column
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftQtyMax
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
        $data = $this->_getValue($row);
        if (empty($data)) {
            $data = 0;
        }

        return
            '<input style="width:30px !important;" type="text" class="input-text input-gifted-max"'
            . $this->getColumn()->getValidateClass()
            . '" name="' . $this->getColumn()->getId()
            . '" value="' . $data . '"/> <br/>';

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
