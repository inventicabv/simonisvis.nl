ALTER TABLE `#__easystore_products` ADD `is_taxable` TINYINT NOT NULL DEFAULT 1 AFTER `has_variants`;
ALTER TABLE `#__easystore_product_skus` ADD `is_taxable` TINYINT NOT NULL DEFAULT 1 AFTER `inventory_amount`;
ALTER TABLE `#__easystore_orders` ADD `shipping_tax` decimal(13,2) DEFAULT 0 AFTER `sale_tax`;
ALTER TABLE `#__easystore_orders` ADD `shipping_tax_rate` decimal(13,2) DEFAULT 0 AFTER `shipping_tax`;
ALTER TABLE `#__easystore_order_product_map` ADD `cart_item` TEXT AFTER `price`;