<?php

/**
 * Renderer for Gift price column
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftedPrice
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Currency
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
        $value = $this->_getValue($row);
        if (!$value) {
            $value = 0;
        }
        if(strpos($value,'%') !== false) {
            // percentage gifting
            $symbol = '';
        } else {
            $currency_code = $this->_getCurrencyCode($row);
            if (!$currency_code) {
                $currency_code = '';
            }
            $value = floatval($value) * $this->_getRate($row);
            $value = sprintf("%.2f", $value);
            //$value = Mage::app()->getLocale()->currency($currency_code)->toCurrency($value);
            $currency = Mage::app()->getLocale()->currency($currency_code);
            $symbol = $currency->getSymbol();
        }
        return $symbol
        . '&nbsp;<input style="width:50px;" type="text" class="input-text input-gifted-price'
        . '" name="' . $this->getColumn()->getId()
        . '" value="' . $value . '" onBlur=" if( this.value > ' . $row->getPrice()
        . '){ alert(\'Gifted price cannot be more than the Original Price\'); this.value = ' . $row->getPrice()
        . '; }"/>';
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
        if(strpos($data,'%') !== false) {
            return $data;
        }
        if (!is_null($data)) {
            $value = $data * 1;
            $sign = (bool)(int)$this->getColumn()->getShowNumberSign() && ($value > 0) ? '+' : '';
            if ($sign) {
                $value = $sign . $value;
            }

            return $value ? $value : '0'; // fixed for showing zero in grid
        }

        return $this->getColumn()->getDefault();
    }

}
