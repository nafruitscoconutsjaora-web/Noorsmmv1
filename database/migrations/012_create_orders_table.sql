-- Migration: 012_create_orders_table.sql
CREATE TABLE IF NOT EXISTS `orders` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `provider_id` BIGINT UNSIGNED NULL,
  `provider_order_id` VARCHAR(128) NULL,
  `link` VARCHAR(2048) NOT NULL,
  `quantity` INT NOT NULL,
  `charge` DECIMAL(18,8) NOT NULL,
  `start_counter` INT NULL DEFAULT NULL,
  `remains` INT NULL DEFAULT NULL,
  `status` ENUM('pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'failed') NOT NULL DEFAULT 'pending',
  `provider_response` TEXT NULL,
  `error_message` TEXT NULL,
  `refill_status` VARCHAR(64) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_orders_user` (`user_id`, `created_at`),
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_provider` (`provider_id`, `provider_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
