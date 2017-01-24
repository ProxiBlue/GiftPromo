<?php

/**
 * Renderer for Number
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_StockQty
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
        return parent::renderCss() . ' a-right';
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
        if ($this->getColumn()->getEditable()) {
            $value = $this->_getValue($row);

            return
                ($this->getColumn()->getEditOnly() ? '' : ($value != '' ? '' : '&nbsp;'))
                . $this->_getInputValueElement($row);
        }

        return $this->_getValue($row);
    }

    /**
     * Returns value of the row
     *
     * @param Varien_Object $row
     *
     * @return mixed|string
     */
    protected function _getValue(Varien_Object $row)
    {
        $data = parent::_getValue($row);
        if (!is_null($data)) {
            if (!$row->getIsQtyDecimal()) {
                $data = intval($data);
            }
            $value = $data;
            $sign = (bool)(int)$this->getColumn()->getShowNumberSign() && ($value > 0) ? '+' : '';
            if ($sign) {
                $value = $sign . $value;
            }

            return $value ? $value : '0'; // fixed for showing zero in grid
        }

        return $this->getColumn()->getDefault();
    }

}
