<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

// seperated to keep backwards compatibility / module upgrade
try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `block_qty_changes` integer DEFAULT 0;
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();