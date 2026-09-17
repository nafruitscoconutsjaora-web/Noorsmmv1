<?php

declare(strict_types=1);

// Apex SMM Platform - Installation Wizard - Step 3: Database & Administrator

$baseDir = dirname(__DIR__);
$envFile = $baseDir . '/.env';
$schemaFile = $baseDir . '/database/schema.sql';

// Detect current app URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
$detectedAppUrl = $protocol . $host;

// Pre-fill defaults from existing .env if available
$defaults = [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'smm_panel',
    'db_user' => 'smm_user',
    'db_pass' => 'smm_secure_pass_2026',
    'admin_user' => 'admin',
    'admin_email' => 'admin@example.com',
    'app_name' => 'Apex SMM Services',
    'app_url' => $detectedAppUrl,
    'currency' => 'INR',
    'timezone' => 'Asia/Kolkata',
];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if ($k === 'DB_HOST') $defaults['db_host'] = $v;
        if ($k === 'DB_PORT') $defaults['db_port'] = (int)$v;
        if ($k === 'DB_DATABASE') $defaults['db_name'] = $v;
        if ($k === 'DB_USERNAME') $defaults['db_user'] = $v;
        if ($k === 'DB_PASSWORD') $defaults['db_pass'] = $v;
        if ($k === 'APP_NAME') $defaults['app_name'] = $v;
        if ($k === 'APP_URL') $defaults['app_url'] = $v;
        if ($k === 'DEFAULT_CURRENCY') $defaults['currency'] = $v;
        if ($k === 'PANEL_TIMEZONE') $defaults['timezone'] = $v;
    }
}

