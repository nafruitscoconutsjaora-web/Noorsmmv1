-- Migration: 009_create_category_mappings_table.sql
CREATE TABLE IF NOT EXISTS `category_mappings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `provider_category_name` VARCHAR(128) NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_provider_cat_map` (`provider_id`, `provider_category_name`),
  INDEX `idx_cm_local_cat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
