<?php

class ProxiBlue_GiftPromo_Model_Sales_Order_Api extends Mage_Sales_Model_Order_Api
{
    /**
     * Retrieve full order information
     * and adjust teh gift- products accordingly
     *
     * @param string $orderIncrementId
     * @return array
     */
    public function info($orderIncrementId)
    {
        $result = parent::info($orderIncrementId);
        if (Mage::getStoreConfig('giftpromo/api/strip_gift_type')) {
            if (is_array($result) && !empty($result['items']) && is_array($result['items'])) {
                foreach ($result['items'] as $key => $item) {
                    $result['items'][$key]['product_type'] = str_replace(
                        ProxiBlue_GiftPromo_Model_Product_Type::TYPE_GIFT
                        , '', $item['product_type']
                    );
                }
            }
        }
        return $result;
    }


} // Class Mage_Sales_Model_Order_Api End
