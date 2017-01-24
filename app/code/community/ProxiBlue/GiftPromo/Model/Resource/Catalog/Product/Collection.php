<?php

class ProxiBlue_GiftPromo_Model_Resource_Catalog_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    /**
     * Initialize resources
     *
     */
    protected function _construct()
    {
        // if other 3rd party modules extend the catalog/product entity models, we loose our giftpromo
        // product model in loaded giftpromo product collections.
        // This prevents this, by ensuring our resource classes and models are in it.
        $this->_init('giftpromo/product', 'catalog/product');
        $this->_initTables();
    }

    /**
     * force to ensure there is a stock item set for the item.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setFlag('require_stock_items', true);
    }
}
