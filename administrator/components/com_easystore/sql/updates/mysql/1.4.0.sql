ALTER TABLE `#__easystore_products` ADD `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;

ALTER TABLE `#__easystore_categories` ADD `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `#__easystore_tags` ADD `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `#__easystore_coupons` ADD `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `#__easystore_reviews` ADD `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `#__easystore_orders` ADD `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `#__easystore_users` ADD `asset_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;

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
