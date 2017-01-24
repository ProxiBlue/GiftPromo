<?php


/**
 * Gift Product Url model
 *
 */
class ProxiBlue_GiftPromo_Model_Product_Url extends Mage_Catalog_Model_Product_Url
{
    /**
     * Retrieve product URL, for GIft View, based on requestPath param
     *
     * @return string
     */
    public function getProductUrl($product, $useSid = null)
    {
        // ensure this is in fact a gift product
        if(mage::helper('giftpromo')->testGiftTypeCode($product->getTypeId()) == false) {
            return parent::getProductUrl($product, $useSid);
        }
        $routeParams = array();

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        Mage::getSingleton('checkout/session')->setQuoteId($quote->getId());
        if ($quote->getId()) {
            $data = array('quoteid'     => $quote->getId(),
                          'itemid'      => $product->getId(),
                          'giftruleid'  => $product->getGiftRuleId(),
                          'giftitemkey' => $product->getGiftItemKey());
            if (is_object($product->getGiftParentItem())) {
                $data['parentid'] = $product->getGiftParentItem()->getId();
            }
            $params = serialize($data);
            $params = Mage::helper('core')->encrypt($params);
            $routeParams['_query'] = array('___store'=>'', 'key' => $params);
        }


        return $this->getUrlInstance()->getUrl('catalog/product/giftview/', $routeParams);
    }
}
