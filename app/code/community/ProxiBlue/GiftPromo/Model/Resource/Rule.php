<?php

/**
 * GiftPromo Rule resource model
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Resource_Rule extends Mage_Rule_Model_Mysql4_Rule
{ //Mage_Rule_Model_Resource_Rule /** magento 1.12/1.7 introduced Mage_Rule_Model_Resource_Abstract * */ {

    /**
     * Initialize main table and table id field
     */

    protected function _construct()
    {
        $this->_init('giftpromo/rule', 'rule_id');
    }

}
