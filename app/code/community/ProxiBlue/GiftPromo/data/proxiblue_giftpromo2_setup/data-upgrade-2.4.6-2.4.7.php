<?php

/**
 * Fix coupon data in sales table to have rule name populated
 */
$adapter = Mage::getSingleton('core/resource')->getConnection('core_read');
$select = $adapter->select();
$sourceTable = Mage::getSingleton('core/resource')->getTableName('sales/order');
$select->from(array('source_table' => $sourceTable), array('order_id' => 'entity_id', 'coupon_code' => 'coupon_code'))
    ->where('coupon_code IS NOT NULL');
$results = $adapter->fetchAll($select);
foreach ($results as $result) {
    $couponModel = Mage::getModel('giftpromo/promo_coupon');
    $couponModel->loadByCode($result['coupon_code']);
    $ruleId = $couponModel->getRuleId();
    if (empty($ruleId)) {
        continue;
    }
    $ruleModel = Mage::getModel('giftpromo/promo_rule');
    $ruleModel->load($ruleId);

    $order = mage::getModel('sales/order')->load($result['order_id']);
    $order->setCouponRuleName($ruleModel->getRuleName() . ' (Gift Promotions) ');
    $order->save();
}
