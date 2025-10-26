ALTER TABLE `#__easystore_products` ADD `unit` VARCHAR(10) NULL DEFAULT NULL AFTER `weight`;
ALTER TABLE `#__easystore_product_skus` ADD `unit` VARCHAR(10) NULL DEFAULT NULL AFTER `weight`;

CREATE TABLE IF NOT EXISTS `#__easystore_brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  `published` tinyint NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `checked_out` int unsigned DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  `language` char(7) NOT NULL DEFAULT '*',
  PRIMARY KEY (`id`),
  KEY `idx_title` (`title`),
  KEY `idx_published` (`published`),
  UNIQUE KEY `idx_alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `#__easystore_products` ADD `brand_id` INT NULL DEFAULT 0 AFTER `catid`;

CREATE TABLE IF NOT EXISTS `#__easystore_product_upsells` (
  `product_id` bigint unsigned NOT NULL,
  `upsell_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`upsell_id`, `product_id`),
  FOREIGN KEY (`upsell_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__easystore_product_crossells` (
  `product_id` bigint unsigned NOT NULL,
  `crossell_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`crossell_id`, `product_id`),
  FOREIGN KEY (`crossell_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;