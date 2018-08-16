<?php

class ProxiBlue_GiftPromo_Model_Sales_Quote_Payment extends Mage_Sales_Model_Quote_Payment
{


    /**
     *
     * Prevent gifting to action on call to this routine
     *
     * @param   array $data
     * @throws  Mage_Core_Exception
     * @return  Mage_Sales_Model_Quote_Payment
     */
    public function importData(array $data)
    {
        Mage::register('giftpromo_busy', true, true);
        $result = parent::importData($data);
        Mage::register('giftpromo_busy', false, true);
        return $result;
    }

}
