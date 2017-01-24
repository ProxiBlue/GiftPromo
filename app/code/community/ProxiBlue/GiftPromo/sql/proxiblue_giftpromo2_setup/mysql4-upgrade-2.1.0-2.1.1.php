<?php
$installer = $this;
$installer->startSetup();

$promo_rule_table = $installer->getTable('giftpromo/promo_rule');

// seperated to keep backwards compatibility / module upgrade
try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `customer_ids` varchar(255) NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    Mage::logException($e);
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `stop_rules_processing` int NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    Mage::logException($e);
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `times_used` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Times Used';
    "
    );
} catch (Exception $e) {
    Mage::logException($e);
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `coupon_type` smallint(5) unsigned NOT NULL DEFAULT '1';
    "
    );
} catch (Exception $e) {
    Mage::logException($e);
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `use_auto_generation` smallint(6) unsigned NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    Mage::logException($e);
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `uses_per_coupon` smallint(11) NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    Mage::logException($e);
}


$installer->endSetup();