-- Migration: 015_create_payments_table.sql
CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `gateway` VARCHAR(64) NOT NULL DEFAULT 'razorpay',
  `order_id` VARCHAR(128) NOT NULL, -- Razorpay order_id / gateway reference
  `payment_id` VARCHAR(128) NULL,   -- Razorpay payment_id (upon success)
  `signature` VARCHAR(255) NULL,
  `amount` DECIMAL(18,8) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `fee` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `payload` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_payments_order_id` (`order_id`),
  INDEX `idx_payments_user` (`user_id`, `created_at`),
  INDEX `idx_payments_status` (`status`),
  INDEX `idx_payments_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
