<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `add_to_cart_message` varchar(255) NULL DEFAULT '';
    "
    );
} catch (Exception $e) {
    //
}

$installer->endSetup();