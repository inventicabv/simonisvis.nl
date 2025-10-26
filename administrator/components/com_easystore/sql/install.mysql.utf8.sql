CREATE TABLE IF NOT EXISTS `#__easystore_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `description` text,
  `parent_id` bigint unsigned NOT NULL DEFAULT 0,
  `is_gift_card` tinyint NOT NULL DEFAULT '0',
  `is_uncategorised` tinyint NOT NULL DEFAULT '0',
  `lft` int NOT NULL DEFAULT 0,
  `rgt` int NOT NULL DEFAULT 0,
  `level` int unsigned NOT NULL DEFAULT 0,
  `image` varchar(255) NULL,
  `path` varchar(400) NOT NULL DEFAULT '',
  `published` tinyint NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `language` char(7) NOT NULL DEFAULT '*',
  `checked_out` int unsigned DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE(`alias`(100)),
  KEY `idx_title_description` (`title`(100), `description`(100)),
  KEY `idx_publish_access` (`published`, `access`),
  KEY `idx_alias` (`alias` (100)),
  KEY `idx_path` (`path` (100)),
  KEY `idx_language` (`language`),
  KEY `idx_lft_rgt` (`lft`, `rgt`),
  KEY `idx_image` (`image`(100))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `published` tinyint NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `language` char(7) NOT NULL DEFAULT '*',
  `checked_out` int unsigned DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_title_description` (`title`(100), `description`(100))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `catid` bigint unsigned NOT NULL DEFAULT 0,
  `brand_id` int NULL DEFAULT 0,
  `weight` varchar(255) DEFAULT NULL,
  `unit` varchar(10) DEFAULT NULL,
  `dimension` varchar(255) DEFAULT NULL,
  `regular_price` decimal(13, 2) DEFAULT NULL,
  `has_sale` tinyint NOT NULL DEFAULT 0,
  `has_variants` tinyint NOT NULL DEFAULT 0,
  `is_taxable` TINYINT NOT NULL DEFAULT 1,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_tracking_inventory` tinyint NOT NULL DEFAULT 0,
  `inventory_status` tinyint NOT NULL DEFAULT 0,
  `enable_out_of_stock_sell` tinyint NOT NULL DEFAULT 0,
  `quantity` int NOT NULL DEFAULT 0,
  `sku` varchar(100) NOT NULL DEFAULT '',
  `additional_data` text DEFAULT NULL,
  `discount_type` varchar(50) DEFAULT 'percent',
  `discount_value` decimal(13, 2) DEFAULT NULL,
  `published` tinyint NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `language` char(7) NOT NULL DEFAULT '*',
  `metatitle` varchar(255) NOT NULL DEFAULT '' COMMENT 'The meta title for the page.',
  `metadesc` varchar(1024) NOT NULL DEFAULT '' COMMENT 'The meta description for the page.',
  `metakey` varchar(1024) NOT NULL DEFAULT '' COMMENT 'The keywords for the page.',
  `metadata` varchar(2048) NOT NULL DEFAULT '' COMMENT 'JSON encoded metadata properties.',
  `checked_out` int unsigned DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_title_description` (`title`(100), `description`(100)),
  KEY `idx_published` (`published`),
  KEY `idx_has_sale` (`has_sale`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_product_tag_map` (
  `product_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  KEY `idx_product_tag` (`product_id`, `tag_id`),
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `#__easystore_tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL DEFAULT 'image',
  `is_featured` tinyint DEFAULT 0,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `src` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT '',
  `ordering` int NOT NULL DEFAULT 0,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_product` FOREIGN KEY (`product_id`)
  REFERENCES `#__easystore_products` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_temp_media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL DEFAULT 'image',
  `is_featured` tinyint DEFAULT 0,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `src` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT '',
  `ordering` int NOT NULL DEFAULT 0,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_client_id_is_featured` (`client_id`, `is_featured`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_coupons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `coupon_category` VARCHAR(50) DEFAULT 'discount' COMMENT 'discount, free_shipping, sale_price, buy_get_free',
  `code` VARCHAR(255) NOT NULL,
  `discount_type` varchar(50) DEFAULT 'percent',
  `discount_value` DECIMAL(13, 2) NOT NULL DEFAULT 0.00,
  `sale_value` DECIMAL(13, 2) NOT NULL DEFAULT 0.00,
  `applies_to` VARCHAR(50) DEFAULT 'all_products' COMMENT 'all_products, specific_products, specific_categories',
  `country_type` VARCHAR(50) NOT NULL DEFAULT 'all' COMMENT 'all, selected',
  `selected_countries` TEXT NULL DEFAULT NULL,
  `applies_to_x` VARCHAR(50) DEFAULT 'all_products' COMMENT 'all_products, specific_products, specific_categories',
  `buy_x` int NULL DEFAULT NULL,
  `applies_to_y` VARCHAR(50) DEFAULT 'all_products' COMMENT 'all_products, specific_products, specific_categories',
  `get_y` int NULL DEFAULT NULL,
  `coupon_limit_status` tinyint(1) NOT NULL DEFAULT 0,
  `coupon_limit_value` int NULL,
  `usage_limit_status` tinyint(1) NOT NULL DEFAULT 0,
  `usage_limit_value` int NULL,
  `purchase_requirements` VARCHAR(50) DEFAULT 'no_minimum' COMMENT 'no_minimum, minimum_purchase, minimum_quantity',
  `purchase_requirements_value` DECIMAL(13, 2) NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NULL,
  `has_date` tinyint NOT NULL DEFAULT 0,
  `published` tinyint NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `checked_out` int unsigned DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_title` (`title`(100)),
  KEY `idx_code` (`code`(100)),
  UNIQUE(`code`(100))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `#__easystore_wishlist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_wish` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `product_id` bigint unsigned NOT NULL,
  `rating` int unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `review` text DEFAULT NULL,
  `access` int unsigned NOT NULL DEFAULT 0,
  `published` tinyint NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;


-- product variant option tables
CREATE TABLE IF NOT EXISTS `#__easystore_product_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL DEFAULT 'color',
  `ordering` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_name` (`name`(100)),
  UNIQUE (`product_id`, `name`(100)),
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__easystore_product_option_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `option_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `ordering` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_name` (`name`(100)),
  UNIQUE (`product_id`, `option_id`, `name`(100)),
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`option_id`) REFERENCES `#__easystore_product_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__easystore_product_skus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `image_id` bigint unsigned DEFAULT NULL,
  `combination_name` varchar(500) NOT NULL,
  `combination_value` varchar(500) NOT NULL,
  `price` decimal(13,2) DEFAULT NULL,
  `inventory_status` tinyint NOT NULL DEFAULT 0,
  `inventory_amount` int DEFAULT NULL,
  `is_taxable` TINYINT NOT NULL DEFAULT 1,
  `ordering` int NOT NULL DEFAULT 0,
  `sku` varchar(100) NOT NULL DEFAULT '',
  `weight` varchar(100) NOT NULL DEFAULT '',
  `unit` varchar(10) DEFAULT NULL,
  `visibility` tinyint NOT NULL DEFAULT 0,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `price` (`price`),
  UNIQUE (`product_id`, `combination_value`(100)),
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__easystore_coupon_product_sku_map` (
  `coupon_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `sku_id` bigint unsigned NOT NULL,
  KEY `idx_coupon_product_sku` (`coupon_id`, `product_id`, `sku_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;


-- order related tables
CREATE TABLE IF NOT EXISTS `#__easystore_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `creation_date` datetime NULL DEFAULT NULL,
  `customer_id` bigint unsigned NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `company_name` varchar(100) NULL DEFAULT NULL,
  `company_id` varchar(100) NULL DEFAULT NULL,
  `vat_information` varchar(50) NULL DEFAULT NULL,
  `shipping_address` mediumtext,
  `billing_address` mediumtext,
  `customer_note` text NULL,
  `payment_status` varchar(50) NOT NULL DEFAULT 'unpaid',
  `fulfilment` varchar(50) NOT NULL DEFAULT 'unfulfilled',
  `order_status` varchar(50) NOT NULL DEFAULT 'draft',
  `is_guest_order` TINYINT NOT NULL DEFAULT '0',
  `discount_type` varchar(50) DEFAULT 'percent',
  `discount_value` decimal(13,2) DEFAULT NULL,
  `discount_reason` varchar(255) DEFAULT NULL,
  `payment_error_reason` varchar(255) DEFAULT NULL,
  `shipping` text DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `shipping_carrier` varchar(100) DEFAULT NULL,
  `tracking_url` varchar(500) DEFAULT NULL,
  `coupon_id` int DEFAULT NULL,
  `coupon_category` VARCHAR(50) DEFAULT 'discount' COMMENT 'discount, free_shipping, sale_price, buy_get_free',
  `coupon_code` varchar(255) NULL,
  `coupon_type` varchar(20) DEFAULT NULL,
  `coupon_amount` decimal(13,2) DEFAULT 0,
  `is_tax_included_in_price` TINYINT NOT NULL DEFAULT 1,
  `sale_tax` decimal(13,2) DEFAULT 0,
  `shipping_tax` decimal(13,2) DEFAULT 0,
  `shipping_tax_rate` decimal(13,2) DEFAULT 0,
  `shipping_type` varchar(255) NULL,
  `shipping_value` decimal(13,2) DEFAULT 0,
  `payment_method` varchar(255) NULL,
  `is_send_shipping_confirmation_email` tinyint NOT NULL DEFAULT 1,
  `published` tinyint NOT NULL DEFAULT 1,
  `access` int UNSIGNED NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `checked_out` int UNSIGNED DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  `order_token` varchar(255) DEFAULT NULL,
  `custom_invoice_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY(`id`),
  KEY `idx_order_payment_status`(`payment_status`),
  KEY `idx_order_fulfillment`(`fulfilment`),
  KEY `idx_order_order_status`(`order_status`),
  KEY `idx_custom_invoice_id`(`custom_invoice_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT=1000;


CREATE TABLE IF NOT EXISTS `#__easystore_order_product_map` (
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned NULL DEFAULT NULL,
  `discount_type` varchar(50) DEFAULT 'percent',
  `discount_value` decimal(13, 2) DEFAULT NULL,
  `discount_reason` varchar(255) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT 0,
  `price` decimal(13, 2) NOT NULL DEFAULT 0,
  `cart_item` TEXT,
  KEY `idx_order_product_variant` (`order_id`, `product_id`, `variant_id`),
  FOREIGN KEY (`order_id`) REFERENCES `#__easystore_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__easystore_order_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `activity_type` varchar(100) NOT NULL DEFAULT 'order_created',
  `activity_value` varchar(255) NULL DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT NOW(),
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY(`id`),
  KEY `idx_order_id`(`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `#__easystore_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_order_refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `refund_value` decimal(13,2) NOT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT NOW(),
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY(`id`),
  KEY `idx_order_id`(`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `#__easystore_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__easystore_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `user_id` int NOT NULL,
  `user_type` varchar(50) NOT NULL DEFAULT 'customer',
  `phone` varchar(50) NULL,
  `company_name` varchar(100) NULL DEFAULT NULL,
  `company_id` varchar(100) NULL DEFAULT NULL,
  `vat_information` varchar(50) NULL DEFAULT NULL,
  `image` text,
  `shipping_address` text NULL,
  `is_billing_and_shipping_address_same` tinyint NOT NULL DEFAULT 0,
  `billing_address` text NULL,
  `created` datetime NOT NULL DEFAULT NOW(),
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `idx_user_id`(`user_id`),
  KEY `idx_phone`(`phone`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__easystore_cart` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NULL,
  `status` varchar(100) DEFAULT NULL COMMENT 'Possible values (cart, information, shipping, payment)',
  `token` varchar(200) NOT NULL DEFAULT '',
  `shipping_method` mediumtext,
  `payment_method` varchar(255) DEFAULT NULL,
  `coupon_category` VARCHAR(50) DEFAULT 'discount' COMMENT 'discount, free_shipping, sale_price, buy_get_free',
  `coupon_code` varchar(100) DEFAULT NULL,
  `coupon_type` varchar(20) DEFAULT NULL,
  `coupon_amount` decimal(13,2) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT NOW(),
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `sku_id` bigint unsigned NULL,
  `quantity` int unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT NOW(),
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY(`id`),
  FOREIGN KEY (`cart_id`) REFERENCES `#__easystore_cart` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_guests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `shipping_address` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE(`email`),
  KEY `idx_email`(`email`)
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_user_coupon_usage` (
  `user_id` int NULL DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `coupon_id` int NOT NULL,
  `coupon_count` int NOT NULL DEFAULT 0,
  KEY `idx_coupon_usage` (`user_id`, `coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `description` text,
  `image` varchar(255) NOT NULL DEFAULT '',
  `published` tinyint NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `language` char(7) NOT NULL DEFAULT '*',
  `metatitle` varchar(255) NOT NULL DEFAULT '' COMMENT 'The meta title for the page.',
  `metadesc` varchar(1024) NOT NULL DEFAULT '' COMMENT 'The meta description for the page.',
  `metakey` varchar(1024) NOT NULL DEFAULT '' COMMENT 'The keywords for the page.',
  `metadata` varchar(2048) NOT NULL DEFAULT '' COMMENT 'JSON encoded metadata properties.',
  `checked_out` int unsigned DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_title` (`title`),
  KEY `idx_published` (`published`),
  UNIQUE KEY `idx_alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_collection_product_map` (
  `collection_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`collection_id`, `product_id`),
  FOREIGN KEY (`collection_id`) REFERENCES `#__easystore_collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `#__easystore_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__easystore_brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  `published` tinyint NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
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
