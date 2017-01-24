<?php

/**
 * Renderer for gift Gift Message column (also renders Gift Label)
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftedProducts
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        return $this->renderProductList($row);
    }

    /**
     * Render gifted products as a list
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    private function renderProductList(Varien_Object $row)
    {
        $rule = Mage::getModel('giftpromo/promo_rule')->load($row->getRuleId());
        $giftedProducts = $rule->getGiftedProducts();
        $productIds = array_keys($giftedProducts);
        $html = "";
        foreach ($productIds as $productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $html .= "<a style='text-decoration:none' href='" . Mage::helper('adminhtml')->getUrl('adminhtml/catalog_product/edit', array('id' => $product->getId())) . "'>"
                    . $product->getSku() . " " . $product->getName() . ' (' . $product->getTypeId() . ')</a><br/>';
        }

        return $html;
    }


}
