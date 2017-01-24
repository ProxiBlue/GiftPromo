<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `coupon_prefix` varchar(100);
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `coupon_suffix` varchar(100);
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `coupon_length` int;
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `coupon_format` varchar(10);
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();