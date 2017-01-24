<?php

class ProxiBlue_Giftpromo_Block_Product_View extends Mage_Catalog_Block_Product_View
{

    public function __construct()
    {
        parent::__construct();
        $this->addData(
            array(
                'cache_lifetime' => null,
                'cache_tags'     => array(Mage_Cms_Model_Block::CACHE_TAG)
            )
        );

    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $cacheKey = array(
            'BLOCK_TPL',
            Mage::app()->getStore()->getCode() . '-' . $this->getProduct()->getId(),
            $this->getTemplateFile(),
            'template' => $this->getTemplate()
        );

        return $cacheKey;
    }

    /**
     * Retrieve the current selecting product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if ($this->getData('product')) {
            $product = $this->getData('product');
        } elseif (mage::registry('current_product')) {
            $product = mage::registry('current_product');
        } else {
            $product = Mage::getSingleton('catalog/product');
        }
        if (!$product instanceof ProxiBlue_GiftPromo_Model_Product) {
            $product = mage::getModel('giftpromo/product')->cloneProduct($product);
        }
        $this->setData('product', $product);

        return $this->getData('product');
    }

    /**
     * Obtain sorted child blocks
     *
     * @return array
     */
    public function getSortedChildBlocks()
    {
        $children = array();
        $giftuid = rand(0, 10000000000) . md5(uniqid(rand(0, 10000000000) . '_', true)) . rand(0, 10000000000);
        foreach ($this->getSortedChildren() as $childName) {
            $block = $this->getLayout()->getBlock($childName);
            $block->setGiftUid($giftuid);
            $children[$childName] = $block;
        }

        return $children;
    }

    /**
     * Retrieve add to cart url
     *
     * @return string
     */
    public function getAddToCartUrl($product, $additional = array())
    {
        return mage::helper('giftpromo')->getAddUrl($this);
    }

    protected function _prepareLayout()
    {
        return $this;
    }


}
