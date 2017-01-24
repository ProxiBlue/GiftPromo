<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `qualify_message` VARCHAR(255) NOT NULL DEFAULT '';
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();