$error = null;
$formData = $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['db_host'] = trim($_POST['db_host'] ?? '127.0.0.1');
    $formData['db_port'] = (int)($_POST['db_port'] ?? 3306);
    $formData['db_name'] = trim($_POST['db_name'] ?? 'smm_panel');
    $formData['db_user'] = trim($_POST['db_user'] ?? 'smm_user');
    $formData['db_pass'] = (string)($_POST['db_pass'] ?? '');

    $formData['admin_user'] = trim($_POST['admin_user'] ?? 'admin');
    $formData['admin_email'] = trim($_POST['admin_email'] ?? 'admin@example.com');
    $adminPass = (string)($_POST['admin_pass'] ?? '');
    $adminPassConfirm = (string)($_POST['admin_pass_confirm'] ?? '');

    $formData['app_name'] = trim($_POST['app_name'] ?? 'Apex SMM Services');
    $formData['app_url'] = rtrim(trim($_POST['app_url'] ?? $detectedAppUrl), '/');
    $formData['currency'] = strtoupper(trim($_POST['currency'] ?? 'INR'));
    $formData['timezone'] = trim($_POST['timezone'] ?? 'Asia/Kolkata');
    $seedData = isset($_POST['seed_data']);

    // Validations
    if (empty($formData['db_host']) || empty($formData['db_name']) || empty($formData['db_user'])) {
        $error = "Please fill in all required database connection fields.";
    } elseif (empty($formData['admin_user']) || empty($formData['admin_email'])) {
        $error = "Administrator username and email address are required.";
    } elseif (strlen($adminPass) < 6) {
        $error = "Administrator password must be at least 6 characters in length.";
    } elseif ($adminPass !== $adminPassConfirm) {
        $error = "Administrator password and password confirmation do not match.";
    } else {
        try {
            // 1. Test database connection
            $dsnNoDb = "mysql:host={$formData['db_host']};port={$formData['db_port']};charset=utf8mb4";
            $pdo = new PDO($dsnNoDb, $formData['db_user'], $formData['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            // 2. Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$formData['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$formData['db_name']}`");

            // 3. Execute Schema
            if (!file_exists($schemaFile)) {
                throw new RuntimeException("Database schema file not found at: {$schemaFile}");
            }
            $schemaSql = file_get_contents($schemaFile);
            $pdo->exec($schemaSql);

            // 4. Seed or Update Administrator
            $adminHash = password_hash($adminPass, PASSWORD_BCRYPT);
            $chkAdmin = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $chkAdmin->execute([$formData['admin_user'], $formData['admin_email']]);
            $existingAdminId = $chkAdmin->fetchColumn();

            if ($existingAdminId) {
                $upd = $pdo->prepare("UPDATE admins SET username = ?, email = ?, password_hash = ?, role_id = 1, status = 'active', updated_at = NOW() WHERE id = ?");
                $upd->execute([$formData['admin_user'], $formData['admin_email'], $adminHash, $existingAdminId]);
            } else {
                $ins = $pdo->prepare("INSERT INTO admins (username, email, password_hash, role_id, status, created_at, updated_at) VALUES (?, ?, ?, 1, 'active', NOW(), NOW())");
                $ins->execute([$formData['admin_user'], $formData['admin_email'], $adminHash]);
            }

            // 5. Seed Starter Data if checked
            if ($seedData) {
                require_once $baseDir . '/vendor/autoload.php';
                require_once $baseDir . '/database/seeds/DatabaseSeeder.php';
                ob_start();
                Database\Seeds\DatabaseSeeder::run($pdo);
                ob_end_clean();
            }

            // 6. Write or Update .env file
            $appKey = 'base64_' . bin2hex(random_bytes(16));
            $csrfKey = 'csrf_' . bin2hex(random_bytes(16));

            $newEnvContent = <<<ENV
# Application Configuration
APP_NAME="{$formData['app_name']}"
APP_ENV=production
APP_DEBUG=false
APP_URL={$formData['app_url']}
APP_KEY={$appKey}
THEME=classic

# Database Configuration (MySQL / MariaDB)
DB_HOST={$formData['db_host']}
DB_PORT={$formData['db_port']}
DB_DATABASE={$formData['db_name']}
DB_USERNAME={$formData['db_user']}
DB_PASSWORD={$formData['db_pass']}
DB_CHARSET=utf8mb4

# Payment Gateway (Razorpay)
RAZORPAY_ENABLED=true
RAZORPAY_MODE=test
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=

# Currency & Rates
DEFAULT_CURRENCY={$formData['currency']}
PANEL_TIMEZONE={$formData['timezone']}

# Security & Sessions
SESSION_LIFETIME=7200
CSRF_SECRET={$csrfKey}
RATE_LIMIT_PER_MINUTE=60
ENV;

            file_put_contents($envFile, $newEnvContent);

            // Redirect to step 4 complete
            if (!headers_sent()) {
                header("Location: complete.php?installed=1&admin=" . urlencode($formData['admin_user']));
                exit;
            } else {
                echo "<script>window.location.href = 'complete.php?installed=1&admin=" . urlencode($formData['admin_user']) . "';</script>";
                echo "<noscript><meta http-equiv='refresh' content='0;url=complete.php?installed=1&admin=" . urlencode($formData['admin_user']) . "'></noscript>";
                exit;
            }
        } catch (Throwable $e) {
            $error = "Database setup failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex SMM - Database & Admin Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-between p-4 sm:p-6 lg:p-8 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-slate-950">
    <div class="max-w-3xl w-full mx-auto my-auto">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 mb-4 shadow-lg shadow-rose-500/10">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Apex SMM Platform</h1>
            <p class="text-sm text-slate-400 mt-1">High-Speed Social Media Marketing Reseller System</p>
        </div>

        <!-- Wizard Stepper Navigation -->
        <div class="bg-slate-900/80 border border-slate-800/80 backdrop-blur rounded-2xl p-4 sm:p-6 mb-6 shadow-xl">
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center mb-1.5 shadow">
                        ✓
                    </div>
                    <span class="text-slate-400 font-medium">Welcome</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center mb-1.5 shadow">
                        ✓
                    </div>
                    <span class="text-slate-400 font-medium">Requirements</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-rose-500 text-white font-bold flex items-center justify-center ring-4 ring-rose-500/20 mb-1.5 shadow">
                        3
                    </div>
                    <span class="font-semibold text-rose-400">Database</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 font-semibold flex items-center justify-center mb-1.5">
                        4
                    </div>
                    <span class="text-slate-400">Complete</span>
                </div>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-white tracking-tight">Database & Administrator Setup</h2>
                <p class="text-xs text-slate-400 mt-0.5">Configure your MySQL database, administrator login, and panel defaults</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-semibold">Setup Error:</span>
                        <p class="text-slate-300 mt-1"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Section 1: Database Credentials -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                        <div class="w-5 h-5 rounded bg-rose-500/10 text-rose-400 flex items-center justify-center text-xs font-bold">1</div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Database Connection (MySQL / MariaDB)</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Database Host <span class="text-rose-400">*</span></label>
                            <input type="text" name="db_host" value="<?= htmlspecialchars((string)$formData['db_host']) ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Port <span class="text-rose-400">*</span></label>
                            <input type="number" name="db_port" value="<?= (int)$formData['db_port'] ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Database Name <span class="text-rose-400">*</span></label>
                            <input type="text" name="db_name" value="<?= htmlspecialchars((string)$formData['db_name']) ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                            <span class="text-[10px] text-slate-500 mt-1 block">Will be created automatically if missing</span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Database Username <span class="text-rose-400">*</span></label>
                            <input type="text" name="db_user" value="<?= htmlspecialchars((string)$formData['db_user']) ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">Database Password</label>
                        <input type="password" name="db_pass" value="<?= htmlspecialchars((string)$formData['db_pass']) ?>" placeholder="Leave blank if no password" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                    </div>
                </div>

                <!-- Section 2: Administrator Credentials -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                        <div class="w-5 h-5 rounded bg-rose-500/10 text-rose-400 flex items-center justify-center text-xs font-bold">2</div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Master Administrator Account</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Admin Username <span class="text-rose-400">*</span></label>
                            <input type="text" name="admin_user" value="<?= htmlspecialchars((string)$formData['admin_user']) ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Admin Email <span class="text-rose-400">*</span></label>
                            <input type="email" name="admin_email" value="<?= htmlspecialchars((string)$formData['admin_email']) ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Admin Password <span class="text-rose-400">*</span></label>
                            <input type="password" name="admin_pass" value="admin123" required minlength="6" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                            <span class="text-[10px] text-slate-500 mt-1 block">Default: <code class="text-slate-400">admin123</code> (Min. 6 chars)</span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Confirm Password <span class="text-rose-400">*</span></label>
                            <input type="password" name="admin_pass_confirm" value="admin123" required minlength="6" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 transition">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Platform Preferences -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                        <div class="w-5 h-5 rounded bg-rose-500/10 text-rose-400 flex items-center justify-center text-xs font-bold">3</div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Platform Settings & Starter Data</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Application Name</label>
                            <input type="text" name="app_name" value="<?= htmlspecialchars((string)$formData['app_name']) ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white transition">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Application URL</label>
                            <input type="url" name="app_url" value="<?= htmlspecialchars((string)$formData['app_url']) ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Default Currency</label>
                            <select name="currency" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white transition">
                                <option value="INR" <?= $formData['currency'] === 'INR' ? 'selected' : '' ?>>INR (₹ - Indian Rupee)</option>
                                <option value="USD" <?= $formData['currency'] === 'USD' ? 'selected' : '' ?>>USD ($ - United States Dollar)</option>
                                <option value="EUR" <?= $formData['currency'] === 'EUR' ? 'selected' : '' ?>>EUR (€ - Euro)</option>
                                <option value="GBP" <?= $formData['currency'] === 'GBP' ? 'selected' : '' ?>>GBP (£ - British Pound)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">Timezone</label>
                            <input type="text" name="timezone" value="<?= htmlspecialchars((string)$formData['timezone']) ?>" required class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl px-3.5 py-2.5 text-xs text-white transition">
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-800/40 border border-slate-800/80">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="seed_data" value="1" checked class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-rose-500 focus:ring-rose-500 mt-0.5">
                            <div class="text-xs">
                                <span class="font-semibold text-white">Import Starter Catalog & Demo Data (Recommended)</span>
                                <p class="text-slate-400 mt-0.5">Seeds verified social media categories (Instagram, YouTube, TikTok, Telegram, X, Spotify), wholesale services with realistic pricing, sample provider configuration, and demo user (<code class="text-slate-300">demo / demo123</code>).</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-800/80">
                    <a href="requirements.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Back to Requirements</span>
                    </a>

                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-rose-500 hover:bg-rose-600 active:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-lg shadow-rose-500/20 transition">
                        <span>Install & Build Database</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; <?= date('Y') ?> Apex SMM Services. Step 3 of 4: Database Configuration.
        </p>
    </div>
</body>
</html>
