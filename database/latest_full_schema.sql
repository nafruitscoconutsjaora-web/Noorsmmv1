-- ============================================================================
-- SMM PANEL — LATEST COMPLETE PRODUCTION DATABASE SCHEMA
-- Version: 2.0.0 (Consolidated Full Schema)
-- Compatibility: MySQL 8.0+ / MariaDB 10.4+
-- Default Encoding: utf8mb4 / Collation: utf8mb4_unicode_ci
-- Generated for: Apex SMM Services Platform
-- ============================================================================
-- This script contains the latest consolidated database architecture required
-- by all implemented user and administrative systems, including multi-gateway
-- financial processing, order scheduling, automated refills, loyalty tiers,
-- affiliate referrals, security audit logging, and background cron management.
-- No fake/demo user records, orders, balances, or mock transactions are included.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Drop existing tables in reverse dependency order
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `database_backups`;
DROP TABLE IF EXISTS `security_events`;
DROP TABLE IF EXISTS `admin_audit_logs`;
DROP TABLE IF EXISTS `api_request_logs`;
DROP TABLE IF EXISTS `provider_import_logs`;
DROP TABLE IF EXISTS `cron_logs`;
DROP TABLE IF EXISTS `cron_tasks`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `faqs`;
DROP TABLE IF EXISTS `knowledge_base_articles`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `email_logs`;
DROP TABLE IF EXISTS `email_templates`;
DROP TABLE IF EXISTS `support_attachments`;
DROP TABLE IF EXISTS `support_messages`;
DROP TABLE IF EXISTS `support_tickets`;
DROP TABLE IF EXISTS `loyalty_transactions`;
DROP TABLE IF EXISTS `loyalty_accounts`;
DROP TABLE IF EXISTS `referral_payouts`;
DROP TABLE IF EXISTS `referrals`;
DROP TABLE IF EXISTS `payment_webhook_logs`;
DROP TABLE IF EXISTS `wallet_transactions`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `payment_gateways`;
DROP TABLE IF EXISTS `cancellation_requests`;
DROP TABLE IF EXISTS `refill_requests`;
DROP TABLE IF EXISTS `order_schedule_runs`;
DROP TABLE IF EXISTS `order_schedules`;
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `exchange_rates`;
DROP TABLE IF EXISTS `currencies`;
DROP TABLE IF EXISTS `favorite_services`;
DROP TABLE IF EXISTS `price_history`;
DROP TABLE IF EXISTS `category_mappings`;
DROP TABLE IF EXISTS `provider_categories`;
DROP TABLE IF EXISTS `provider_services`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `providers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `user_verifications`;
DROP TABLE IF EXISTS `user_preferences`;
DROP TABLE IF EXISTS `user_logins`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;


-- ============================================================================
-- SECTION 1: ROLES, PERMISSIONS, ADMINS & USER IDENTITY
-- ============================================================================

-- 1. Administrative Roles
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(64) NOT NULL,
  `slug` VARCHAR(64) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. System Permissions
CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(64) NOT NULL,
  `slug` VARCHAR(64) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Role-Permission Cross Mappings
CREATE TABLE `role_permissions` (
  `role_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Administrators
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

