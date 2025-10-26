-- v1.0.5
ALTER TABLE `#__easystore_media` ADD `type` varchar(100) NOT NULL DEFAULT 'image' AFTER `name`;
ALTER TABLE `#__easystore_temp_media` ADD `type` varchar(100) NOT NULL DEFAULT 'image' AFTER `name`;