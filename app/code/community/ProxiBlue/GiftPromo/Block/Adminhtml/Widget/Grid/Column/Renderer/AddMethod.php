<?php

/**
 * Grid select input column renderer
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden <sales@proxiblue.com.au>
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_AddMethod
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
        $html = '<select id="add-method-' . $row->getId() . '" onChange="setRemoveable(' . $row->getId()
            . ',this.options[this.selectedIndex].value)" class="add-method" name="' . $this->escapeHtml($name) . '" '
            . $this->getColumn()->getValidateClass() . '>';
        $value = $row->getData($this->getColumn()->getIndex());
        $options = $this->getColumn()->getOptions();
        foreach ($options as $val => $label) {
            if ($row->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE
                && $label == 'Direct to cart'
            ) {
                continue;
            }
            $selected = (($val == $value && (!is_null($value))) ? ' selected="selected"' : '');
            $html .= '<option value="' . $this->escapeHtml($val) . '"' . $selected . '>';
            $html .= $this->escapeHtml($label) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

}
