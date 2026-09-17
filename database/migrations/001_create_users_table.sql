-- Migration: 001_create_users_table.sql
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `balance` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `status` ENUM('active', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
  `api_key` VARCHAR(64) NULL UNIQUE,
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'UTC',
  `reset_token` VARCHAR(128) NULL,
  `reset_token_expires_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_status` (`status`),
  INDEX `idx_users_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
