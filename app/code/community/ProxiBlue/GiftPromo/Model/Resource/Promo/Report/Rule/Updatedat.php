<?php


/**
 * Rule report resource model with aggregation by updated at
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Resource_Promo_Report_Rule_Updatedat
    extends Mage_SalesRule_Model_Resource_Report_Rule_Updatedat
{

    /**
     * Aggregate coupons reports by orders
     *
     * @throws Exception
     *
     * @param string $aggregationField
     * @param mixed  $from
     * @param mixed  $to
     *
     * @return Mage_SalesRule_Model_Resource_Report_Rule_Createdat
     */
    protected function _aggregateByOrder($aggregationField, $from, $to)
    {
        parent::_aggregateByOrder($aggregationField, $from, $to);
        // now remove any occurance of gift promotions coupons from this report aggregated data
        $table = $this->getMainTable();
        $adapter = $this->_getWriteAdapter();
        $adapter->beginTransaction();
        try {
            $adapter->delete($table, "rule_name LIKE '%(Gift Promotions)%'");
            $adapter->commit();
        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }

        return $this;
    }

}