-- 5. User Accounts (With Extended Contact, Verification & Affiliate Fields)
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `phone` VARCHAR(32) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `balance` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `status` ENUM('active', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME NULL,
  `phone_verified_at` DATETIME NULL,
  `api_key` VARCHAR(64) NULL UNIQUE,
  `referral_code` VARCHAR(32) NULL UNIQUE,
  `referred_by` BIGINT UNSIGNED NULL,
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'UTC',
  `reset_token` VARCHAR(128) NULL,
  `reset_token_expires_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_status` (`status`),
  INDEX `idx_users_api_key` (`api_key`),
  INDEX `idx_users_referred_by` (`referred_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. User Login Security & Session Audit History
CREATE TABLE `user_logins` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `session_id` VARCHAR(128) NULL,
  `status` ENUM('success', 'failed') NOT NULL DEFAULT 'success',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ul_user` (`user_id`, `created_at`),
  INDEX `idx_ul_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. User Notification & Interface Preferences
CREATE TABLE `user_preferences` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `notify_order_status` TINYINT(1) NOT NULL DEFAULT 1,
  `notify_wallet_deposit` TINYINT(1) NOT NULL DEFAULT 1,
  `notify_ticket_reply` TINYINT(1) NOT NULL DEFAULT 1,
  `notify_news` TINYINT(1) NOT NULL DEFAULT 1,
  `language` VARCHAR(10) NOT NULL DEFAULT 'en',
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'UTC',
  `display_density` VARCHAR(20) NOT NULL DEFAULT 'normal',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. User Email & Phone Verification Tokens
CREATE TABLE `user_verifications` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('email', 'phone') NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `target_value` VARCHAR(191) NOT NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `verified_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_uv_user` (`user_id`),
  INDEX `idx_uv_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SECTION 2: SERVICE CATALOG, PROVIDERS & PRICING
-- ============================================================================

-- 9. Service Categories
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

-- 10. Wholesale SMM Providers
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

-- 11. Customer Services
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

-- 12. Remote Provider Synchronized Services
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

-- 13. Remote Provider Categories
CREATE TABLE `provider_categories` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `remote_id` VARCHAR(64) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pc_provider` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Provider to Local Category Mappings
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

-- 15. Service Price History & Margin Tracking
CREATE TABLE `price_history` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `old_rate` DECIMAL(18,8) NOT NULL,
  `new_rate` DECIMAL(18,8) NOT NULL,
  `old_margin` DECIMAL(18,8) NOT NULL,
  `new_margin` DECIMAL(18,8) NOT NULL,
  `changed_by` VARCHAR(64) NOT NULL DEFAULT 'system',
  `reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ph_service` (`service_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. User Favorite Services
CREATE TABLE `favorite_services` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_fav_user_service` (`user_id`, `service_id`),
  INDEX `idx_fav_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SECTION 3: CURRENCIES & EXCHANGE RATES
-- ============================================================================

-- 17. Multi-Currency Definitions
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

-- 18. Daily / Real-Time Foreign Exchange Rates
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


-- ============================================================================
-- SECTION 4: ORDERS, SCHEDULES & AUTO-FULFILLMENT
-- ============================================================================

-- 19. Customer Orders
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

-- 20. Order Status Audit History
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

-- 21. Scheduled & Recurring Order Profiles
CREATE TABLE `order_schedules` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `link` VARCHAR(2048) NOT NULL,
  `quantity` INT NOT NULL,
  `schedule_type` ENUM('scheduled', 'recurring') NOT NULL DEFAULT 'recurring',
  `runs_total` INT NOT NULL DEFAULT 1,
  `runs_completed` INT NOT NULL DEFAULT 0,
  `interval_hours` INT NOT NULL DEFAULT 24,
  `next_run_at` DATETIME NOT NULL,
  `status` ENUM('active', 'paused', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
  `last_run_at` DATETIME NULL,
  `last_error` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_os_user` (`user_id`),
  INDEX `idx_os_status_next` (`status`, `next_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Scheduled Order Execution Runs
CREATE TABLE `order_schedule_runs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `schedule_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NULL,
  `status` ENUM('success', 'failed') NOT NULL DEFAULT 'success',
  `charge` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `message` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_osr_schedule` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Automated & Manual Refill Requests
CREATE TABLE `refill_requests` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `provider_id` BIGINT UNSIGNED NULL,
  `provider_refill_id` VARCHAR(128) NULL,
  `status` ENUM('pending', 'processing', 'completed', 'rejected', 'failed') NOT NULL DEFAULT 'pending',
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_rr_order` (`order_id`),
  INDEX `idx_rr_user` (`user_id`),
  INDEX `idx_rr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Order Cancellation Requests
CREATE TABLE `cancellation_requests` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `provider_id` BIGINT UNSIGNED NULL,
  `reason` VARCHAR(255) NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'failed') NOT NULL DEFAULT 'pending',
  `admin_note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cr_order` (`order_id`),
  INDEX `idx_cr_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SECTION 5: FINANCIAL OPERATIONS, PAYMENTS & GATEWAYS
-- ============================================================================

-- 25. Payment Gateways Registry & Configuration (46 Gateway Engine Architecture)
CREATE TABLE `payment_gateways` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(64) NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `type` ENUM('builtin', 'custom') NOT NULL DEFAULT 'builtin',
  `description` TEXT NULL,
  `instructions` TEXT NULL,
  `user_message` VARCHAR(255) NULL,
  `logo` VARCHAR(255) NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `min_amount` DECIMAL(18,8) NOT NULL DEFAULT 10.00000000,
  `max_amount` DECIMAL(18,8) NOT NULL DEFAULT 500000.00000000,
  `fixed_fee` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `percent_fee` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `bonus_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `bonus_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
  `bonus_value` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `max_bonus` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `bonus_rules` TEXT NULL,
  `credentials` LONGTEXT NULL,
  `config` LONGTEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `code` (`code`),
  INDEX `idx_enabled_sort` (`is_enabled`, `sort_order`),
  INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Customer Payments (With Fee, Bonus & Net Wallet Credit Tracking)
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
  `bonus` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `wallet_credit` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `status` ENUM('pending', 'processing', 'completed', 'success', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
  `payload` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_payments_order_id` (`order_id`),
  INDEX `idx_payments_user` (`user_id`, `created_at`),
  INDEX `idx_payments_status` (`status`),
  INDEX `idx_payments_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Wallet Ledger Transactions
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

-- 28. Inbound Payment Gateway Webhook Audit Logs
CREATE TABLE `payment_webhook_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `gateway` VARCHAR(64) NOT NULL,
  `event_type` VARCHAR(128) NOT NULL,
  `event_id` VARCHAR(128) NULL,
  `payload` JSON NULL,
  `signature` VARCHAR(255) NULL,
  `status` ENUM('verified', 'invalid', 'error') NOT NULL DEFAULT 'verified',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pwl_gateway` (`gateway`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SECTION 6: AFFILIATES, REFERRALS & LOYALTY PROGRAM
-- ============================================================================

-- 29. User Referral Network & Commissions
CREATE TABLE `referrals` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `referrer_id` BIGINT UNSIGNED NOT NULL,
  `referee_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `referral_code` VARCHAR(32) NOT NULL,
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `total_commission_earned` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ref_referrer` (`referrer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 30. Affiliate Referral Payouts
CREATE TABLE `referral_payouts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(18,8) NOT NULL,
  `method` VARCHAR(64) NOT NULL DEFAULT 'wallet_credit',
  `payout_details` VARCHAR(255) NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `admin_note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_rp_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 31. Loyalty Reward Accounts & Tiers
CREATE TABLE `loyalty_accounts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `points` INT UNSIGNED NOT NULL DEFAULT 0,
  `tier` ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL DEFAULT 'bronze',
  `lifetime_spent` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 32. Loyalty Point Transactions (Earned / Redeemed)
CREATE TABLE `loyalty_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `points` INT NOT NULL,
  `type` ENUM('earn', 'redeem', 'bonus', 'adjustment') NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `reference_id` VARCHAR(128) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_lt_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SECTION 7: SUPPORT TICKETING & COMMUNICATIONS
-- ============================================================================

-- 33. Support Tickets (With Assigned Admin Support)
CREATE TABLE `support_tickets` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `category` VARCHAR(64) NOT NULL DEFAULT 'order',
  `order_id` BIGINT UNSIGNED NULL,
  `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  `assigned_admin_id` BIGINT UNSIGNED NULL,
  `status` ENUM('open', 'pending', 'answered', 'closed') NOT NULL DEFAULT 'open',
  `last_reply_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_st_user` (`user_id`),
  INDEX `idx_st_status` (`status`),
  INDEX `idx_st_assigned` (`assigned_admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 34. Support Thread Messages
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

-- 35. Support File Attachments
CREATE TABLE `support_attachments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `message_id` BIGINT UNSIGNED NULL,
  `filename` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sa_ticket` (`ticket_id`),
  INDEX `idx_sa_message` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 36. Transactional & Marketing Email Templates
CREATE TABLE `email_templates` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `template_key` VARCHAR(64) NOT NULL UNIQUE,
  `name` VARCHAR(128) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `variables_hint` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 37. Outbound Email Delivery Logs
CREATE TABLE `email_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `recipient` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `template_key` VARCHAR(64) NULL,
  `status` ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_el_recipient` (`recipient`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 38. In-App User & Global Broadcast Notifications
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


-- ============================================================================
-- SECTION 8: KNOWLEDGE BASE & FREQUENTLY ASKED QUESTIONS
-- ============================================================================

-- 39. Knowledge Base & Guides
CREATE TABLE `knowledge_base_articles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(64) NOT NULL DEFAULT 'General',
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `content` TEXT NOT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_kb_cat_pub` (`category`, `is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 40. Frequently Asked Questions (FAQs)
CREATE TABLE `faqs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(64) NOT NULL DEFAULT 'General',
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_faq_pub_sort` (`is_published`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SECTION 9: SYSTEM CONFIGURATION, CRONS & AUDIT LOGS
-- ============================================================================

-- 41. Key-Value Global Platform Settings
CREATE TABLE `settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(128) NOT NULL UNIQUE,
  `value` TEXT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'string',
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 42. Automated Cron Tasks Registry & Scheduler
CREATE TABLE `cron_tasks` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL,
  `task_key` VARCHAR(64) NOT NULL UNIQUE,
  `cron_expression` VARCHAR(64) NOT NULL DEFAULT '* * * * *',
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `is_running` TINYINT(1) NOT NULL DEFAULT 0,
  `last_run_at` DATETIME NULL,
  `last_duration` DECIMAL(8,3) NULL,
  `last_status` VARCHAR(32) NULL,
  `last_error` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 43. Cron Execution Telemetry Logs
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

-- 44. Provider Synchronization & Import Logs
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

-- 45. Public & Reseller API Request Logs
CREATE TABLE `api_request_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL,
  `api_key_preview` VARCHAR(16) NULL,
  `endpoint` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL DEFAULT 'POST',
  `ip_address` VARCHAR(45) NOT NULL,
  `status_code` INT NOT NULL DEFAULT 200,
  `response_time_ms` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_arl_user` (`user_id`, `created_at`),
  INDEX `idx_arl_status` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 46. Administrative Audit Trail
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

-- 47. Security Events & Fraud Abuse Monitoring
CREATE TABLE `security_events` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(64) NOT NULL,
  `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
  `user_id` BIGINT UNSIGNED NULL,
  `admin_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `details` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_se_type` (`event_type`),
  INDEX `idx_se_severity` (`severity`),
  INDEX `idx_se_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 48. Database Backup Records & Metadata
CREATE TABLE `database_backups` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `tables_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `records_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('completed', 'failed') NOT NULL DEFAULT 'completed',
  `created_by` VARCHAR(64) NOT NULL DEFAULT 'system',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- BASELINE ESSENTIAL SYSTEM DATA (SYSTEM CONFIGURATION ONLY)
-- (Excludes fake/demo users, test balances, mock orders, and payment records)
-- ============================================================================

-- 1. Default Administrator Roles
INSERT INTO `roles` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Super Admin', 'super_admin', 'Full system access and settings control'),
(2, 'Support Agent', 'support_agent', 'Support tickets and order viewing')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 2. Supported Currencies
INSERT INTO `currencies` (`code`, `name`, `symbol`, `is_default`, `status`) VALUES
('USD', 'US Dollar', '$', 0, 'active'),
('INR', 'Indian Rupee', '₹', 1, 'active'),
('EUR', 'Euro', '€', 0, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 3. Base Exchange Rates
INSERT INTO `exchange_rates` (`base_currency`, `quote_currency`, `rate`, `source`, `effective_date`, `status`) VALUES
('USD', 'INR', 83.50000000, 'system_init', CURRENT_DATE(), 'active'),
('INR', 'USD', 0.01197605, 'system_init', CURRENT_DATE(), 'active'),
('USD', 'USD', 1.00000000, 'system_init', CURRENT_DATE(), 'active'),
('INR', 'INR', 1.00000000, 'system_init', CURRENT_DATE(), 'active')
ON DUPLICATE KEY UPDATE `rate` = VALUES(`rate`);

-- 4. Baseline System Settings (No secrets/credentials hardcoded)
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
('rate_limit_per_minute', '60', 'integer', 'Default API/web request limit per minute')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- 5. Standard Automated Background Cron Tasks
INSERT INTO `cron_tasks` (`name`, `task_key`, `cron_expression`, `is_enabled`) VALUES
('Order Status Synchronization', 'orders_sync', '*/2 * * * *', 1),
('Provider Services Synchronization', 'provider_services_sync', '0 */6 * * *', 1),
('Provider Rates Synchronization', 'provider_prices_sync', '0 0 * * *', 1),
('Automated Refill Runner', 'auto_refill', '*/5 * * * *', 1),
('Retry Failed Submissions', 'retry_failed_orders', '*/10 * * * *', 1),
('Payment Reconciliation', 'payment_reconciliation', '*/15 * * * *', 1),
('Scheduled Orders Processor', 'scheduled_orders', '* * * * *', 1),
('System Log & Cache Cleanup', 'cleanup', '0 3 * * *', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 6. System Transactional Email Templates
INSERT INTO `email_templates` (`template_key`, `name`, `subject`, `body`, `variables_hint`) VALUES
('welcome', 'Welcome Registration', 'Welcome to {{site_name}}!', 'Hello {{username}},\n\nThank you for registering on {{site_name}}. Your account is ready for high-speed social media growth.\n\nBest regards,\nThe {{site_name}} Team', '{{username}}, {{site_name}}, {{login_url}}'),
('order_completed', 'Order Completed', 'Order #{{order_id}} Completed Successfully', 'Hello {{username}},\n\nYour order #{{order_id}} for {{service_name}} has completed successfully.\n\nQuantity: {{quantity}}\n\nThank you for choosing us!', '{{username}}, {{order_id}}, {{service_name}}, {{quantity}}'),
('wallet_deposit', 'Deposit Confirmed', 'Payment of {{amount}} Credited to Wallet', 'Hello {{username}},\n\nYour deposit of {{amount}} has been verified and added to your wallet balance.\n\nTransaction ID: {{payment_id}}', '{{username}}, {{amount}}, {{payment_id}}'),
('ticket_reply', 'New Ticket Reply', 'Reply on Support Ticket #{{ticket_id}}', 'Hello {{username}},\n\nThere is a new reply on your ticket #{{ticket_id}}: \"{{subject}}\".\n\nPlease log in to your dashboard to review.', '{{username}}, {{ticket_id}}, {{subject}}')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 7. System Frequently Asked Questions
INSERT INTO `faqs` (`category`, `question`, `answer`, `is_published`, `sort_order`) VALUES
('Ordering', 'How long does it take for my order to start?', 'Most automated orders initiate within 1-15 minutes of submission. Delivery speed depends on current wholesale network queue.', 1, 1),
('Ordering', 'Can I cancel an in-progress order?', 'Once an order is submitted to wholesale providers, cancellation is only available if the provider supports the cancel action.', 1, 2),
('Wallet', 'What payment methods do you support?', 'We support instant wallet top-ups through secure digital payment gateways (UPI, Net Banking, Cards, QR) with immediate automated balance crediting.', 1, 3),
('Refill', 'What does the Refill button mean?', 'Refill guarantees restoration of dropped counts within the provider coverage window (e.g. 30-day refill guarantee).', 1, 4),
('API', 'How do I connect my reseller website via API?', 'Go to Account > API to view your API credentials. Use the standard SMM API v2 endpoints documented on our API documentation page.', 1, 5)
ON DUPLICATE KEY UPDATE `question` = VALUES(`question`);

-- 8. System Knowledge Base Articles
INSERT INTO `knowledge_base_articles` (`category`, `title`, `slug`, `content`, `is_published`, `sort_order`) VALUES
('Getting Started', 'Quickstart Guide to Placing Your First SMM Order', 'quickstart-first-order', 'To place your first order:\n1. Top up your wallet using instant payment.\n2. Navigate to New Order.\n3. Choose your category and service.\n4. Input the target link and desired quantity.\n5. Click Submit to initiate automated delivery.', 1, 1),
('API Integration', 'Connecting via Standard SMM API v2', 'smm-api-v2-integration', 'Our platform implements the standard SMM API v2 specification. Request methods accept standard POST parameters with key, action (services, add, status, refill), and return JSON responses.', 1, 2),
('Troubleshooting', 'Why is my order marked as Partial or Cancelled?', 'why-order-partial-or-cancelled', 'When an order is marked Partial, the server delivered a portion of the quantity and automatically refunded the remainder balance to your wallet.', 1, 3)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 9. Built-in & Custom Payment Gateway Definitions Registry (46 Gateways)
-- Note: Credentials are kept empty/unconfigured; passwords/API secrets are NOT hardcoded.
INSERT INTO `payment_gateways` 
(`code`, `name`, `type`, `description`, `instructions`, `is_enabled`, `sort_order`, `currency`, `min_amount`, `max_amount`, `fixed_fee`, `percent_fee`, `bonus_enabled`, `bonus_type`, `bonus_value`, `max_bonus`, `credentials`, `config`) 
VALUES
('razorpay', 'Razorpay', 'builtin', 'Instant deposit via Razorpay', '', 1, 10, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('cashfree', 'Cashfree Payments', 'builtin', 'Instant deposit via Cashfree Payments', '', 0, 11, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('phonepe', 'PhonePe PG', 'builtin', 'Instant deposit via PhonePe PG', '', 0, 12, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('payu', 'PayU India', 'builtin', 'Instant deposit via PayU India', '', 0, 13, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('paytm', 'Paytm PG', 'builtin', 'Instant deposit via Paytm PG', '', 0, 14, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('ccavenue', 'CCAvenue', 'builtin', 'Instant deposit via CCAvenue', '', 0, 15, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('billdesk', 'BillDesk', 'builtin', 'Instant deposit via BillDesk', '', 0, 16, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('easebuzz', 'Easebuzz', 'builtin', 'Instant deposit via Easebuzz', '', 0, 17, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('plural', 'Pine Labs Plural', 'builtin', 'Instant deposit via Pine Labs Plural', '', 0, 18, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('worldline', 'Worldline India', 'builtin', 'Instant deposit via Worldline India', '', 0, 19, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('zaakpay', 'Zaakpay (MobiKwik)', 'builtin', 'Instant deposit via Zaakpay (MobiKwik)', '', 0, 20, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('instamojo', 'Instamojo', 'builtin', 'Instant deposit via Instamojo', '', 0, 21, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('mobikwik', 'MobiKwik', 'builtin', 'Instant deposit via MobiKwik', '', 0, 22, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('juspay', 'Juspay / HyperCheckout', 'builtin', 'Instant deposit via Juspay / HyperCheckout', '', 0, 23, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('payglocal', 'PayGlocal', 'builtin', 'Instant deposit via PayGlocal', '', 0, 24, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('stripe', 'Stripe', 'builtin', 'Instant deposit via Stripe', '', 0, 25, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('paypal', 'PayPal', 'builtin', 'Instant deposit via PayPal', '', 0, 26, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('braintree', 'Braintree (PayPal Service)', 'builtin', 'Instant deposit via Braintree (PayPal Service)', '', 0, 27, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('adyen', 'Adyen', 'builtin', 'Instant deposit via Adyen', '', 0, 28, 'EUR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"EUR\",\"instructions\":\"\"}'),
('checkoutcom', 'Checkout.com', 'builtin', 'Instant deposit via Checkout.com', '', 0, 29, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('worldpay', 'Worldpay (FIS)', 'builtin', 'Instant deposit via Worldpay (FIS)', '', 0, 30, 'GBP', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"GBP\",\"instructions\":\"\"}'),
('twocheckout', '2Checkout / Verifone', 'builtin', 'Instant deposit via 2Checkout / Verifone', '', 0, 31, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('airwallex', 'Airwallex', 'builtin', 'Instant deposit via Airwallex', '', 0, 32, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('nuvei', 'Nuvei / SafeCharge', 'builtin', 'Instant deposit via Nuvei / SafeCharge', '', 0, 33, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('rapyd', 'Rapyd', 'builtin', 'Instant deposit via Rapyd', '', 0, 34, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('mollie', 'Mollie', 'builtin', 'Instant deposit via Mollie', '', 0, 35, 'EUR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"EUR\",\"instructions\":\"\"}'),
('dlocal', 'dLocal', 'builtin', 'Instant deposit via dLocal', '', 0, 36, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('payu_global', 'PayU Global (Europe / LATAM)', 'builtin', 'Instant deposit via PayU Global (Europe / LATAM)', '', 0, 37, 'EUR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"EUR\",\"instructions\":\"\"}'),
('payoneer', 'Payoneer', 'builtin', 'Instant deposit via Payoneer', '', 0, 38, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('globalpayments', 'Global Payments', 'builtin', 'Instant deposit via Global Payments', '', 0, 39, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('cybersource', 'CyberSource (Visa)', 'builtin', 'Instant deposit via CyberSource (Visa)', '', 0, 40, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('authorizenet', 'Authorize.Net', 'builtin', 'Instant deposit via Authorize.Net', '', 0, 41, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('amazonpay', 'Amazon Pay', 'builtin', 'Instant deposit via Amazon Pay', '', 0, 42, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('klarna', 'Klarna', 'builtin', 'Instant deposit via Klarna', '', 0, 43, 'EUR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"EUR\",\"instructions\":\"\"}'),
('mercadopago', 'Mercado Pago', 'builtin', 'Instant deposit via Mercado Pago', '', 0, 44, 'BRL', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"BRL\",\"instructions\":\"\"}'),
('paystack', 'Paystack', 'builtin', 'Instant deposit via Paystack', '', 0, 45, 'NGN', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"NGN\",\"instructions\":\"\"}'),
('flutterwave', 'Flutterwave', 'builtin', 'Instant deposit via Flutterwave', '', 0, 46, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('alipay', 'Alipay (Global)', 'builtin', 'Instant deposit via Alipay (Global)', '', 0, 47, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('wechatpay', 'WeChat Pay', 'builtin', 'Instant deposit via WeChat Pay', '', 0, 48, 'CNY', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"CNY\",\"instructions\":\"\"}'),
('binancepay', 'Binance Pay', 'builtin', 'Instant deposit via Binance Pay', '', 0, 49, 'USDT', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USDT\",\"instructions\":\"\"}'),
('coinbase_commerce', 'Coinbase Commerce', 'builtin', 'Instant deposit via Coinbase Commerce', '', 0, 50, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('cryptocom_pay', 'Crypto.com Pay', 'builtin', 'Instant deposit via Crypto.com Pay', '', 0, 51, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('coinpayments', 'CoinPayments', 'builtin', 'Instant deposit via CoinPayments', '', 0, 52, 'USD', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"USD\",\"instructions\":\"\"}'),
('bank_transfer', 'Direct Bank Transfer / Wire', 'custom', 'Manual offline deposit via direct bank transfer / wire', '', 0, 53, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('custom_qr', 'Custom QR / Static UPI', 'custom', 'Manual offline deposit via static UPI QR code', '', 0, 54, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}'),
('custom_manual', 'Custom Manual Payment', 'custom', 'Manual offline payment gateway with custom admin instructions', '', 0, 55, 'INR', 10.00, 50000.00, 0.00, 0.00, 0, 'percentage', 0.00, 0.00, NULL, '{\"default_currency\":\"INR\",\"instructions\":\"\"}')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================================
-- END OF LATEST FULL SCHEMA
-- ============================================================================
