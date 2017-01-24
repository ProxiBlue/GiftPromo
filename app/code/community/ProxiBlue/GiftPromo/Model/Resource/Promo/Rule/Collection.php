<?php

/**
 * Rule collection
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 **/
class ProxiBlue_GiftPromo_Model_Resource_Promo_Rule_Collection
    extends ProxiBlue_GiftPromo_Model_Resource_Promo_Rule_Collection_Abstract
{

    protected $_dateFilter = false;

    public function enableDateFilter()
    {
        $this->_dateFilter = true;
    }

    /**
     * Constructor
     *
     */
    protected function _construct()
    {
        $this->_init('giftpromo/promo_rule');
    }

    public function asArray()
    {
        $result = array();
        foreach ($this->getItems() as $item) {
            $sortOrder = ($item->getSortOrder() === false)?0:$item->getSortOrder();
            if(!array_key_exists($sortOrder,$result)) {
                $result[$sortOrder] = array();
            }
            $result[$sortOrder][] = $item;
        }
        return $result;

    }

}
