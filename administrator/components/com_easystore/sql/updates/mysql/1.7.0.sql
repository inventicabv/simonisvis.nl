-- version 1.7.0
-- Add custom invoice ID column to existing orders table for backward compatibility
-- This update allows custom invoice IDs to be stored with orders
ALTER TABLE `#__easystore_orders` ADD COLUMN `custom_invoice_id` VARCHAR(255) DEFAULT NULL AFTER `order_token`;

-- Add index for performance optimization
ALTER TABLE `#__easystore_orders` ADD INDEX `idx_custom_invoice_id` (`custom_invoice_id`);
