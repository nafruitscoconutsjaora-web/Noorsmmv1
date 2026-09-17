-- Migration: 007_create_provider_services_table.sql
CREATE TABLE IF NOT EXISTS `provider_services` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `remote_service_id` VARCHAR(64) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `category_name` VARCHAR(128) NOT NULL,
  `rate` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `min_qty` INT NOT NULL DEFAULT 1,
  `max_qty` INT NOT NULL DEFAULT 10000,
  `type` VARCHAR(64) NOT NULL DEFAULT 'Default',
  `refill` TINYINT(1) NOT NULL DEFAULT 0,
  `cancel` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `raw_data` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_provider_service` (`provider_id`, `remote_service_id`),
  INDEX `idx_ps_provider` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
