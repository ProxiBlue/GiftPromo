<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `keep_validated_on_stop` TINYINT(1) DEFAULT 0;
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();