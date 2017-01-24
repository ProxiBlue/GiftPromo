<?php
$installer = $this;
$installer->startSetup();

$couponTable = $installer->getTable('giftpromo/promo_coupon');
$promo_rule_table = $installer->getTable('giftpromo/promo_rule');


// rule based promos  
$installer->run(
    "
CREATE TABLE IF NOT EXISTS `{$promo_rule_table}` (
  `rule_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL
, `description` text NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `allow_gift_selection` tinyint(1) NOT NULL DEFAULT '0',
  `conditions_serialized` mediumtext NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
  `giftpromo` mediumtext NOT NULL default '',
  `website_ids` text,
  `stop_rules_processing` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`),
  KEY `sort_order` (`is_active`,`sort_order`,`to_date`,`from_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
"
);


// seperated to keep backwards compatibility / module upgrade
try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `customer_ids` varchar(255) NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `stop_rules_processing` int NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `times_used` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Times Used';
    "
    );
} catch (Exception $e) {
    //
}

$installer->run(
    "
   DROP TABLE IF EXISTS `{$couponTable}`; 
   CREATE TABLE  IF NOT EXISTS `{$couponTable}` ( 
  `coupon_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Coupon Id',
  `rule_id` int(10) unsigned NOT NULL COMMENT 'Rule Id',
  `code` varchar(255) DEFAULT NULL COMMENT 'Code',
  `usage_limit` int(10) unsigned DEFAULT NULL COMMENT 'Usage Limit',
  `usage_per_customer` int(10) unsigned DEFAULT NULL COMMENT 'Usage Per Customer',
  `times_used` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Times Used',
  `expiration_date` timestamp NULL DEFAULT NULL COMMENT 'Expiration Date',
  `is_primary` smallint(5) unsigned DEFAULT NULL COMMENT 'Is Primary',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Coupon Code Creation Date',
  `type` smallint(6) DEFAULT '0' COMMENT 'Coupon Code Type',
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `UNQ_GIFTPROMO_PROMO_COUPON_CODE` (`code`),
  UNIQUE KEY `UNQ_GIFTPROMO_PROMO_COUPON_RULE_ID_IS_PRIMARY` (`rule_id`,`is_primary`),
  KEY `IDX_GIFTPROMO_PROMO_COUPON_RULE_ID` (`rule_id`),
  CONSTRAINT `FK_GIFTPROMO_PROMO_COUPON_RULE_ID_GIFTPROMO_RULE_ID` FOREIGN KEY (`rule_id`) REFERENCES `{$promo_rule_table}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Giftpromo Coupon'
"
);

$couponUsageTable = $installer->getTable('giftpromo/promo_coupon_usage');

$installer->run(
    "
DROP TABLE IF EXISTS `{$couponUsageTable}`;     
CREATE TABLE  IF NOT EXISTS `{$couponUsageTable}` (
  `coupon_id` int(10) unsigned NOT NULL COMMENT 'Coupon Id',
  `customer_id` int(10) unsigned NOT NULL COMMENT 'Customer Id',
  `times_used` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Times Used',
  PRIMARY KEY (`coupon_id`,`customer_id`),
  KEY `IDX_GIFTPROMO_PROMO_COUPON_USAGE_COUPON_ID` (`coupon_id`),
  KEY `IDX_GIFTPROMO_PROMO_COUPON_USAGE_CUSTOMER_ID` (`customer_id`),
  CONSTRAINT `FK_GIFTPROMO_PROMO_COUPON_USAGE_COUPON_ID_GIFTPROMO_COUPON_ID` FOREIGN KEY (`coupon_id`) REFERENCES `{$couponTable}` (`coupon_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_GIFTPROMO_PROMO_COUPON_USAGE_CUSTOMER_ID_CUSTOMER_ENTITY_ID` FOREIGN KEY (`customer_id`) REFERENCES `{$installer->getTable(
        'customer_entity'
    )}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Giftpromo Coupon Usage'
"
);

try {
    $installer->run(
        "
    ALTER TABLE `{$installer->getTable('sales/order')}`
        ADD COLUMN `applied_gift_rule_ids` VARCHAR(255) DEFAULT '';
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `coupon_type` smallint(5) unsigned NOT NULL DEFAULT '1';
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `use_auto_generation` smallint(6) unsigned NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$promo_rule_table}`
        ADD COLUMN `uses_per_coupon` smallint(11) NOT NULL DEFAULT '0';
    "
    );
} catch (Exception $e) {
    //
}


$installer->addAttribute(
    'order', 'applied_gift_rule_ids', array(
        'label'    => 'Applied Gift Rule Ids',
        'visible'  => 0,
        'required' => 0,
        'type'     => 'static'
    )
);

try {
    $installer->run(
        "
    ALTER TABLE `{$installer->getTable('sales/order_item')}`
        ADD COLUMN `applied_gift_rule_ids` VARCHAR(255) DEFAULT '';
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$installer->getTable('sales/quote')}`
        ADD COLUMN `applied_gift_rule_ids` VARCHAR(255) DEFAULT '';
    "
    );
} catch (Exception $e) {
    //
}

try {
    $installer->run(
        "
    ALTER TABLE `{$installer->getTable('sales/quote_item')}`
        ADD COLUMN `applied_gift_rule_ids` VARCHAR(255) DEFAULT '';
    "
    );
} catch (Exception $e) {
    //
}


$this->addAttribute(
    'catalog_product',
    'gift_promotion_icon',
    array(
        'group'            => 'Images',
        'type'             => 'varchar',
        'frontend'         => 'catalog/product_attribute_frontend_image',
        'label'            => 'Gift Promotion Icon',
        'input'            => 'media_image',
        'class'            => '',
        'source'           => '',
        'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible'          => true,
        'required'         => false,
        'user_defined'     => false,
        'default'          => '',
        'searchable'       => false,
        'filterable'       => false,
        'comparable'       => false,
        'visible_on_front' => false,
        'unique'           => false,
    )
);


$installer->run(
    "

CREATE TABLE  IF NOT EXISTS `{$installer->getTable('giftpromo/promo_rule_customer')}` (
  `rule_customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Rule Customer Id',
  `rule_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Rule Id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Customer Id',
  `times_used` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Times Used',
  PRIMARY KEY (`rule_customer_id`),
  KEY `IDX_GIFTPROMO_CUSTOMER_RULE_ID_CUSTOMER_ID` (`rule_id`,`customer_id`),
  KEY `IDX_GIFTPROMO_CUSTOMER_CUSTOMER_ID_RULE_ID` (`customer_id`,`rule_id`),
  CONSTRAINT `FK_GIFTPROMO_CUSTOMER_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID` FOREIGN KEY (`customer_id`) REFERENCES `{$installer->getTable(
        'customer_entity'
    )}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_GIFTPROMO_CUSTOMER_RULE_ID_GIFTPROMO_RULE_ID` FOREIGN KEY (`rule_id`) REFERENCES `{$promo_rule_table}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='GiftPromo Customer'

"
);


$installer->endSetup();