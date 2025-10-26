ALTER TABLE `#__easystore_products` ADD `metatitle` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'The meta title for the page.' AFTER `language`;
ALTER TABLE `#__easystore_products` ADD `metadesc` VARCHAR(1024) NOT NULL DEFAULT '' COMMENT 'The meta description for the page.' AFTER `metatitle`;
ALTER TABLE `#__easystore_products` ADD `metakey` VARCHAR(1024) NOT NULL DEFAULT '' COMMENT 'The keywords for the page.' AFTER `metadesc`;
ALTER TABLE `#__easystore_products` ADD `metadata` VARCHAR(2048) NOT NULL DEFAULT '' COMMENT 'JSON encoded metadata properties.' AFTER `metakey`;