<?php

/**
 * Get gifts products attached to currnt viewed product
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Product_List_Gifts
    extends ProxiBlue_GiftPromo_Block_Product_List_Abstract
{


    public function canLinkItem($item)
    {
        return Mage::helper('giftpromo')->canLinkItem($item);
    }

}
