<?php

/**
 * Common methods for gift valid checking
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Product_List_Abstract
    extends Mage_Catalog_Block_Product_Abstract
{

    /**
     * Collection of items
     *
     * @var Varien_Collection
     */
    protected $_itemCollection;

    /**
     * Get items collection
     *
     * @return Varien_Collection
     */
    public function getItems()
    {
        return $this->_itemCollection;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->addData(
            array(
                'cache_lifetime' => null,
            )
        );
    }

    /**
     * Before html renderer
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->_prepareData();

        return parent::_beforeToHtml();
    }

    /**
     * Prepare the collection data
     */
    protected function _prepareData()
    {
        try {
            $this->_itemCollection = Mage::helper('giftpromo/gifticon')
                ->testItemHasValidGifting(
                    Mage::registry('product'),
                    false
                );

            return $this;
        } catch (Exception $e) {
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }
    }

}
