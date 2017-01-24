<?php

/**
 * Customer Registration rule condition
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Customer_Conditions_Period
    extends Mage_Rule_Model_Condition_Abstract
{

    protected $_inputType = 'text';

    public function __construct()
    {
        Mage_Rule_Model_Condition_Abstract::__construct();
        $this->setType('giftpromo/promo_rule_condition_customer_conditions_period')
            ->setValue(null)
            ->setConditions(array())
            ->setActions(array());
    }

    /**
     * Load the given array into the object as rule data
     *
     * @param array  $arr
     * @param string $key
     *
     * @return \ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Subtotal
     */
    public function loadArray($arr, $key = 'conditions')
    {
        $this->setOperator($arr['operator']);
        parent::loadArray($arr, $key);

        return $this;
    }

    /**
     *Return the rule data as xml
     *
     * @param string $containerKey
     * @param string $itemKey
     *
     * @return string
     */
    public function asXml($containerKey = 'conditions', $itemKey = 'condition')
    {
        $xml = '<attribute>' . $this->getAttribute() . '</attribute>'
            . '<operator>' . $this->getOperator() . '</operator>'
            . parent::asXml($containerKey, $itemKey);

        return $xml;
    }

    public function loadValueOptions()
    {
        $this->setValueOption(array());

        return array();
    }

    /**
     * Populate the internal Operator data with accepatble operators
     *
     * @return \ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Register
     */
    public function loadOperatorOptions()
    {
        $this->setOperatorOption(
            array(
                '>=' => Mage::helper('rule')->__('more than or equal to'),
                '<=' => Mage::helper('rule')->__('less than or equal to'),
                '>'  => Mage::helper('rule')->__('more than'),
                '<'  => Mage::helper('rule')->__('less than'),
            )
        );

        return $this;
    }

    /**
     * Get this models Element Type
     *
     * @return type
     */
    public function getValueElementType()
    {
        return $this->_inputType;
    }

    /**
     * Get the renderer to use for this value type
     *
     * @return object
     */
    public function getValueElementRenderer()
    {
        return Mage::getBlockSingleton('rule/editable');
    }

    /**
     * Render this as html
     *
     * @return string
     */
    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            Mage::helper('giftpromo')->__(
                "If the customer registration is %s %s minutes", $this->getOperatorElement()->getHtml(),
                $this->getValueElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }

        return $html;
    }

    /**
     * validate
     *
     * @param Varien_Object $object Quote
     *
     * @return boolean
     */
    public function validate(Varien_Object $object)
    {

        // get the current logged in customer
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        $result = false;

        if (is_object($customer) && $customer->getId()) {
            /**
             * Condition attribute value
             */
            $value = $this->getValueParsed();

            /**
             * Comparison operator
             */
            $op = $this->getOperatorForValidate();

            $registered = strtotime($customer->getCreatedAt());
            $now = time();

            $minutes = round(abs($now - $registered) / 60, 0);

            switch ($op) {
                case '<':
                    if ($minutes < $value) {
                        $result = true;
                    }
                    break;
                case '>';
                    if ($minutes > $value) {
                        $result = true;
                    }
                    break;
                case '<=':
                    if ($minutes <= $value) {
                        $result = true;
                    }
                    break;
                case '>=';
                    if ($minutes >= $value) {
                        $result = true;
                    }
                    break;

            }
        }

        return $result;
    }

}
