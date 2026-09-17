-- Part 2 Schema Extensions: Advanced Admin & User Features
-- Fully compatible with existing SMM Panel schema

-- 1. Support Tickets Extensions
ALTER TABLE `support_tickets` 
  ADD COLUMN IF NOT EXISTS `assigned_admin_id` BIGINT UNSIGNED NULL AFTER `priority`,
  ADD INDEX IF NOT EXISTS `idx_st_assigned` (`assigned_admin_id`);

-- 2. Users Extensions for Referrals & Loyalty
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `referral_code` VARCHAR(32) NULL UNIQUE AFTER `api_key`,
  ADD COLUMN IF NOT EXISTS `referred_by` BIGINT UNSIGNED NULL AFTER `referral_code`,
  ADD COLUMN IF NOT EXISTS `phone` VARCHAR(32) NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `phone_verified_at` DATETIME NULL AFTER `email_verified_at`,
  ADD INDEX IF NOT EXISTS `idx_users_referred_by` (`referred_by`);

-- 3. Support Attachments
CREATE TABLE IF NOT EXISTS `support_attachments` (
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

-- 4. User Login & Security Activity
CREATE TABLE IF NOT EXISTS `user_logins` (
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

-- 5. Favorite Services
CREATE TABLE IF NOT EXISTS `favorite_services` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_fav_user_service` (`user_id`, `service_id`),
  INDEX `idx_fav_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Refill Requests
CREATE TABLE IF NOT EXISTS `refill_requests` (
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

-- 7. Cancellation Requests
CREATE TABLE IF NOT EXISTS `cancellation_requests` (
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

-- 8. Order Schedules & Recurring Orders
CREATE TABLE IF NOT EXISTS `order_schedules` (
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

-- 9. Order Schedule Execution Runs
CREATE TABLE IF NOT EXISTS `order_schedule_runs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `schedule_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NULL,
  `status` ENUM('success', 'failed') NOT NULL DEFAULT 'success',
  `charge` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `message` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_osr_schedule` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Loyalty Accounts & Tiers
CREATE TABLE IF NOT EXISTS `loyalty_accounts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `points` INT UNSIGNED NOT NULL DEFAULT 0,
  `tier` ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL DEFAULT 'bronze',
  `lifetime_spent` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Loyalty Transactions
CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `points` INT NOT NULL,
  `type` ENUM('earn', 'redeem', 'bonus', 'adjustment') NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `reference_id` VARCHAR(128) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_lt_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. User Preferences
CREATE TABLE IF NOT EXISTS `user_preferences` (
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

-- 13. User Verifications (Email / Phone)
CREATE TABLE IF NOT EXISTS `user_verifications` (
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

-- 14. Referrals & Commissions
CREATE TABLE IF NOT EXISTS `referrals` (
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

-- 15. Referral Payouts
CREATE TABLE IF NOT EXISTS `referral_payouts` (
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

-- 16. API Request Logs
CREATE TABLE IF NOT EXISTS `api_request_logs` (
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

-- 17. Knowledge Base Articles
CREATE TABLE IF NOT EXISTS `knowledge_base_articles` (
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

-- 18. FAQs
CREATE TABLE IF NOT EXISTS `faqs` (
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

-- 19. Price History
CREATE TABLE IF NOT EXISTS `price_history` (
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

-- 20. Payment Webhook Logs
CREATE TABLE IF NOT EXISTS `payment_webhook_logs` (
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

-- 21. Cron Tasks Management
CREATE TABLE IF NOT EXISTS `cron_tasks` (
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

-- 22. Email Templates
CREATE TABLE IF NOT EXISTS `email_templates` (
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

-- 23. Email Delivery Logs
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `recipient` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `template_key` VARCHAR(64) NULL,
  `status` ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_el_recipient` (`recipient`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Security & Fraud Events
CREATE TABLE IF NOT EXISTS `security_events` (
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

-- 25. Database Backups
CREATE TABLE IF NOT EXISTS `database_backups` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `tables_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `records_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('completed', 'failed') NOT NULL DEFAULT 'completed',
  `created_by` VARCHAR(64) NOT NULL DEFAULT 'system',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Initial Seed for Cron Tasks
INSERT IGNORE INTO `cron_tasks` (`name`, `task_key`, `cron_expression`, `is_enabled`) VALUES
('Order Status Synchronization', 'orders_sync', '*/2 * * * *', 1),
('Provider Services Synchronization', 'provider_services_sync', '0 */6 * * *', 1),
('Provider Rates Synchronization', 'provider_prices_sync', '0 0 * * *', 1),
('Automated Refill Runner', 'auto_refill', '*/5 * * * *', 1),
('Retry Failed Submissions', 'retry_failed_orders', '*/10 * * * *', 1),
('Payment Reconciliation', 'payment_reconciliation', '*/15 * * * *', 1),
('Scheduled Orders Processor', 'scheduled_orders', '* * * * *', 1),
('System Log & Cache Cleanup', 'cleanup', '0 3 * * *', 1);

-- 27. Initial Seed for Email Templates
INSERT IGNORE INTO `email_templates` (`template_key`, `name`, `subject`, `body`, `variables_hint`) VALUES
('welcome', 'Welcome Registration', 'Welcome to {{site_name}}!', 'Hello {{username}},\n\nThank you for registering on {{site_name}}. Your account is ready for high-speed social media growth.\n\nBest regards,\nThe {{site_name}} Team', '{{username}}, {{site_name}}, {{login_url}}'),
('order_completed', 'Order Completed', 'Order #{{order_id}} Completed Successfully', 'Hello {{username}},\n\nYour order #{{order_id}} for {{service_name}} has completed successfully.\n\nQuantity: {{quantity}}\n\nThank you for choosing us!', '{{username}}, {{order_id}}, {{service_name}}, {{quantity}}'),
('wallet_deposit', 'Deposit Confirmed', 'Payment of {{amount}} Credited to Wallet', 'Hello {{username}},\n\nYour deposit of {{amount}} has been verified and added to your wallet balance.\n\nTransaction ID: {{payment_id}}', '{{username}}, {{amount}}, {{payment_id}}'),
('ticket_reply', 'New Ticket Reply', 'Reply on Support Ticket #{{ticket_id}}', 'Hello {{username}},\n\nThere is a new reply on your ticket #{{ticket_id}}: \"{{subject}}\".\n\nPlease log in to your dashboard to review.', '{{username}}, {{ticket_id}}, {{subject}}');

-- 28. Initial Seed for Knowledge Base & FAQs
INSERT IGNORE INTO `faqs` (`category`, `question`, `answer`, `is_published`, `sort_order`) VALUES
('Ordering', 'How long does it take for my order to start?', 'Most automated orders initiate within 1-15 minutes of submission. Delivery speed depends on current wholesale network queue.', 1, 1),
('Ordering', 'Can I cancel an in-progress order?', 'Once an order is submitted to wholesale providers, cancellation is only available if the provider supports the cancel action.', 1, 2),
('Wallet', 'What payment methods do you support?', 'We currently support instant checkout via Razorpay (UPI, Net Banking, Credit/Debit cards, QR payments) with 0-minute automated crediting.', 1, 3),
('Refill', 'What does the Refill button mean?', 'Refill guarantees restoration of dropped counts within the provider coverage window (e.g. 30-day refill guarantee).', 1, 4),
('API', 'How do I connect my reseller website via API?', 'Go to Account > API Management to generate an API key. Use the standard SMM API v2 endpoints documented on our /api-docs page.', 1, 5);

INSERT IGNORE INTO `knowledge_base_articles` (`category`, `title`, `slug`, `content`, `is_published`, `sort_order`) VALUES
('Getting Started', 'Quickstart Guide to Placing Your First SMM Order', 'quickstart-first-order', 'To place your first order:\n1. Top up your wallet using instant payment.\n2. Navigate to New Order.\n3. Choose your category and service.\n4. Input the link and quantity.\n5. Click Submit to initiate automated delivery.', 1, 1),
('API Integration', 'Connecting via Standard SMM API v2', 'smm-api-v2-integration', 'Our platform implements the standard SMM API v2 specification. Request methods accept standard POST parameters with key, action (services, add, status, refill), and return JSON responses.', 1, 2),
('Troubleshooting', 'Why is my order marked as Partial or Cancelled?', 'why-order-partial-or-cancelled', 'When an order is marked Partial, the server delivered a portion of the quantity and automatically refunded the remainder balance to your wallet.', 1, 3);
