<?php


class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_Checkbox
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Checkbox
{

    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     *
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $values = $this->getColumn()->getValues();
        $value = $row->getData($this->getColumn()->getIndex());
        if (is_array($values)) {
            $checked = in_array($value, $values) ? ' checked="checked"' : '';
        } else {
            $checked = ($value === $this->getColumn()->getValue()) ? ' checked="checked"' : '';
        }

        $disabledValues = $this->getColumn()->getDisabledValues();
        if (is_array($disabledValues)) {
            $disabled = in_array($value, $disabledValues) ? ' disabled="disabled"' : '';
        } else {
            $disabled = ($value === $this->getColumn()->getDisabledValue()) ? ' disabled="disabled"' : '';
        }

        $this->setDisabled($disabled);

        if ($this->getNoObjectId() || $this->getColumn()->getUseIndex()) {
            $v = $value;
        } else {
            $v = ($row->getId() != "") ? $row->getId() : $value;
        }

        return $this->_getHtml($v, $checked, $row);
    }

    /**
     * @param string $value   Value of the element
     * @param bool   $checked Whether it is checked
     *
     * @return string
     */
    protected function _getHtml($value, $checked, $row)
    {
        $html = '<input type="checkbox" ';
        $html .= 'name="' . $this->getColumn()->getFieldName() . '" ';
        $html .= 'data-type="' . $row->getTypeId() . '" ';
        $html .= 'value="' . $this->escapeHtml($value) . '" ';
        $html .= 'class="' . ($this->getColumn()->getInlineCss() ? $this->getColumn()->getInlineCss() : 'checkbox')
            . '"';
        $html .= $checked . $this->getDisabled() . '/>';

        return $html;
    }


}
