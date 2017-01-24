<?php

class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Edit_Tab_Main_Renderer_Checkbox
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Checkbox render function
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $checkbox = new Varien_Data_Form_Element_Checkbox($element->getData());
        $checkbox->setForm($element->getForm());

        $elementHtml = $checkbox->getElementHtml() . sprintf(
                '<label for="%s"><b>%s</b></label><p class="note">%s</p>',
                $element->getHtmlId(), $element->getLabel(), $element->getNote()
            );
        $html = '<td class="label">&nbsp;</td>';
        $html .= '<td class="value">' . $elementHtml . '</td>';

        return $html;
    }

}
