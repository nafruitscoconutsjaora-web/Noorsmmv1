<?php

declare(strict_types=1);

namespace Database\Seeds;

use PDO;

class DatabaseSeeder
{
    public static function run(PDO $db): void
    {
        // 1. Seed Admin
        $stmt = $db->query("SELECT COUNT(*) FROM admins");
        if ((int)$stmt->fetchColumn() === 0) {
            $passHash = password_hash('admin123', PASSWORD_BCRYPT);
            $ins = $db->prepare("INSERT INTO admins (username, email, password_hash, role_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $ins->execute(['admin', 'admin@example.com', $passHash, 1, 'active']);
            echo "Seeded Admin: admin / admin123\n";
        }

        // 2. Seed Demo User
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        if ((int)$stmt->fetchColumn() === 0) {
            $userPass = password_hash('demo123', PASSWORD_BCRYPT);
            $ins = $db->prepare("INSERT INTO users (username, email, password_hash, balance, status, api_key, timezone, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $ins->execute(['demo', 'demo@example.com', $userPass, 1500.00, 'active', 'smm_live_demo_984712034871', 'Asia/Kolkata']);
            $userId = (int)$db->lastInsertId();
            echo "Seeded User: demo / demo123 (Balance: ₹1500.00)\n";

            // Initial deposit transaction
            $tx = $db->prepare("INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $tx->execute([$userId, 'credit', 1500.00, 0.00, 1500.00, 'Welcome demo balance deposit', 'completed']);

            // Sample completed payment
            $pm = $db->prepare("INSERT INTO payments (user_id, gateway, order_id, payment_id, amount, currency, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $pm->execute([$userId, 'razorpay', 'order_rzp_demo_001', 'pay_rzp_demo_9921', 1500.00, 'INR', 'completed']);
        } else {
            $userId = (int)$db->query("SELECT id FROM users LIMIT 1")->fetchColumn();
        }

        // 3. Seed Provider
        $stmt = $db->query("SELECT COUNT(*) FROM providers");
        if ((int)$stmt->fetchColumn() === 0) {
            $ins = $db->prepare("INSERT INTO providers (name, api_url, api_key, balance, currency, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $ins->execute(['Wholesale SMM Hub', 'https://wholesalesmmhub.com/api/v2', 'api_live_wholesale_key_sec9918', 428.50, 'USD', 'active']);
            $providerId = (int)$db->lastInsertId();
            echo "Seeded Provider: Wholesale SMM Hub (Balance: $428.50)\n";
        } else {
            $providerId = (int)$db->query("SELECT id FROM providers LIMIT 1")->fetchColumn();
        }

        // 4. Seed Categories & Services
        $stmt = $db->query("SELECT COUNT(*) FROM categories");
        if ((int)$stmt->fetchColumn() === 0) {
            $categories = [
                ['name' => 'Instagram Followers & Likes', 'slug' => 'instagram-followers-likes', 'icon' => 'camera', 'sort_order' => 1, 'status' => 'active'],
                ['name' => 'YouTube Views & Watch Time', 'slug' => 'youtube-views-watchtime', 'icon' => 'play', 'sort_order' => 2, 'status' => 'active'],
                ['name' => 'TikTok Engagement & Views', 'slug' => 'tiktok-views-engagement', 'icon' => 'music', 'sort_order' => 3, 'status' => 'active'],
                ['name' => 'Telegram Channel Members & Reactions', 'slug' => 'telegram-channel-members', 'icon' => 'send', 'sort_order' => 4, 'status' => 'active'],
                ['name' => 'X / Twitter Followers & Retweets', 'slug' => 'x-twitter-followers', 'icon' => 'hash', 'sort_order' => 5, 'status' => 'active'],
                ['name' => 'Spotify Music Streams & Saves', 'slug' => 'spotify-music-streams', 'icon' => 'disc', 'sort_order' => 6, 'status' => 'active'],
            ];

            $catMap = [];
            $insCat = $db->prepare("INSERT INTO categories (name, slug, icon, sort_order, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            foreach ($categories as $cat) {
                $insCat->execute([$cat['name'], $cat['slug'], $cat['icon'], $cat['sort_order'], $cat['status']]);
                $catMap[$cat['name']] = (int)$db->lastInsertId();
            }
            echo "Seeded " . count($categories) . " Categories\n";

            // Services
            $services = [
                [
                    'category_id' => $catMap['Instagram Followers & Likes'],
                    'name' => 'Instagram High Quality Followers [Non-Drop] [30 Days Refill] [Speed: 10K/Day]',
                    'service_type' => 'default',
                    'rate' => 79.50,
                    'provider_cost' => 0.60,
                    'provider_currency' => 'USD',
                    'margin_type' => 'percentage',
                    'margin_value' => 50.0,
                    'min_quantity' => 50,
                    'max_quantity' => 100000,
                    'drip_feed' => 0,
                    'refill' => 1,
                    'cancel' => 0,
                    'description' => "Real-looking profiles with posts and profile pictures.\nStart time: Instant (0 - 15 minutes).\nRefill button enabled for 30 days.",
                    'status' => 'active',
                    'sort_order' => 1,
                    'provider_id' => $providerId,
                    'provider_service_id' => '101',
                ],
                [
                    'category_id' => $catMap['Instagram Followers & Likes'],
                    'name' => 'Instagram Instant Likes [HQ Real Accounts] [Speed: 20K/Day]',
                    'service_type' => 'default',
                    'rate' => 18.00,
                    'provider_cost' => 0.12,
                    'provider_currency' => 'USD',
                    'margin_type' => 'percentage',
                    'margin_value' => 60.0,
                    'min_quantity' => 20,
                    'max_quantity' => 50000,
                    'drip_feed' => 0,
                    'refill' => 0,
                    'cancel' => 0,
                    'description' => "Instant delivery, high retention likes. Supports multiple photos / carousels.",
                    'status' => 'active',
                    'sort_order' => 2,
                    'provider_id' => $providerId,
                    'provider_service_id' => '102',
                ],
                [
                    'category_id' => $catMap['YouTube Views & Watch Time'],
                    'name' => 'YouTube High Retention Views [Real Engagement] [Monetizable]',
                    'service_type' => 'default',
                    'rate' => 145.00,
                    'provider_cost' => 1.10,
                    'provider_currency' => 'USD',
                    'margin_type' => 'percentage',
                    'margin_value' => 45.0,
                    'min_quantity' => 500,
                    'max_quantity' => 1000000,
                    'drip_feed' => 1,
                    'refill' => 1,
                    'cancel' => 0,
                    'description' => "Natural desktop & mobile viewer retention (1 - 5 mins watch duration). Safe for AdSense.",
                    'status' => 'active',
                    'sort_order' => 1,
                    'provider_id' => $providerId,
                    'provider_service_id' => '201',
                ],
                [
                    'category_id' => $catMap['YouTube Views & Watch Time'],
                    'name' => 'YouTube 4000 Hours Watchtime Package [15+ Mins Video]',
                    'service_type' => 'default',
                    'rate' => 450.00,
                    'provider_cost' => 3.80,
                    'provider_currency' => 'USD',
                    'margin_type' => 'percentage',
                    'margin_value' => 40.0,
                    'min_quantity' => 100,
                    'max_quantity' => 4000,
                    'drip_feed' => 0,
                    'refill' => 1,
                    'cancel' => 0,
                    'description' => "Designed for YouTube partner monetization requirements. Video must be 15+ minutes.",
                    'status' => 'active',
                    'sort_order' => 2,
                    'provider_id' => $providerId,
                    'provider_service_id' => '202',
                ],
                [
                    'category_id' => $catMap['TikTok Engagement & Views'],
                    'name' => 'TikTok Viral Views [Instant Start] [Speed: 1M/Day]',
                    'service_type' => 'default',
                    'rate' => 4.50,
                    'provider_cost' => 0.03,
                    'provider_currency' => 'USD',
                    'margin_type' => 'percentage',
                    'margin_value' => 70.0,
                    'min_quantity' => 100,
                    'max_quantity' => 10000000,
                    'drip_feed' => 0,
                    'refill' => 0,
                    'cancel' => 0,
                    'description' => "Ultra-fast delivery for TikTok algorithm push and FYP ranking.",
                    'status' => 'active',
                    'sort_order' => 1,
                    'provider_id' => $providerId,
                    'provider_service_id' => '301',
                ],
                [
                    'category_id' => $catMap['TikTok Engagement & Views'],
                    'name' => 'TikTok Custom Comments [Real Looking Users]',
                    'service_type' => 'custom_comments',
                    'rate' => 320.00,
                    'provider_cost' => 2.50,
                    'provider_currency' => 'USD',
                    'margin_type' => 'percentage',
                    'margin_value' => 45.0,
                    'min_quantity' => 10,
                    'max_quantity' => 5000,
                    'drip_feed' => 0,
                    'refill' => 0,
                    'cancel' => 0,
                    'description' => "Enter 1 comment per line. Relevant emojis and natural phrasing supported.",
                    'status' => 'active',
                    'sort_order' => 2,
                    'provider_id' => $providerId,
                    'provider_service_id' => '302',
                ],
                [
                    'category_id' => $catMap['Telegram Channel Members & Reactions'],
                    'name' => 'Telegram 0% Drop Channel Members [30 Days Guaranteed]',
                    'service_type' => 'default',
                    'rate' => 65.00,
                    'provider_cost' => 0.50,
                    'provider_currency' => 'USD',
                    'margin_type' => 'percentage',
                    'margin_value' => 45.0,
                    'min_quantity' => 100,
                    'max_quantity' => 50000,
                    'drip_feed' => 0,
                    'refill' => 1,
                    'cancel' => 0,
                    'description' => "Non-drop high quality members for public channels and crypto groups.",
                    'status' => 'active',
                    'sort_order' => 1,
                    'provider_id' => $providerId,
                    'provider_service_id' => '401',
                ],
            ];

            $insSvc = $db->prepare("INSERT INTO services (category_id, provider_id, provider_service_id, name, description, service_type, provider_cost, provider_currency, margin_type, margin_value, rate, min_quantity, max_quantity, drip_feed, refill, cancel, status, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            foreach ($services as $s) {
                $insSvc->execute([
                    $s['category_id'],
                    $s['provider_id'],
                    $s['provider_service_id'],
                    $s['name'],
                    $s['description'],
                    $s['service_type'],
                    $s['provider_cost'],
                    $s['provider_currency'],
                    $s['margin_type'],
                    $s['margin_value'],
                    $s['rate'],
                    $s['min_quantity'],
                    $s['max_quantity'],
                    $s['drip_feed'],
                    $s['refill'],
                    $s['cancel'],
                    $s['status'],
                    $s['sort_order'],
                ]);
            }
            echo "Seeded " . count($services) . " Services\n";
        }

        // 5. Seed Initial Sample Orders
        $stmt = $db->query("SELECT COUNT(*) FROM orders");
        if ((int)$stmt->fetchColumn() === 0 && !empty($userId)) {
            $svc1 = $db->query("SELECT id, rate FROM services LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($svc1) {
                $insOrd = $db->prepare("INSERT INTO orders (user_id, service_id, provider_id, provider_order_id, link, quantity, charge, start_counter, remains, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $insOrd->execute([
                    $userId,
                    $svc1['id'],
                    $providerId,
                    'remote_ord_882194',
                    'https://instagram.com/techcreator_official',
                    1000,
                    (float)$svc1['rate'],
                    12450,
                    0,
                    'completed',
                ]);

                $insOrd->execute([
                    $userId,
                    $svc1['id'],
                    $providerId,
                    'remote_ord_882195',
                    'https://instagram.com/fashion_trend_daily',
                    500,
                    (float)$svc1['rate'] * 0.5,
                    340,
                    120,
                    'in_progress',
                ]);
                echo "Seeded Sample Orders\n";
            }
        }

        // 6. Seed Sample Ticket
        $stmt = $db->query("SELECT COUNT(*) FROM support_tickets");
        if ((int)$stmt->fetchColumn() === 0 && !empty($userId)) {
            $insTick = $db->prepare("INSERT INTO support_tickets (user_id, subject, category, priority, status, last_reply_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())");
            $insTick->execute([$userId, 'Question regarding YouTube watchtime speed', 'order', 'medium', 'answered']);
            $ticketId = (int)$db->lastInsertId();

            $insMsg = $db->prepare("INSERT INTO support_messages (ticket_id, user_id, admin_id, message, is_admin, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $insMsg->execute([$ticketId, $userId, null, "Hello team,\nDoes the 4000 hours watchtime package deliver gradually over 72 hours, or faster?", 0]);
            $insMsg->execute([$ticketId, null, 1, "Hello!\nYes, watchtime is safely dripped over 48 to 72 hours to ensure natural algorithmic pickup and complete safety for your YouTube channel. Feel free to place the order whenever ready!", 1]);
            echo "Seeded Sample Ticket & Replies\n";
        }
    }
}
