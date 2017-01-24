<?php

/**
 * Grid select input column renderer
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden <sales@proxiblue.com.au>
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_Select
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Select
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
        $name = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
        $disabled = ($row->getAddMethod() == 0) ? '' : 'disabled';
        $html = '<select id="remoaveable-' . $row->getId() . '" class="can-delete" name="' . $this->escapeHtml($name)
            . '" ' . $this->getColumn()->getValidateClass() . '>';
        $value = $row->getData($this->getColumn()->getIndex());
        foreach ($this->getColumn()->getOptions() as $val => $label) {
            if ($val != 0) {
                $disabled = '';
            }
            $selected = (($val == $value && (!is_null($value))) ? ' selected="selected"' : '');
            $html .= '<option ' . $disabled . ' value="' . $this->escapeHtml($val) . '"' . $selected . '>';
            $html .= $this->escapeHtml($label) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

}
