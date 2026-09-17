-- Migration: 018_create_settings_table.sql
CREATE TABLE IF NOT EXISTS `settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(128) NOT NULL UNIQUE,
  `value` TEXT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'string',
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Base settings structure (no fake stats, real operational defaults)
INSERT IGNORE INTO `settings` (`key`, `value`, `type`, `description`) VALUES
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
