<?php

class ProxiBlue_GiftPromo_Model_Session extends Mage_Core_Model_Session_Abstract
{

    /**
     * Class constructor. Initialize session namespace
     */
    public function __construct()
    {
        $this->init('giftpromo');
        if (!is_array($this->getCurrentSelectedGifts())) {
             $this->setCurrentSelectedGifts(array());
        }
    }

    /**
     * Unset all data associated with object
     */
    public function unsetAll()
    {
        parent::unsetAll();
        $this->setCurrentSelectedGifts(array());
    }

    public function clear()
    {
        $this->setCurrentSelectedGifts(array());
    }

}
