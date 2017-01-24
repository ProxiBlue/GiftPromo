<?php

class ProxiBlue_Giftpromo_Block_Product_View_Type_Configurable extends Mage_Catalog_Block_Product_View_Type_Configurable
{

    public function __construct()
    {
        parent::__construct();
        $this->addData(
            array(
                'cache_lifetime' => null,
                'cache_tags'     => array(Mage_Cms_Model_Block::CACHE_TAG),
                'cache_key'      => rand(10000000, 5000000000)
            )
        );
    }

    /**
     * Composes configuration for js
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $attributes = array();
        $options = array();
        $store = $this->getCurrentStore();
        $taxHelper = Mage::helper('tax');
        $currentProduct = $this->getProduct();

        $preconfiguredFlag = $currentProduct->hasPreconfiguredValues();
        if ($preconfiguredFlag) {
            $preconfiguredValues = $currentProduct->getPreconfiguredValues();
            $defaultValues = array();
        }

        $uniqueId = $this->getGiftUid();

        $allowedProducts = $this->getAllowProducts();
        if (is_array($allowedProducts)) {
            foreach ($this->getAllowProducts() as $product) {
                $productId = $product->getId();

                foreach ($this->getAllowAttributes() as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $uniqueId . '-' . $productAttribute->getId();
                    $attributeValue = $product->getData($productAttribute->getAttributeCode());
                    if (!isset($options[$productAttributeId])) {
                        $options[$productAttributeId] = array();
                    }

                    if (!isset($options[$productAttributeId][$attributeValue])) {
                        $options[$productAttributeId][$attributeValue] = array();
                    }
                    $options[$productAttributeId][$attributeValue][] = $productId;
                }
            }

            $this->_resPrices = array(
                $this->_preparePrice($currentProduct->getFinalPrice())
            );

            foreach ($this->getAllowAttributes() as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeId = $uniqueId . '-' . $productAttribute->getId();
                $info = array(
                    'id'      => $uniqueId . '-' . $productAttribute->getId(),
                    'code'    => $productAttribute->getAttributeCode(),
                    'label'   => $attribute->getLabel(),
                    'options' => array()
                );

                $optionPrices = array();
                $prices = $attribute->getPrices();
                if (is_array($prices)) {
                    foreach ($prices as $value) {
                        if (!$this->_validateAttributeValue($attributeId, $value, $options)) {
                            continue;
                        }
                        $currentProduct->setConfigurablePrice(
                            $this->_preparePrice($value['pricing_value'], $value['is_percent'])
                        );
                        $currentProduct->setParentId(true);
                        Mage::dispatchEvent(
                            'catalog_product_type_configurable_price', array('product' => $currentProduct)
                        );
                        $configurablePrice = $currentProduct->getConfigurablePrice();

                        if (isset($options[$attributeId][$value['value_index']])) {
                            $productsIndex = $options[$attributeId][$value['value_index']];
                        } else {
                            $productsIndex = array();
                        }

                        $info['options'][] = array(
                            'id'       => $value['value_index'],
                            'label'    => $value['label'],
                            'price'    => '',
                            'oldPrice' => '',
                            'products' => $productsIndex,
                        );
                        $optionPrices[] = $configurablePrice;
                    }
                }
                /**
                 * Prepare formated values for options choose
                 */
                foreach ($optionPrices as $optionPrice) {
                    foreach ($optionPrices as $additional) {
                        $this->_preparePrice(abs($additional - $optionPrice));
                    }
                }
                if ($this->_validateAttributeInfo($info)) {
                    $attributes[$attributeId] = $info;
                }

                // Add attribute default value (if set)
                if ($preconfiguredFlag) {
                    $configValue = $preconfiguredValues->getData('super_attribute/' . $attributeId);
                    if ($configValue) {
                        $defaultValues[$attributeId] = $configValue;
                    }
                }
            }

            $taxCalculation = Mage::getSingleton('tax/calculation');
            if (!$taxCalculation->getCustomer() && Mage::registry('current_customer')) {
                $taxCalculation->setCustomer(Mage::registry('current_customer'));
            }

            $_request = $taxCalculation->getRateRequest(false, false, false);
            $_request->setProductClassId($currentProduct->getTaxClassId());
            $defaultTax = $taxCalculation->getRate($_request);

            $_request = $taxCalculation->getRateRequest();
            $_request->setProductClassId($currentProduct->getTaxClassId());
            $currentTax = $taxCalculation->getRate($_request);

            $taxConfig = array(
                'includeTax'     => $taxHelper->priceIncludesTax(),
                'showIncludeTax' => $taxHelper->displayPriceIncludingTax(),
                'showBothPrices' => $taxHelper->displayBothPrices(),
                'defaultTax'     => $defaultTax,
                'currentTax'     => $currentTax,
                'inclTaxTitle'   => Mage::helper('catalog')->__('Incl. Tax')
            );

            $config = array(
                'attributes' => $attributes,
                'template'   => str_replace('%s', '#{price}', $store->getCurrentCurrency()->getOutputFormat()),
                'basePrice'  => $this->_registerJsPrice($this->_convertPrice($currentProduct->getFinalPrice())),
                'oldPrice'   => $this->_registerJsPrice($this->_convertPrice($currentProduct->getPrice())),
                'productId'  => $currentProduct->getId(),
                'chooseText' => Mage::helper('catalog')->__('Choose an Option...'),
                'taxConfig'  => $taxConfig
            );

            if ($preconfiguredFlag && !empty($defaultValues)) {
                $config['defaultValues'] = $defaultValues;
            }

            $config = array_merge($config, $this->_getAdditionalConfig());

            return Mage::helper('core')->jsonEncode($config);
        } else {
            return '{}';
        }
    }

    /**
     * Retrieve the current selecting product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if ($this->getData('product')) {
            return $this->getData('product');
        } else {
            return Mage::getSingleton('catalog/product');
        }
    }

    /**
     * Get Allowed Products
     *
     * @return array
     */
    public function getAllowProducts()
    {

        $products = array();
        $version = Mage::getVersionInfo();
        // version below 1.7 / 1.12 do not have SkipSaleableCheck
        if ((class_exists('Enterprise_Cms_Helper_Data', false) && $version['minor'] < 12) || $version['minor'] < 7) {
            $skipSaleableCheck = false;
        } else {
            $skipSaleableCheck = Mage::helper('catalog/product')->getSkipSaleableCheck();
        }

        $allProducts = $this->getProduct()->getTypeInstance(true)
            ->getUsedProducts(null, $this->getProduct());
        foreach ($allProducts as $product) {
            if ($product->isSaleable() || $skipSaleableCheck) {
                $products[] = $product;
            }
        }
        $this->setAllowProducts($products);

        return $this->getData('allow_products');
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
     * Load block html from cache storage
     *
     * @return string | false
     */
    protected function _loadCache()
    {
        return false;
    }

    /**
     * Save block content to cache storage
     *
     * @param string $data
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _saveCache($data)
    {
        return false;
    }

}

