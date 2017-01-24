<?php

/**
 * Customer Twitter rule condition
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Twitter_Conditions_Follow
    extends ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Twitter_Api
{

    protected $_inputType = 'text';

    public function __construct()
    {
        parent::__construct();
        $this->setType('giftpromo/promo_rule_condition_twitter_conditions_follow')
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
                '==' => Mage::helper('rule')->__('follows'),
                '!=' => Mage::helper('rule')->__('does not follow'),
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
                "If the customer %s the twitter account:  <b>%s</b>", $this->getOperatorElement()->getHtml(),
                ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Twitter_Conditions::getTwitterHandle()
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
        if (is_object($customer) && $customer->getId()) {
            /**
             * Condition attribute value
             */
            $value = $this->getValueParsed();

            /**
             * Comparison operator
             */
            $op = $this->getOperatorForValidate();

            $result = false;
            $following = false;
            try {
                $url = 'friendships/lookup.json';
                $getfield = '?screen_name='
                    . ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Twitter_Conditions::getTwitterHandle()
                    . ',' . $customer->getTwitterHandle();
                $requestMethod = 'GET';
                $result = $this->setGetfield($getfield)
                    ->buildOauth($url, $requestMethod)
                    ->performRequest(true);
                $result = json_decode($result);
                $ourRelationData = array_shift($result);
                if (property_exists($ourRelationData, 'connections')) {
                    foreach ($ourRelationData->connections as $key => $value) {
                        if ($value == 'following') {
                            $following = true;
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }

            switch ($op) {
                case '==':
                    if ($following) {
                        $result = true;
                    }
                    break;
                case '!=':
                    if (!$following) {
                        $result = true;
                    }
                    break;
            }
        }

        return $result;
    }

}
