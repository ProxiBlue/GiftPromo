<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

// seperated to keep backwards compatibility / module upgrade
try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `allow_gift_selection_count` integer DEFAULT '1';
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();