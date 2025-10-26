-- v1.0.1
CREATE TABLE IF NOT EXISTS `#__easystore_guests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `shipping_address` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE(`email`),
  KEY `idx_email`(`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;


ALTER TABLE `#__easystore_orders` ADD `customer_email` varchar(100) DEFAULT NULL AFTER `customer_id`;
ALTER TABLE `#__easystore_orders` ADD `is_guest_order` tinyint NOT NULL DEFAULT '0' AFTER `order_status`;
ALTER TABLE `#__easystore_orders` ADD `shipping_address` mediumtext AFTER `customer_email`;
ALTER TABLE `#__easystore_orders` ADD `billing_address` mediumtext AFTER `shipping_address`;
ALTER TABLE `#__easystore_orders` ADD `published` tinyint NOT NULL DEFAULT 1 AFTER `is_send_shipping_confirmation_email`;


ALTER TABLE `#__easystore_orders` CHANGE `discount_type` `discount_type` VARCHAR(50) DEFAULT 'percent';
ALTER TABLE `#__easystore_orders` CHANGE `order_status` `order_status` VARCHAR(50) NOT NULL DEFAULT 'draft';
ALTER TABLE `#__easystore_orders` CHANGE `is_send_shipping_confirmation_email` `is_send_shipping_confirmation_email` tinyint NOT NULL DEFAULT 1;