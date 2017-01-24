<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `usage_limit` tinyint(1) NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();