<?php

/**
 * Helper routines to build the gifticon
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Helper_GiftIcon
    extends Mage_Catalog_Helper_Data
{

    protected $_product_valid_cache = array();

    public function giftIconHtml($product)
    {
        if (Mage::getStoreConfig('giftpromo/catalog/icons_enabled')) {
            $layout = Mage::getSingleton('core/layout');
            $giftBlock = $layout->createBlock(
                'giftpromo/product_list_icon',
                'catalog.product.gifticon',
                array(
                    'template' => 'giftpromo/catalog/product/list/icon.phtml'
                )
            );
            if ($giftBlock) {
                $html = $giftBlock->setProduct($product)->toHtml();

                return $html;
            }
        }

        return '';
    }

    /**
     * Get all gifts assocaited with a product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param bool                       $asArray
     *
     *
     * @return mixed array\Varien_Data_Collection
     */
    public function testItemHasValidGifting(
        Mage_Catalog_Model_Product $product, $asArray = true, $resultCopyQuote = false
    ) {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $useCache = Mage::app()->useCache('giftpromo_product_valid');
        if ($useCache) {
            $cacheID = ($quote->getId()) ? $quote->getId() : 0;
            $cacheName = 'giftpromo_product_valid_cache_'
                . $product->getId()
                . '_' . $quote->getUpdatedAt()
                . '_' . $cacheID;
            $cachedData = Mage::app()->getCache()
                ->load($cacheName);
            if (is_string($cachedData)) {
                try {
                    $result = unserialize($cachedData);
                    mage::helper('giftpromo')->debug("loaded from cache {$cacheName}", 5);

                    return $result;
                } catch (Exception $e) {
                    mage::logException($e);
                }
            }
        }

        $giftProducts = array();
        try {
            $rules = Mage::getSingleton('giftpromo/promo_rule')
                ->getCollection()
                ->addFieldToSelect('rule_id');
            // create a copy of the current quote object
            // add this product to the copy quote
            // validate against the copy quote
            $copyQuote = new Varien_Object;
            $copyQuote->setData($quote->getData());
            // inject this product as an item here!
            $allItems = array();
            $product->setQty(1);
            //The below caused selectable gifts to revert back to normal products.
            //not entirely sure why there is a need to reload the object here.
            //maybe something else is now broke?
            //$product = $product->load($product->getId());
            $product->setProductId(
                $product->getId()
            ); // workaround since it expects cart items, not products
            $product->setProduct(
                $product
            ); // workaround since it expects cart items, not products
            $allItems[] = $product;
            //adjust the cart subtotal to reflect the additional item price, thus making rule valid for totals
            $copyQuote->setAllItems($allItems);
            $copyQuote->setAllVisibleItems($allItems);
            $copyQuote->setSubtotal(
                $copyQuote->getSubtotal() + $product->getPrice()
            );
            $copyQuote->setSkipForced(true);
            foreach ($rules as $rule) {
                $rule = Mage::getModel('giftpromo/promo_rule')->load(
                    $rule->getId()
                );
                $product->setGiftingRule($rule->getId());
                // test if the rule can be processed (valid coupon/usage/customer group if coupon is required for rule)
                if ($rule->validate($copyQuote)) {

                    $giftProductsFromRule = $rule->getItemsArray(true, $product);
                    if (is_array($giftProductsFromRule)) {
                        foreach ($giftProductsFromRule as $ruleBasedGiftProduct) {
                            $giftProducts[$ruleBasedGiftProduct->getId()]
                                = $ruleBasedGiftProduct;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        if ($resultCopyQuote) {
            return $copyQuote;
        } elseif ($asArray) {
            $result = $giftProducts;
        } else {
            $collection = new Varien_Data_Collection;
            foreach ($giftProducts as $giftProduct) {
                try {
                    $collection->addItem($giftProduct);
                } catch (Exception $e) {
                    // fail silently, as the item already exists as a gift, thus don't add it again
                }
            }
            $result = $collection;
        }

        // cache this
        if ($useCache) {
            try {
                $cacheID = ($quote->getId()) ? $quote->getId() : 0;
                $cacheName = 'giftpromo_product_valid_cache_'
                    . $product->getId()
                    . '_' . $quote->getUpdatedAt()
                    . '_' . $cacheID;
                $cacheModel = Mage::app()->getCache();
                if ($product->getId()) {
                    // clear out any old cache for this.
                    mage::helper('giftpromo')->debug(
                        "Clearing old cache for GIFTPROMO_PRODUCT_VALID_{$product->getId()}_{$cacheID}", 5
                    );
                    $cacheModel->clean(
                        Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                        array('GIFTPROMO_PRODUCT_VALID_' . $product->getId() . '_' . $cacheID)
                    );
                }
                $cacheModel
                    ->save(
                        serialize($result),
                        $cacheName,
                        array('GIFTPROMO_PRODUCT_VALID',
                              'GIFTPROMO_PRODUCT_VALID_' . $cacheID,
                              'GIFTPROMO_PRODUCT_VALID_' . $product->getId(),
                              'GIFTPROMO_PRODUCT_VALID_' . $product->getId() . '_' . $cacheID
                        ), 600
                    );
                mage::helper('giftpromo')->debug("Generating cache for {$cacheName}", 5);

            } catch
            (Exception $e) {
                mage::logException($e);
            }
        }

        return $result;
    }

    public function __destruct()
    {
        mage::getSingleton('checkout/session')->setProductValidCache(
            $this->_product_valid_cache
        );
    }

    public function isProductAGift($product)
    {
        $rules = Mage::getSingleton('giftpromo/promo_rule')
            ->getCollection()
            ->addFieldToSelect('rule_id')
            ->addFieldToFilter('giftpromo', array('like' => '%' . $product->getId() . '=%'));
        if (count($rules->getItems()) > 0) {
            return $this->giftIconHtml($product);
        }
        return '';
    }


}
