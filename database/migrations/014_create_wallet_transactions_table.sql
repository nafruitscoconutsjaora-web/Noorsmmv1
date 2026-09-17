-- Migration: 014_create_wallet_transactions_table.sql
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NULL,
  `payment_id` BIGINT UNSIGNED NULL,
  `type` ENUM('credit', 'debit', 'refund') NOT NULL,
  `amount` DECIMAL(18,8) NOT NULL,
  `balance_before` DECIMAL(18,8) NOT NULL,
  `balance_after` DECIMAL(18,8) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `reference_id` VARCHAR(128) NULL,
  `status` ENUM('completed', 'pending', 'failed') NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_wt_user` (`user_id`, `created_at`),
  INDEX `idx_wt_type` (`type`),
  INDEX `idx_wt_ref` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
