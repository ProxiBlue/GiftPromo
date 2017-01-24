<?php

/**
 * Grid display of selectable gift products in promo rules tab
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_AdminHtml_Promo_Rule_Edit_Tab_Actions_GiftPromo_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * Set grid params
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('gift_product_grid');
        $this->setDefaultSort('gifted_position');
        $this->setUseAjax(true);
        $this->setDefaultFilter(array('in_products' => 1));
    }

    /**
     * Rerieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getData('grid_url')
            ? $this->getData('grid_url')
            : $this->getUrl(
                '*/*/giftpromoGrid', array('_current' => true)
            );
    }

    /**
     * Add filter
     *
     * @param object $column
     *
     * @return Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_GiftPromo
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in' => $productIds));
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', array('nin' => $productIds));
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Retrieve selected gift product products
     *
     * @return array
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRule()->getGiftedProducts();
        if (!is_array($products)) {
            $products = array();
        }

        return array_keys($products);
    }

    /**
     * Prepare collection
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter(
                'type_id', array('in' =>
                                     array(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                                           Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
                                           Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
                                           Mage_Catalog_Model_Product_Type::TYPE_BUNDLE))
            )
            ->addAttributeToSelect('*');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Add columns to grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $ruleModel = Mage::registry('current_giftpromo_promo_rule');
        $this->setRule($ruleModel);

        $this->addColumn(
            'in_products', array(
                'header_css_class' => 'a-center',
                'type'             => 'checkbox',
                'name'             => 'in_products',
                'values'           => $this->_getSelectedProducts(),
                'align'            => 'center',
                'index'            => 'entity_id',
                'value'            => 1,
            )
        );


        $this->addColumn(
            'entity_id', array(
                'header'   => Mage::helper('catalog')->__('ID'),
                'sortable' => true,
                'width'    => 60,
                'index'    => 'entity_id'
            )
        );

        $this->addColumn(
            'sku', array(
                'header' => Mage::helper('catalog')->__('SKU'),
                'width'  => 80,
                'index'  => 'sku'
            )
        );

        $this->addColumn(
            'name', array(
                'header' => Mage::helper('catalog')->__('Name'),
                'index'  => 'name',
                'width'  => 300
            )
        );

        $this->addColumn(
            'gifted_message', array(
                'header'         => Mage::helper('catalog')->__('Gifted Label and Message'),
                'name'           => 'gifted_message',
                'index'          => 'gifted_message',
                'label_width'    => 100,
                'message_width'  => 200,
                'message_height' => 50,
                'width'          => 300,
                'filter'         => false,
                'renderer'       => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftedMessage',
            )
        );

        $this->addColumn(
            'type',
            array(
                'header'  => Mage::helper('catalog')->__('Type'),
                'width'   => '60px',
                'index'   => 'type_id',
                'type'    => 'options',
                /** LVSTODO: Limit product types here as well **/
                'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
            )
        );

        $this->addColumn(
            'status', array(
                'header'  => Mage::helper('catalog')->__('Status'),
                'width'   => 90,
                'index'   => 'status',
                'type'    => 'options',
                'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
            )
        );

        $this->addColumn(
            'visibility', array(
                'header'  => Mage::helper('catalog')->__('Visibility'),
                'width'   => 90,
                'index'   => 'visibility',
                'type'    => 'options',
                'options' => Mage::getSingleton('catalog/product_visibility')->getOptionArray(),
            )
        );

        $this->addColumn(
            'price', array(
                'header'        => Mage::helper('giftpromo')->__('Original Price'),
                'type'          => 'currency',
                'currency_code' => (string)Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
                'index'         => 'price'
            )
        );

        $this->addColumn(
            'gifted_price', array(
                'header'        => Mage::helper('catalog')->__('Gifted Price'),
                'name'          => 'gifted_price',
                'type'          => 'currency',
                'index'         => 'gifted_price',
                'width'         => 60,
                'filter'   => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Filter_GiftedPrice',
                'renderer'      => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftedPrice',
                'currency_code' => (string)Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE)
            )
        );

        $this->addColumn(
            'gifted_qty_rate', array(
                'header'   => Mage::helper('catalog')->__('Qty Rate'),
                'name'     => 'gifted_qty_rate',
                'type'     => 'text',
                'index'    => 'gifted_qty_rate',
                'width'    => 200,
                'filter'   => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Filter_GiftQtyRate',
                'renderer' => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftQtyRate',
            )
        );

        $this->addColumn(
            'gifted_qty_max', array(
                'header'         => Mage::helper('catalog')->__('Maximum Qty'),
                'name'           => 'gifted_qty_max',
                'type'           => 'number',
                'validate_class' => 'validate-number',
                'index'          => 'gifted_qty_max',
                'width'          => 30,
                'filter'         => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Filter_GiftQtyMax',
                'renderer'       => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftQtyMax',
            )
        );

        $this->addColumn(
            'gifted_position', array(
                'header'         => Mage::helper('catalog')->__('Position'),
                'name'           => 'gifted_position',
                'type'           => 'number',
                'validate_class' => 'validate-number',
                'index'          => 'gifted_position',
                'width'          => 60,
                'editable'       => true,
                'edit_only'      => true,
                'filter'         => false,
                'sortable'       => false,
                'renderer'       => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_Number',
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Merge in the rule data for grid row display
     */
    protected function _afterLoadCollection()
    {
        $rule = $this->getRule();
        $giftProducts = $rule->getGiftedProducts();
        foreach ($this->getCollection() as $item) {
            if (is_array($giftProducts) && array_key_exists($item->getEntityId(), $giftProducts)) {
                $newData = array_merge($item->getData(), $giftProducts[$item->getEntityId()]);
                $item->setData($newData);
            }
        }
    }

}
