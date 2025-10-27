CREATE TABLE IF NOT EXISTS `#__postnl_shipments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL COMMENT 'Reference to #__easystore_orders',
  `barcode` varchar(50) NOT NULL COMMENT 'PostNL tracking barcode',
  `tracking_url` varchar(500) NOT NULL COMMENT 'Track & Trace URL',
  `label_content` mediumtext COMMENT 'Base64 encoded label (PDF/ZPL)',
  `label_format` varchar(10) NOT NULL DEFAULT 'PDF' COMMENT 'Label format: PDF or ZPL',
  `status` varchar(20) NOT NULL DEFAULT 'created' COMMENT 'Shipment status',
  `api_response` text COMMENT 'Full API response JSON',
  `created_date` datetime NOT NULL,
  `modified_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
