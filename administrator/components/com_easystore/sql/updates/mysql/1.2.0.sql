-- v1.2.0
ALTER TABLE `#__easystore_users` ADD `company_name` varchar(100) NULL DEFAULT NULL AFTER `phone`;
ALTER TABLE `#__easystore_users` ADD `vat_information` varchar(50) NULL DEFAULT NULL AFTER `company_name`;

ALTER TABLE `#__easystore_orders` ADD `company_name` varchar(100) NULL DEFAULT NULL AFTER `customer_email`;
ALTER TABLE `#__easystore_orders` ADD `vat_information` varchar(50) NULL DEFAULT NULL AFTER `company_name`;

CREATE TABLE IF NOT EXISTS `#__easystore_coupon_category_map` (
  `coupon_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `buy_get_offer` VARCHAR(10) NULL DEFAULT NULL,
  KEY `idx_coupon_category` (`coupon_id`, `category_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_coupon_product_map` (
  `coupon_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `buy_get_offer` VARCHAR(10) NULL DEFAULT NULL,
  KEY `idx_coupon_product` (`coupon_id`, `product_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `#__easystore_coupons` ADD `coupon_category` VARCHAR(50) DEFAULT 'discount' COMMENT 'discount, free_shipping, sale_price, buy_get_free' AFTER `alias`;
ALTER TABLE `#__easystore_coupons` ADD `sale_value` DECIMAL(13, 2) NOT NULL DEFAULT 0.00 AFTER `discount_value`;
ALTER TABLE `#__easystore_coupons` ADD `applies_to` VARCHAR(50) DEFAULT 'all_products' COMMENT 'all_products, specific_products, specific_categories' AFTER `sale_value`;
ALTER TABLE `#__easystore_coupons` ADD `country_type` VARCHAR(50) NOT NULL DEFAULT 'all' COMMENT 'all, selected' AFTER `applies_to`;
ALTER TABLE `#__easystore_coupons` ADD `selected_countries` TEXT NULL DEFAULT NULL AFTER `country_type`;
ALTER TABLE `#__easystore_coupons` ADD `applies_to_x` VARCHAR(50) DEFAULT 'all_products' COMMENT 'all_products, specific_products, specific_categories' AFTER `selected_countries`;
ALTER TABLE `#__easystore_coupons` ADD `buy_x` int NULL DEFAULT NULL AFTER `applies_to_x`;
ALTER TABLE `#__easystore_coupons` ADD `applies_to_y` VARCHAR(50) DEFAULT 'all_products' COMMENT 'all_products, specific_products, specific_categories' AFTER `buy_x`;
ALTER TABLE `#__easystore_coupons` ADD `get_y` int NULL DEFAULT NULL AFTER `applies_to_y`;
ALTER TABLE `#__easystore_coupons` ADD `coupon_limit_status` tinyint(1) NOT NULL DEFAULT 0 AFTER `get_y`;
ALTER TABLE `#__easystore_coupons` ADD `coupon_limit_value` int NULL AFTER `coupon_limit_status`;
ALTER TABLE `#__easystore_coupons` ADD `usage_limit_status` tinyint(1) NOT NULL DEFAULT 0 AFTER `coupon_limit_value`;
ALTER TABLE `#__easystore_coupons` ADD `usage_limit_value` int NULL AFTER `usage_limit_status`;
ALTER TABLE `#__easystore_coupons` ADD `purchase_requirements` VARCHAR(50) DEFAULT 'no_minimum' COMMENT 'no_minimum, minimum_purchase, minimum_quantity' AFTER `usage_limit_value`;
ALTER TABLE `#__easystore_coupons` ADD `purchase_requirements_value` DECIMAL(13, 2) NULL AFTER `purchase_requirements`;

ALTER TABLE `#__easystore_orders` ADD `coupon_category` VARCHAR(50) DEFAULT 'discount' COMMENT 'discount, free_shipping, sale_price, buy_get_free' AFTER `coupon_id`;

ALTER TABLE `#__easystore_cart` ADD `coupon_category` VARCHAR(50) DEFAULT 'discount' COMMENT 'discount, free_shipping, sale_price, buy_get_free' AFTER `payment_method`;

CREATE TABLE IF NOT EXISTS `#__easystore_user_coupon_usage` (
  `user_id` int NULL DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `coupon_id` int NOT NULL,
  `coupon_count` int NOT NULL DEFAULT 0,
  KEY `idx_coupon_usage` (`user_id`, `coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
