-- Complete Production Database Schema for SMM Panel
-- MySQL 8.x / MariaDB compatible

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `admin_audit_logs`;
DROP TABLE IF EXISTS `provider_import_logs`;
DROP TABLE IF EXISTS `cron_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `support_messages`;
DROP TABLE IF EXISTS `support_tickets`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `wallet_transactions`;
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `exchange_rates`;
DROP TABLE IF EXISTS `currencies`;
DROP TABLE IF EXISTS `category_mappings`;
DROP TABLE IF EXISTS `provider_categories`;
DROP TABLE IF EXISTS `provider_services`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `providers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;

-- 1. Users
CREATE TABLE `users` (
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

-- 2. Admins
CREATE TABLE `admins` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_admins_role_id` (`role_id`),
  INDEX `idx_admins_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Roles and Permissions
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(64) NOT NULL,
  `slug` VARCHAR(64) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(64) NOT NULL,
  `slug` VARCHAR(64) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `role_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Super Admin', 'super_admin', 'Full system access and settings control'),
(2, 'Support Agent', 'support_agent', 'Support tickets and order viewing');

-- 4. Categories
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL,
  `slug` VARCHAR(128) NOT NULL UNIQUE,
  `icon` VARCHAR(64) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_categories_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Providers
CREATE TABLE `providers` (
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

-- 6. Services
CREATE TABLE `services` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `provider_id` BIGINT UNSIGNED NULL,
  `provider_service_id` VARCHAR(64) NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `service_type` VARCHAR(64) NOT NULL DEFAULT 'default',
  `provider_cost` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `provider_currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `margin_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
  `margin_value` DECIMAL(18,8) NOT NULL DEFAULT 20.00000000,
  `rate` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `min_quantity` INT NOT NULL DEFAULT 10,
  `max_quantity` INT NOT NULL DEFAULT 10000,
  `drip_feed` TINYINT(1) NOT NULL DEFAULT 0,
  `refill` TINYINT(1) NOT NULL DEFAULT 0,
  `cancel` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_services_cat_status` (`category_id`, `status`),
  INDEX `idx_services_provider` (`provider_id`, `provider_service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Provider Services
CREATE TABLE `provider_services` (
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

-- 8. Provider Categories
CREATE TABLE `provider_categories` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `remote_id` VARCHAR(64) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pc_provider` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Category Mappings
CREATE TABLE `category_mappings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `provider_category_name` VARCHAR(128) NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_provider_cat_map` (`provider_id`, `provider_category_name`),
  INDEX `idx_cm_local_cat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Currencies
CREATE TABLE `currencies` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(10) NOT NULL UNIQUE,
  `name` VARCHAR(64) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `currencies` (`code`, `name`, `symbol`, `is_default`, `status`) VALUES
('USD', 'US Dollar', '$', 0, 'active'),
('INR', 'Indian Rupee', '₹', 1, 'active'),
('EUR', 'Euro', '€', 0, 'active');

-- 11. Exchange Rates
CREATE TABLE `exchange_rates` (
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

INSERT INTO `exchange_rates` (`base_currency`, `quote_currency`, `rate`, `source`, `effective_date`, `status`) VALUES
('USD', 'INR', 83.50000000, 'system_init', CURRENT_DATE(), 'active'),
('INR', 'USD', 0.01197605, 'system_init', CURRENT_DATE(), 'active'),
('USD', 'USD', 1.00000000, 'system_init', CURRENT_DATE(), 'active'),
('INR', 'INR', 1.00000000, 'system_init', CURRENT_DATE(), 'active');

-- 12. Orders
CREATE TABLE `orders` (
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

-- 13. Order Status History
CREATE TABLE `order_status_history` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `old_status` VARCHAR(64) NOT NULL,
  `new_status` VARCHAR(64) NOT NULL,
  `note` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NOT NULL DEFAULT 'system',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_osh_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Wallet Transactions
CREATE TABLE `wallet_transactions` (
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

-- 15. Payments
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `gateway` VARCHAR(64) NOT NULL DEFAULT 'razorpay',
  `order_id` VARCHAR(128) NOT NULL,
  `payment_id` VARCHAR(128) NULL,
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

-- 16. Support Tickets
CREATE TABLE `support_tickets` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `category` VARCHAR(64) NOT NULL DEFAULT 'order',
  `order_id` BIGINT UNSIGNED NULL,
  `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  `status` ENUM('open', 'pending', 'answered', 'closed') NOT NULL DEFAULT 'open',
  `last_reply_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_st_user` (`user_id`),
  INDEX `idx_st_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Support Messages
CREATE TABLE `support_messages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `admin_id` BIGINT UNSIGNED NULL,
  `message` TEXT NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sm_ticket` (`ticket_id`),
  CONSTRAINT `fk_sm_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Settings
CREATE TABLE `settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(128) NOT NULL UNIQUE,
  `value` TEXT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'string',
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('site_name', 'Apex SMM Services', 'string', 'Public brand name'),
('site_description', 'High speed social media marketing growth platform and automated API delivery', 'string', 'Platform meta description'),
('support_email', 'support@example.com', 'string', 'Official support contact email'),
('currency', 'INR', 'string', 'Default panel display currency'),
('currency_symbol', '₹', 'string', 'Default currency symbol'),
('timezone', 'Asia/Kolkata', 'string', 'Panel default timezone'),
('maintenance_mode', '0', 'boolean', 'Site maintenance mode switch'),
('registration_enabled', '1', 'boolean', 'Allow new user registrations'),
('theme', 'classic', 'string', 'Active presentation theme'),
('razorpay_enabled', '1', 'boolean', 'Enable Razorpay payment gateway'),
('razorpay_key_id', '', 'string', 'Razorpay API Key ID'),
('razorpay_key_secret', '', 'string', 'Razorpay API Key Secret'),
('razorpay_webhook_secret', '', 'string', 'Razorpay Webhook Secret'),
('razorpay_mode', 'test', 'string', 'Razorpay mode (test or live)'),
('recent_orders_enabled', '1', 'boolean', 'Show recent orders section on landing page'),
('recent_orders_count', '10', 'integer', 'Number of recent orders to display'),
('recent_orders_mask_user', '1', 'boolean', 'Mask usernames on public recent orders'),
('recent_orders_show_qty', '1', 'boolean', 'Show quantity in public orders list'),
('recent_orders_show_service', '1', 'boolean', 'Show service name in public orders list'),
('recent_orders_show_price', '0', 'boolean', 'Show price in public orders list'),
('recent_orders_refresh_sec', '30', 'integer', 'Polling refresh interval in seconds'),
('rate_limit_per_minute', '60', 'integer', 'Default API/web request limit per minute');

-- 19. Notifications
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(64) NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_global` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user` (`user_id`, `is_read`),
  INDEX `idx_notif_global` (`is_global`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Cron Logs
CREATE TABLE `cron_logs` (
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

-- 21. Provider Import Logs
CREATE TABLE `provider_import_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `imported_count` INT NOT NULL DEFAULT 0,
  `updated_count` INT NOT NULL DEFAULT 0,
  `failed_count` INT NOT NULL DEFAULT 0,
  `details` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pil_provider` (`provider_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Admin Audit Logs
CREATE TABLE `admin_audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(128) NOT NULL,
  `resource` VARCHAR(128) NOT NULL,
  `resource_id` VARCHAR(128) NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_aal_admin` (`admin_id`, `created_at`),
  INDEX `idx_aal_action` (`action`),
  INDEX `idx_aal_resource` (`resource`, `resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
