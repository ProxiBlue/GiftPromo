<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `force_stop_rules_processing` int NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();