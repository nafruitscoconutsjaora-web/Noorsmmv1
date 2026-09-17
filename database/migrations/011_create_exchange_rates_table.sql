-- Migration: 011_create_exchange_rates_table.sql
CREATE TABLE IF NOT EXISTS `exchange_rates` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `base_currency` VARCHAR(10) NOT NULL,
  `quote_currency` VARCHAR(10) NOT NULL,
  `rate` DECIMAL(18,8) NOT NULL,
  `source` VARCHAR(64) NOT NULL DEFAULT 'manual',
  `effective_date` DATE NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_pair_date` (`base_currency`, `quote_currency`, `effective_date`),
  INDEX `idx_er_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Base rate configuration (1 USD = 83.50 INR as default database configured value, not hardcoded in code)
INSERT IGNORE INTO `exchange_rates` (`base_currency`, `quote_currency`, `rate`, `source`, `effective_date`, `status`) VALUES
('USD', 'INR', 83.50000000, 'system_init', CURRENT_DATE(), 'active'),
('INR', 'USD', 0.01197605, 'system_init', CURRENT_DATE(), 'active'),
('USD', 'USD', 1.00000000, 'system_init', CURRENT_DATE(), 'active'),
('INR', 'INR', 1.00000000, 'system_init', CURRENT_DATE(), 'active');
