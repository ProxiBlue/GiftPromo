<?php

/**
 * Abstract Rule entity resource collection model
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
abstract class ProxiBlue_GiftPromo_Model_Resource_Promo_Rule_Collection_Abstract
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{ //Mage_Core_Model_Resource_Db_Collection_Abstract {


    /**
     * Init flag for adding rule website ids to collection result
     *
     * @param bool|null $flag
     *
     * @return Mage_Rule_Model_Resource_Rule_Collection_Abstract
     */
    public function addWebsitesToResult($flag = null)
    {
        if (!Mage::app()->isSingleStoreMode()) {
            $flag = ($flag === null) ? true : $flag;
            $this->setFlag('add_websites_to_result', $flag);
        }

        return $this;
    }

    /**
     * Filter collection to only active or inactive rules
     *
     * @param int $isActive
     *
     * @return Mage_Rule_Model_Resource_Rule_Collection_Abstract
     */
    public function addIsActiveFilter($isActive = 1)
    {
        if (!$this->getFlag('is_active_filter')) {
            $this->addFieldToFilter('is_active', (int)$isActive ? 1 : 0);
            $this->setFlag('is_active_filter', true);
        }

        return $this;
    }

    /**
     * Provide support for website id filter
     *
     * @param string $field
     * @param mixed $condition
     *
     * @return Mage_Rule_Model_Resource_Rule_Collection_Abstract
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'website_ids') {
            return $this->addWebsiteFilter($condition);
        }

        if ($field == 'customer_ids') {
            return $this->addCustomerFilter($condition);
        }

        parent::addFieldToFilter($field, $condition);

        return $this;
    }

    /**
     * Limit rules collection by specific websites
     *
     * @param int|array|Mage_Core_Model_Website $websiteId
     *
     * @return Mage_Rule_Model_Resource_Rule_Collection_Abstract
     */
    public function addWebsiteFilter($websiteId)
    {
        if (!$this->getFlag('is_website_table_joined')) {
            $this->setFlag('is_website_table_joined', true);
            if ($websiteId instanceof Mage_Core_Model_Website) {
                $websiteId = $websiteId->getId();
            }

            $this->getSelect()->where(' (find_in_set(?, cast(website_ids as char)) > 0) ', $websiteId);
        }

        return $this;
    }

    /**
     * Limit rules collection by specific websites
     *
     * @param int|array|Mage_Core_Model_Website $websiteId
     *
     * @return Mage_Rule_Model_Resource_Rule_Collection_Abstract
     */
    public function addCustomerFilter($customerId)
    {
        if (!$this->getFlag('is_customer_table_joined')) {
            $this->setFlag('is_customer_table_joined', true);
            if ($customerId instanceof Mage_Customer_Model_Customer) {
                $customerId = $customerId->getId();
            }

            $this->getSelect()->where(' (find_in_set(?, cast(customer_ids as char)) > 0) ', $customerId);
        }

        return $this;
    }

    /**
     * Add website ids to rules data
     *
     * @return Mage_Rule_Model_Resource_Rule_Collection_Abstract
     */

    protected function _afterLoad()
    {
        parent::_afterLoad();
        // adjust the collection items data.
        /** @var Mage_Rule_Model_Abstract $item */
        foreach ($this->_items as $item) {
            $item->afterLoad();
        }

        return $this;
    }

    /**
     * inject filters before loading collection
     *
     */
    protected function _beforeLoad()
    {
        parent::_beforeLoad();
        if ($this->_dateFilter) {
            $this->addDateFilter();
        }
        $this->setOrder('sort_order', self::SORT_ORDER_DESC);

        return $this;
    }

    /**
     * Filter The date range
     *
     *
     */
    public function addDateFilter()
    {
        $now = Mage::getModel('core/date')->date('Y-m-d');
        $this->getSelect()->where('from_date is null or from_date <= ?', $now);
        $this->getSelect()->where('to_date is null or to_date >= ?', $now);
    }

}
