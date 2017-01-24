<?php

class ProxiBlue_GiftPromo_Block_Sales_Reorder_Sidebar extends Mage_Sales_Block_Reorder_Sidebar
{

    /**
     * Check item product availability for reorder
     *
     * @param  Mage_Sales_Model_Order_Item $orderItem
     *
     * @return boolean
     */
    public function isItemAvailableForReorder(Mage_Sales_Model_Order_Item $orderItem)
    {
        $result = false;
        if ($orderItem->getProduct()) {
            if (Mage::helper('giftpromo')->testGiftTypeCode($orderItem->getProductType())) {
                return false;
            }

            return $orderItem->getProduct()->getStockItem()->getIsInStock();
        }

        return $result;
    }

}
