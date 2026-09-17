-- Migration: 020_create_cron_logs_table.sql
CREATE TABLE IF NOT EXISTS `cron_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_name` VARCHAR(128) NOT NULL,
  `start_time` DATETIME NOT NULL,
  `finish_time` DATETIME NULL,
  `duration_seconds` DECIMAL(10,3) NULL,
  `status` ENUM('running', 'success', 'failed') NOT NULL DEFAULT 'running',
  `message` TEXT NULL,
  `details` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cron_task` (`task_name`, `created_at`),
  INDEX `idx_cron_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
