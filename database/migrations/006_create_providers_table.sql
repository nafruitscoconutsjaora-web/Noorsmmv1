-- Migration: 006_create_providers_table.sql
CREATE TABLE IF NOT EXISTS `providers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL,
  `api_url` VARCHAR(255) NOT NULL,
  `api_key` VARCHAR(255) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `balance` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `last_synced_at` DATETIME NULL,
  `last_sync_status` VARCHAR(64) NULL,
  `last_sync_error` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_providers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
