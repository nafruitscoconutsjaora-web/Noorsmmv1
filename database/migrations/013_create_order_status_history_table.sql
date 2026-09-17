-- Migration: 013_create_order_status_history_table.sql
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `old_status` VARCHAR(64) NOT NULL,
  `new_status` VARCHAR(64) NOT NULL,
  `note` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NOT NULL DEFAULT 'system',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_osh_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
