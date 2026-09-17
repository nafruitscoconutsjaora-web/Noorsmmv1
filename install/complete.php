<?php

declare(strict_types=1);

// Apex SMM Platform - Installation Wizard - Step 4: Complete & Launch

$baseDir = dirname(__DIR__);
$adminUser = htmlspecialchars($_GET['admin'] ?? 'admin');
$lockFile = __DIR__ . '/INSTALLED';

$lockMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lock_installer'])) {
    file_put_contents($lockFile, "Installed on " . date('Y-m-d H:i:s') . "\n");
    $lockMessage = "Installation wizard has been securely locked! To run setup again in the future, remove file: install/INSTALLED.";
}

$isLocked = file_exists($lockFile);

// Read app info from .env
$appName = 'Apex SMM Services';
$appUrl = 'http://localhost:3000';
$currency = 'INR';

if (file_exists($baseDir . '/.env')) {
    $lines = file($baseDir . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if ($k === 'APP_NAME') $appName = $v;
        if ($k === 'APP_URL') $appUrl = $v;
        if ($k === 'DEFAULT_CURRENCY') $currency = $v;
    }
}

$cronCmd = "* * * * * php " . $baseDir . "/bin/cron.php all >> " . $baseDir . "/storage/logs/cron.log 2>&1";
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex SMM - Installation Complete</title>
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
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 mb-4 shadow-lg shadow-emerald-500/10">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Apex SMM Platform</h1>
            <p class="text-sm text-slate-400 mt-1">Installation Successful & Ready for Production</p>
        </div>

        <!-- Wizard Stepper Navigation -->
        <div class="bg-slate-900/80 border border-slate-800/80 backdrop-blur rounded-2xl p-4 sm:p-6 mb-6 shadow-xl">
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center mb-1.5 shadow">
                        ✓
                    </div>
                    <span class="text-emerald-400 font-medium">Welcome</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center mb-1.5 shadow">
                        ✓
                    </div>
                    <span class="text-emerald-400 font-medium">Requirements</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center mb-1.5 shadow">
                        ✓
                    </div>
                    <span class="text-emerald-400 font-medium">Database</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center ring-4 ring-emerald-500/20 mb-1.5 shadow">
                        ✓
                    </div>
                    <span class="font-semibold text-emerald-400">Complete</span>
                </div>
            </div>
        </div>

        <!-- Main Complete Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>

            <?php if ($lockMessage): ?>
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span><?= htmlspecialchars($lockMessage) ?></span>
                </div>
            <?php endif; ?>

            <!-- Hero Banner -->
            <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-950/40 via-slate-900 to-slate-900 border border-emerald-500/20">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white tracking-tight">Installation Completed Successfully!</h2>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            Your <span class="text-white font-semibold"><?= htmlspecialchars($appName) ?></span> platform has been successfully deployed. Database schema tables have been initialized, currency formatting set to <span class="font-mono text-emerald-400"><?= htmlspecialchars($currency) ?></span>, and the administrator profile is active.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Credentials & Portal Reference Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Admin Card -->
                <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-800/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Administration Portal
                        </span>
                        <a href="/admin/login" class="text-xs text-rose-400 hover:text-rose-300 font-semibold inline-flex items-center gap-1">
                            Login &rarr;
                        </a>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-800">
                            <span class="text-slate-400">Portal URL:</span>
                            <a href="/admin/login" class="font-mono text-slate-200 hover:underline">/admin/login</a>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800">
                            <span class="text-slate-400">Username:</span>
                            <span class="font-mono text-white font-semibold"><?= htmlspecialchars($adminUser) ?></span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-400">Access Level:</span>
                            <span class="text-emerald-400 font-medium">Super Administrator</span>
                        </div>
                    </div>
                    <a href="/admin/login" class="block w-full text-center py-2.5 bg-rose-500 hover:bg-rose-600 text-white text-xs font-semibold rounded-xl shadow transition">
                        Open Admin Dashboard
                    </a>
                </div>

                <!-- User Portal Card -->
                <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-800/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Customer Portal
                        </span>
                        <a href="/login" class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold inline-flex items-center gap-1">
                            Login &rarr;
                        </a>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-800">
                            <span class="text-slate-400">Portal URL:</span>
                            <a href="/login" class="font-mono text-slate-200 hover:underline">/login</a>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800">
                            <span class="text-slate-400">Demo User:</span>
                            <span class="font-mono text-white font-semibold">demo / demo123</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-400">Preloaded Wallet:</span>
                            <span class="text-emerald-400 font-medium">₹1,500.00 (Demo)</span>
                        </div>
                    </div>
                    <a href="/login" class="block w-full text-center py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition">
                        Open Customer Portal
                    </a>
                </div>
            </div>

            <!-- Background Automation Cron Card -->
            <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-800/80 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Automated Background Cron Scheduler
                    </span>
                    <span class="text-[10px] text-sky-400 bg-sky-500/10 px-2 py-0.5 rounded">Every 1 Minute</span>
                </div>
                <p class="text-xs text-slate-400">
                    To automate wholesale order dispatching, real-time status tracking, and provider balance checks, add the following single cron entry to your server:
                </p>
                <div class="relative">
                    <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-sky-300 text-xs font-mono overflow-x-auto select-all"><?= htmlspecialchars($cronCmd) ?></pre>
                </div>
            </div>

            <!-- Security Notice & Lock Action -->
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="text-xs font-bold text-amber-300">Security Recommendation</h4>
                        <p class="text-[11px] text-slate-300 mt-0.5">
                            <?= $isLocked ? 'Installer is currently locked with file <code>install/INSTALLED</code>.' : 'Lock or delete the <code>/install</code> folder to prevent unauthorized re-installation.' ?>
                        </p>
                    </div>
                </div>

                <?php if (!$isLocked): ?>
                    <form method="POST" class="shrink-0">
                        <button type="submit" name="lock_installer" value="1" class="px-3.5 py-2 bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 text-xs font-semibold rounded-xl border border-amber-500/30 transition">
                            Lock Installer Now
                        </button>
                    </form>
                <?php else: ?>
                    <span class="px-3 py-1.5 bg-emerald-500/20 text-emerald-300 text-xs font-semibold rounded-xl border border-emerald-500/30">
                        ✓ Locked
                    </span>
                <?php endif; ?>
            </div>

            <!-- Quick Links Footer -->
            <div class="pt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-800/80 text-xs">
                <div class="flex gap-3 text-slate-400">
                    <a href="/services" class="hover:text-white transition">Public Catalog</a>
                    <span>&bull;</span>
                    <a href="/api-docs" class="hover:text-white transition">SMM API v2 Docs</a>
                    <span>&bull;</span>
                    <a href="/login" class="hover:text-white transition">User Login</a>
                </div>

                <a href="/admin/login" class="inline-flex items-center gap-2 px-6 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-semibold rounded-xl shadow-lg shadow-rose-500/20 transition">
                    <span>Proceed to Admin Dashboard</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; <?= date('Y') ?> Apex SMM Services. Installation successfully finished.
        </p>
    </div>
</body>
</html>
