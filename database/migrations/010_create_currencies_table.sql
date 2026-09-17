-- Migration: 010_create_currencies_table.sql
CREATE TABLE IF NOT EXISTS `currencies` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(10) NOT NULL UNIQUE,
  `name` VARCHAR(64) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert standard currencies
INSERT IGNORE INTO `currencies` (`code`, `name`, `symbol`, `is_default`, `status`) VALUES
('USD', 'US Dollar', '$', 0, 'active'),
('INR', 'Indian Rupee', '₹', 1, 'active'),
('EUR', 'Euro', '€', 0, 'active');
