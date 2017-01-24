<?php

/**
 * Promo rule grid display
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_Promo_Rule_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('giftpromo_promo_rule_grid');
        $this->setDefaultSort('sort_order');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getRuleId()));
    }

    /**
     * Prepare the collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('giftpromo/promo_rule')->getResourceCollection();
        $collection->addWebsitesToResult();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'rule_id', array(
                'header' => Mage::helper('giftpromo')->__('ID'),
                'align'  => 'right',
                'width'  => '50px',
                'index'  => 'rule_id',
            )
        );

        $this->addColumn(
            'name', array(
                'header' => Mage::helper('giftpromo')->__('Rule Name'),
                'align'  => 'left',
                'index'  => 'rule_name',
            )
        );

//        $this->addColumn(
//            'conditions', array(
//                'header'   => Mage::helper('giftpromo')->__('Rule Conditions'),
//                'align'    => 'left',
//                'index'    => 'rule_id',
//                'filter'   => false,
//                'type'     => 'text',
//                'as_html'  => 1,
//                'width'  => '300px',
//                'renderer' => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_Conditions',
//
//            )
//        );

        $this->addColumn(
            'from_date', array(
                'header' => Mage::helper('giftpromo')->__('Date Start'),
                'align'  => 'left',
                'width'  => '120px',
                'type'   => 'date',
                'index'  => 'from_date',
            )
        );

        $this->addColumn(
            'to_date', array(
                'header'  => Mage::helper('giftpromo')->__('Date Expire'),
                'align'   => 'left',
                'width'   => '120px',
                'type'    => 'date',
                'default' => '--',
                'index'   => 'to_date',
            )
        );

        $this->addColumn(
            'allow_gift_selection', array(
                'header'  => Mage::helper('giftpromo')->__('Gift Selection'),
                'align'   => 'left',
                'index'   => 'allow_gift_selection',
                'filter'  => false,
                'type'    => 'options',
                'options' => array(
                    1 => 'Yes',
                    0 => 'No',
                ),
            )
        );

        $this->addColumn(
            'gifted_products', array(
                'header'   => Mage::helper('giftpromo')->__('Gifted Products'),
                'align'    => 'left',
                'index'    => 'gifted_products',
                'filter'   => false,
                'type'     => 'text',
                'renderer' => 'ProxiBlue_GiftPromo_Block_Adminhtml_Widget_Grid_Column_Renderer_GiftedProducts',
                'sortable' => false,

            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'rule_website', array(
                    'header'   => Mage::helper('giftpromo')->__('Website'),
                    'align'    => 'left',
                    'index'    => 'website_ids',
                    'type'     => 'options',
                    'sortable' => false,
                    'options'  => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(),
                    'width'    => 200,
                )
            );
        }

        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt' => -1))
            ->load()
            ->toOptionHash();

        $this->addColumn(
            'group', array(
                'header'  => Mage::helper('customer')->__('Group'),
                'width'   => '100',
                'index'   => 'customer_ids',
                'type'    => 'options',
                'options' => $groups,

            )
        );

        $this->addColumn(
            'is_active', array(
                'header'  => Mage::helper('giftpromo')->__('Status'),
                'align'   => 'left',
                'width'   => '80px',
                'index'   => 'is_active',
                'type'    => 'options',
                'options' => array(
                    1 => 'Active',
                    0 => 'Inactive',
                ),
            )
        );

        $this->addColumn(
            'stop_rules_processing', array(
                'header'  => Mage::helper('giftpromo')->__('Stop Processing'),
                'align'   => 'left',
                'width'   => '80px',
                'index'   => 'stop_rules_processing',
                'type'    => 'options',
                'options' => array(
                    1 => 'Yes',
                    0 => 'No',
                ),
            )
        );

        $this->addColumn(
            'sort_order', array(
                'header' => Mage::helper('giftpromo')->__('Priority'),
                'align'  => 'right',
                'index'  => 'sort_order',
            )
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('rule_id');

        $this->getMassactionBlock()->addItem(
            'delete', array(
                'label'   => Mage::helper('giftpromo')->__('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('giftpromo')->__('Are you sure?')
            )
        );

        $statuses = array(
            1 => 'Active',
            0 => 'Inactive',
        );

//        /array_unshift($statuses, array('label' => '', 'value' => ''));
        $this->getMassactionBlock()->addItem(
            'status', array(
                'label'      => Mage::helper('giftpromo')->__('Change status'),
                'url'        => $this->getUrl('*/*/massStatus', array('_current' => true)),
                'additional' => array(
                    'visibility' => array(
                        'name'   => 'status',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => Mage::helper('giftpromo')->__('Status'),
                        'values' => $statuses
                    )
                )
            )
        );

        $this->getMassactionBlock()->addItem(
            'duplicate', array(
                'label'   => Mage::helper('giftpromo')->__('Copy Rule'),
                'url'     => $this->getUrl('*/*/massDuplicate')
            )
        );
        
        Mage::dispatchEvent('adminhtml_giftpromo_grid_prepare_massaction', array('block' => $this));

        return $this;
    }

    /**
     * Sets sorting order by some column
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ?
                $column->getFilterIndex() : $column->getIndex();
            $collection->setOrder($columnIndex, strtoupper($column->getDir()));
        }

        return $this;
    }

}
