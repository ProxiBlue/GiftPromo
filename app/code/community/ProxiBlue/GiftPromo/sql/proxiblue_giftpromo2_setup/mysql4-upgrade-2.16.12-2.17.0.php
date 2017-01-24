<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `block_rules` varchar(255) NULL DEFAULT '';
    "
    );
} catch (Exception $e) {
    //
}
try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `block_rules_message` text NULL DEFAULT '';
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();