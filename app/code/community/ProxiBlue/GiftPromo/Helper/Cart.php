<?php

/**
 * Shopping cart helper
 *
 */
class ProxiBlue_GiftPromo_Helper_Cart extends Mage_Checkout_Helper_Cart
{

    /**
     * Retrieve url for add product to cart
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array                      $additional
     *
     * @return string
     */
    public function getAddUrl($product, $additional = array())
    {
        if (mage::helper('giftpromo')->testGiftTypeCode($product->getTypeId())
            && is_object(mage::registry('current_rule_object'))
        ) {
            $additional = array_merge(
                $additional,
                array('rule_id'                => mage::registry('current_rule_object')->getId(),
                      'selected_gift_item_key' => mage::registry('current_gift_item_key') . "_" . $product->getId(),
                      '_secure'                => Mage::app()->getStore()->isCurrentlySecure(),
                      '_store'                 => Mage::app()->getStore()->getId(),
                      '_redirect_url'          => base64_encode($this->getCartUrl())
                )
            );
            if ($currentGiftItemParentKey = mage::registry('current_gift_item_parent_key')) {
                $additional['gift_parent_item_id'] = $currentGiftItemParentKey;
            }

            return Mage::getUrl('giftpromo/cart/addgift', $additional);

        } else {
            return parent::getAddUrl($product, $additional);
        }
    }

    /**
     * Alternative way to calculate line items count.
     * It has been found that in some instances cart line items can be counted twice.
     * This overcomes that issue if exists in given store codebase
     *
     * @return int
     */
    public function getItemsCount()
    {
        if(!Mage::getStoreConfig('giftpromo/cart/use_alternative_items_count')) {
            return parent::getItemsCount();
        }
        $itemsCount = 0;

        foreach ($this->getQuote()->setData(
            'trigger_recollect',
            0
        )->getAllVisibleItems() as $item) {
            if ($item->getParentItem() || Mage::helper('giftpromo')->testGiftTypeCode($item->getProductType())) {
                continue;
            }
            $itemsCount++;
        }
        $this->getQuote()->setItemsCount($itemsCount);

        return $itemsCount*1;

    }

}
