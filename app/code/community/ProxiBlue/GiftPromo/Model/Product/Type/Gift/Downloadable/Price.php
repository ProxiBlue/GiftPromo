<?php

/**
 * Gift product price renderer.
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Product_Type_Gift_Downloadable_Price extends Mage_Downloadable_Model_Product_Price
{
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
        if (is_null($qty) && !is_null($product->getCalculatedFinalPrice())) {
            return $product->getCalculatedFinalPrice();
        }

        if (is_null($product->getGiftedPrice())) {
            $giftProductModel = Mage::getModel('giftpromo/product')->cloneProduct($product);
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

        $finalPrice = $giftedPrice;
        $product->setFinalPrice($finalPrice);

        Mage::dispatchEvent('giftpromo_product_get_final_price', array('product' => $product, 'qty' => $qty));

        $finalPrice = $product->getData('final_price');
        $finalPrice = $this->_applyOptionsPrice($product, $qty, $finalPrice);
        $finalPrice = max(0, $finalPrice);
        $product->setFinalPrice($finalPrice);

        return $finalPrice;
    }
}
