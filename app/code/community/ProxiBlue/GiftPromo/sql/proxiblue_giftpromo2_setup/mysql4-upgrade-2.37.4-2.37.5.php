<?php
$installer = $this;
$installer->startSetup();

$table = $installer->getTable('sales/quote_address');

try {
    $installer->run(
        "
   ALTER TABLE $table
ADD COLUMN `coupon_code` VARCHAR(45) NULL DEFAULT NULL;

    "
    );
} catch (Exception $e) {
    Mage::logException($e);
}

$installer->endSetup();