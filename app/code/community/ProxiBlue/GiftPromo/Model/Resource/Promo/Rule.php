<?php

/**
 * Rule collection
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 **/
class ProxiBlue_GiftPromo_Model_Resource_Promo_Rule extends ProxiBlue_GiftPromo_Model_Resource_Promo_Abstract
{
    /**
     * Initialize main table and table id field
     */
    protected function _construct()
    {
        $this->_init('giftpromo/promo_rule', 'rule_id');
    }
}
