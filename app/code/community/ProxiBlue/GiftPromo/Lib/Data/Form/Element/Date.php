<?php

/**
 * Data Form element Date
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 *
 * */
class ProxiBlue_GiftPromo_Lib_Data_Form_Element_Date extends Varien_Data_Form_Element_Date
{

    /**
     * Output the input field and assign calendar instance to it.
     * In order to output the date:
     * - the value must be instantiated (Zend_Date)
     * - output format must be set (compatible with Zend_Date)
     *
     * @return string
     */
    public function getElementHtml()
    {
        $this->addClass('input-text');

        $html = sprintf(
            '<input name="%s" id="%s" value="%s" %s style="width:110px !important;" />'
            . ' <img src="%s" alt="" class="v-middle" id="%s_trig" title="%s" style="%s" />',
            $this->getName(), $this->getHtmlId(), $this->_escape($this->getValue()),
            $this->serialize($this->getHtmlAttributes()),
            $this->getImage(), $this->getHtmlId(), 'Select Date', ($this->getDisabled() ? 'display:none;' : '')
        );
        $outputFormat = $this->getFormat();
        if (empty($outputFormat)) {
            throw new Exception(
                'Output format is not specified. Please, specify "format" key in constructor, or set it using setFormat().'
            );
        }
        $displayFormat = Varien_Date::convertZendToStrFtime($outputFormat, true, (bool)$this->getTime());

        $html .= sprintf(
            '
            <script type="text/javascript">
            //<![CDATA[
                Calendar.setup({
                    inputField: "%s",
                    ifFormat: "%s",
                    showsTime: %s,
                    button: "%s_trig",
                    align: "Bl",
                    singleClick : true,
                    eventName : "mousedown",
                });
            //]]>
            </script>',
            $this->getHtmlId(), $displayFormat,
            $this->getTime() ? 'true' : 'false', $this->getHtmlId()
        );

        $html .= $this->getAfterElementHtml();

        return $html;
    }
}
