<?php

/**
 * Quote item resource collection
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Sales_Resource_Quote_Item_Collection
    extends Mage_Sales_Model_Resource_Quote_Item_Collection
{

    /**
     * Add products to items and item options
     *
     * Rewrite to handle product added to cart, which is also in cart as a gift.
     * This should not happen a lot.
     * The issue with core code is that the two quote item's product data get muddled, due to
     * entity references, thus the new (non gift product) gets its product type also set to gift on
     * cart reload. Magento just never expected this sittuation, thus the core code just don't work for it.
     *
     * @return Mage_Sales_Model_Resource_Quote_Item_Collection
     */
    protected function _assignProducts()
    {
        Varien_Profiler::start('QUOTE:' . __METHOD__);
        $productIds = array();
        foreach ($this as $item) {
            $productIds[] = (int)$item->getProductId();
        }
        $this->_productIds = array_merge($this->_productIds, $productIds);

        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->setStoreId($this->getStoreId())
            ->addIdFilter($this->_productIds)
            ->addAttributeToSelect(Mage::getSingleton('sales/quote_config')->getProductAttributes())
            ->addOptionsToResult()
            ->addStoreFilter()
            ->addUrlRewrite()
            ->addTierPriceData()
            ->setFlag('require_stock_items', true);

        Mage::dispatchEvent(
            'prepare_catalog_product_collection_prices', array(
                'collection' => $productCollection,
                'store_id'   => $this->getStoreId(),
            )
        );
        Mage::dispatchEvent(
            'sales_quote_item_collection_products_after_load', array(
                'product_collection' => $productCollection
            )
        );

        $recollectQuote = false;
        foreach ($this as $item) {
            $product = $productCollection->getItemById($item->getProductId());
            if ($product) {
                // check if this product appears in the basket multiple times
                // if so, reload the poduct to fix the type
                $repeated = $this->countProductsRepeated();
                if ($repeated[$item->getProductId()] > 1) {
                    $product = Mage::getModel('catalog/product')->load($product->getId());
                }
                $product->setCustomOptions(array());
                $qtyOptions = array();
                $optionProductIds = array();
                foreach ($item->getOptions() as $option) {
                    /**
                     * Call type specified logic for product associated with quote item
                     */
                    $product->getTypeInstance(true)->assignProductToOption(
                        $productCollection->getItemById($option->getProductId()), $option, $product
                    );

                    if (is_object($option->getProduct()) && $option->getProductId() != $product->getId()) {
                        $optionProductIds[$option->getProductId()] = $option->getProductId();
                    }
                }

                if ($optionProductIds) {
                    foreach ($optionProductIds as $optionProductId) {
                        $qtyOption = $item->getOptionByCode('product_qty_' . $optionProductId);
                        if ($qtyOption) {
                            $qtyOptions[$optionProductId] = $qtyOption;
                        }
                    }
                }
                $item->setQtyOptions($qtyOptions);

                $item->setProduct($product);
            } else {
                $item->isDeleted(true);
                $recollectQuote = true;
            }
            $item->checkData();
        }

        if ($recollectQuote && $this->_quote) {
            $this->_quote->collectTotals();
        }
        Varien_Profiler::stop('QUOTE:' . __METHOD__);

        return $this;
    }

    /**
     * Count the number of times a product id appears in the cart.
     *
     * @return array
     */
    private function countProductsRepeated()
    {
        $arr = array();
        foreach ($this->getItems() as $item) {
            $arr[] = $item->getProductId();
        }

        return array_count_values($arr);
    }

}

