<?php

/**
 * Customer Registration rule condition
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Customer_Conditions_Count
    extends Mage_Rule_Model_Condition_Abstract
{

    protected $_inputType = 'text';

    public function __construct()
    {
        Mage_Rule_Model_Condition_Abstract::__construct();
        $this->setType('giftpromo/promo_rule_condition_customer_conditions_count')
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
     * Return the rule data as xml
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
                '==' => Mage::helper('rule')->__('equals to '),
                '>'  => Mage::helper('rule')->__('more than '),
                '<'  => Mage::helper('rule')->__('less than '),
                '>=' => Mage::helper('rule')->__('more than or equals to'),
                '<=' => Mage::helper('rule')->__('less than or equals to')
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
                "If this gifting rule had been applied to the customer %s %s times. (<b>equals to 0</b> times = never)",
                $this->getOperatorElement()->getHtml(), $this->getValueElement()->getHtml()
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
        // basically we need to check all orders done by this customer, and check if the rule_id here has been applied to any of those orders.
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

            $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->AddFieldToFilter('customer_id', array('eq' => $customer->getId()))
                ->AddFieldToFilter('applied_gift_rule_ids', array('finset' => $this->getRule()->getRuleId()));
            $orderCollection->load();
            switch ($op) {
                case '==':
                    if ($orderCollection->count() == $value) {
                        $result = true;
                    }
                    break;
                case '<':
                    if ($orderCollection->count() < $value) {
                        $result = true;
                    }
                    break;
                case '>';
                    if ($orderCollection->count() > $value) {
                        $result = true;
                    }
                    break;
                case '<=':
                    if ($orderCollection->count() <= $value) {
                        $result = true;
                    }
                    break;
                case '>=';
                    if ($orderCollection->count() >= $value) {
                        $result = true;
                    }
                    break;

            }
        }

        return $result;
    }

}
