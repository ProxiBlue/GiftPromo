<?php

/**
 * Gift product price renderer.
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Product_Type_Gift_Bundle_Price extends Mage_Bundle_Model_Product_Price
{
    /**
     * Return product base price
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getPrice($product)
    {
        if (is_null($product->getGiftedPrice())) {
            $giftProductModel = Mage::getModel('giftpromo/product')->cloneProduct($product);
            if (is_null($giftProductModel->getGiftedPrice())) {
                $giftProductModel->setGiftedPrice(0);
            }
            $giftedPrice = $giftProductModel->getGiftedPrice();
        } else {
            $giftedPrice = $product->getGiftedPrice();
        }
        if ($giftedPrice === false) {
            $giftedPrice = 0;
        }
        $finalPrice = max(0, $giftedPrice);
        $product->setFinalPrice($finalPrice);

        return $finalPrice;

    }


    /**
     * Get product final price
     *
     * @param   double                     $qty
     * @param   Mage_Catalog_Model_Product $product
     *
     * @return  double
     */
    public function getFinalPrice($qty = null, $product)
    {
        if (is_null($product->getGiftedPrice())) {
            $giftProductModel = Mage::getModel('giftpromo/product')->cloneProduct($product);
            if (is_null($giftProductModel->getGiftedPrice())) {
                $giftProductModel->setGiftedPrice(0);
            }
            $giftedPrice = $giftProductModel->getGiftedPrice();
        } else {
            $giftedPrice = $product->getGiftedPrice();
        }
        if ($giftedPrice === false) {
            $giftedPrice = 0;
        }

        if(strpos($giftedPrice,'%') !== false) {
            $realPrice = $product->getPrice();
            $giftedPrice = $realPrice - ($giftedPrice/100 * $realPrice);
        }

        $finalPrice = max(0, $giftedPrice);
        $product->setFinalPrice($finalPrice);

        return $finalPrice;
    }

    public function getSelectionFinalTotalPrice(
        $bundleProduct, $selectionProduct, $bundleQty, $selectionQty,
        $multiplyQty = true, $takeTierPrice = true
    ) {
        return 0;
    }

    /**
     * Returns final price of a child product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float                      $productQty
     * @param Mage_Catalog_Model_Product $childProduct
     * @param float                      $childProductQty
     *
     * @return decimal
     */
    public function getChildFinalPrice($product, $productQty, $childProduct, $childProductQty)
    {
        return 0;
    }

    /**
     * Get item price used for quote calculation process.
     * This method get custom price (if it is defined) or original product final price
     *
     * @return float
     */
    public function getCalculationPrice()
    {
        return 0;
    }

}
