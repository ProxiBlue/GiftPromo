<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `generate_coupon` int NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `coupon_gen_to` datetime;
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `coupon_email_code` varchar(100);
    "
    );
} catch (Exception $e) {
    //
}



$installer->endSetup();