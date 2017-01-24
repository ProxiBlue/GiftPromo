<?php

/**
 * Renderer for gift Gift Message column (also renders Gift Label)
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftedMessage
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        return $this->renderLabel($row) . "<br/>" . $this->renderMessage($row);
    }

    private function renderLabel(Varien_Object $row)
    {
        $truncateLength = 50;
        $text = $this->escapeHtml(Mage::helper('core/string')->truncate($row->getGiftedLabel(), $truncateLength));

        return
            '<input style="margin-left: 5px; margin-bottom: 5px; width:' . $this->getColumn()->getLabelWidth()
            . 'px !important;" type="text" class="input-text input-gifted-label"'
            . '" name="gifted_label"'
            . ' value="' . $text . '"/>';
    }

    /**
     * Render contents as a long text
     *
     * Text will be truncated as specified in string_limit, truncate or 250 by default
     * Also it can be html-escaped and nl2br()
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    private function renderMessage(Varien_Object $row)
    {
        $truncateLength = 250;
        $text = $this->escapeHtml(Mage::helper('core/string')->truncate($row->getGiftedMessage(), $truncateLength));

        return
            '<div style="white-space: nowrap"><input style="margin-left: 5px;width:' . $this->getColumn()
                ->getMessageWidth() . 'px !important;" type="text" class="input-text input-gifted-message"'
            . '" name="gifted_message"'
            . ' value="' . $text . '"/></div>';
    }

}
