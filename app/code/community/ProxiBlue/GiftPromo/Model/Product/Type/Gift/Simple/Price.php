<?php

/**
 * Gift product price renderer.
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Product_Type_Gift_Simple_Price extends Mage_Catalog_Model_Product_Type_Price
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
        if (is_null($product->getGiftedPrice())) {
            $infoBuyRequest = $product->getCustomOption('info_buyRequest');
            $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
            if($buyRequest->getGiftedPrice()) {
                $giftedPrice = $buyRequest->getGiftedPrice();
            } else {
                $giftProductModel = Mage::getModel('giftpromo/product')->cloneProduct($product);
                if (is_null($giftProductModel->getGiftedPrice())) {
                    $giftProductModel->setGiftedPrice(0);
                }
                $giftedPrice = $giftProductModel->getGiftedPrice();
            }
        } else {
            $giftedPrice = $product->getGiftedPrice();
        }
        if ($giftedPrice === false) {
            $giftedPrice = 0;
        }

        if(strpos($giftedPrice,'%') !== false) {
            $realPrice = $product->getPrice();
            $giftedPrice = $realPrice - (float)(str_replace('%','',$giftedPrice)/100 * $realPrice);
        }

        $finalPrice = $giftedPrice;
        $product->setFinalPrice($finalPrice);

        Mage::dispatchEvent('giftpromo_product_get_final_price', array('product' => $product, 'qty' => $qty));

        $finalPrice = $product->getData('final_price');
        $finalPrice = $this->_applyOptionsPrice($product, $qty, $finalPrice);
        $finalPrice = max(0, $finalPrice);
        $product->setFinalPrice($finalPrice);
        $product->setCalculatedFinalPrice($finalPrice);

        return $finalPrice;
    }

}
