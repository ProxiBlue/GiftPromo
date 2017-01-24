<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Catalog category helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ProxiBlue_Giftpromo_Helper_Product extends Mage_Catalog_Helper_Product
{

    /**
     * Retrieve product view page url
     *
     * @param   mixed $product
     *
     * @return  string
     */
    public function getProductUrl($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            return $product->getProductUrl();
        } elseif (is_numeric($product)) {
            return Mage::getModel('catalog/product')->load($product)->getProductUrl();
        }

        return false;
    }

    /**
     * Retrieve product price
     *
     * @param   Mage_Catalog_Model_Product $product
     *
     * @return  float
     */
    public function getPrice($product)
    {
        return $product->getPrice();
    }

    /**
     * Retrieve product final price
     *
     * @param   Mage_Catalog_Model_Product $product
     *
     * @return  float
     */
    public function getFinalPrice($product)
    {
        return $product->getFinalPrice();
    }


    /**
     * Inits product to be used for product controller actions and layouts
     * $params can have following data:
     *   'category_id' - id of category to check and append to product as current.
     *     If empty (except FALSE) - will be guessed (e.g. from last visited) to load as current.
     *
     * @param int                               $productId
     * @param Mage_Core_Controller_Front_Action $controller
     * @param Varien_Object                     $params
     *
     * @return false|Mage_Catalog_Model_Product
     */
    public function initProduct($productId, $controller, $params = null)
    {
        // Prepare data for routine
        if (!$params) {
            $params = new Varien_Object();
        }

        // Init and load product
        Mage::dispatchEvent(
            'catalog_controller_product_init_before', array(
                'controller_action' => $controller,
                'params'            => $params,
            )
        );

        if (!$productId) {
            return false;
        }

        $product = Mage::getModel('giftpromo/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($productId);

        if (!in_array(Mage::app()->getStore()->getWebsiteId(), $product->getWebsiteIds())) {
            return false;
        }

        // Load product current category
        $categoryId = $params->getCategoryId();
        if (!$categoryId && ($categoryId !== false)) {
            $lastId = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId();
            if ($product->canBeShowInCategory($lastId)) {
                $categoryId = $lastId;
            }
        } elseif (!$product->canBeShowInCategory($categoryId)) {
            $categoryId = null;
        }

        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $product->setCategory($category);
            Mage::register('current_category', $category);
        }

        // Register current data and dispatch final events
        Mage::register('current_product', $product);
        Mage::register('product', $product);

        try {
            Mage::dispatchEvent('catalog_controller_product_init', array('product' => $product));
            Mage::dispatchEvent(
                'catalog_controller_product_init_after',
                array('product'           => $product,
                      'controller_action' => $controller
                )
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);

            return false;
        }

        return $product;
    }

    /**
     * Return loaded product instance
     *
     * @param  int|string $productId (SKU or ID)
     * @param  int        $store
     * @param  string     $identifierType
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct($productId, $store, $identifierType = null)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('giftpromo/product')->setStoreId(Mage::app()->getStore($store)->getId());

        $expectedIdType = false;
        if ($identifierType === null) {
            if (is_string($productId) && !preg_match("/^[+-]?[1-9][0-9]*$|^0$/", $productId)) {
                $expectedIdType = 'sku';
            }
        }

        if ($identifierType == 'sku' || $expectedIdType == 'sku') {
            $idBySku = $product->getIdBySku($productId);
            if ($idBySku) {
                $productId = $idBySku;
            } else {
                if ($identifierType == 'sku') {
                    // Return empty product because it was not found by originally specified SKU identifier
                    return $product;
                }
            }
        }

        if ($productId && is_numeric($productId)) {
            $product->load((int)$productId);
        }

        return $product;
    }


}
