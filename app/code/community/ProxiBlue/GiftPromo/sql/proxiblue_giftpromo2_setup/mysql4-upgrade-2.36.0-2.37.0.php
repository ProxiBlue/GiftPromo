<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `icon_file` varchar(300) DEFAULT NULL;
    "
    );
} catch (Exception $e) {
    Mage::logException($e);
}

$installer->endSetup();