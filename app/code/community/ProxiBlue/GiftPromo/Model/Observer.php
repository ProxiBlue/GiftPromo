<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Base observer class extended by module observers
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Observer
{

    /**
     * Internal holder for helper class
     *
     * @var object
     */
    protected $_helper;

    /**
     * Get the helper class and cache teh object
     *
     * @return object
     */
    protected function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::Helper('giftpromo');
        }

        return $this->_helper;
    }


}

?>
