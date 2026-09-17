<?php

declare(strict_types=1);

// Apex SMM Platform - Installation Wizard - Step 1: Welcome

$baseDir = dirname(__DIR__);
$envFile = $baseDir . '/.env';
$isInstalled = false;

if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (str_contains($envContent, 'DB_DATABASE=') && !str_contains($envContent, 'DB_DATABASE=""')) {
        $isInstalled = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex SMM - Installation Wizard</title>
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
                    <div class="w-8 h-8 rounded-full bg-rose-500 text-white font-bold flex items-center justify-center ring-4 ring-rose-500/20 mb-1.5 shadow">
                        1
                    </div>
                    <span class="font-semibold text-rose-400">Welcome</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 font-semibold flex items-center justify-center mb-1.5">
                        2
                    </div>
                    <span class="text-slate-400">Requirements</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 font-semibold flex items-center justify-center mb-1.5">
                        3
                    </div>
                    <span class="text-slate-400">Database</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 font-semibold flex items-center justify-center mb-1.5">
                        4
                    </div>
                    <span class="text-slate-400">Complete</span>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-rose-500/5 rounded-full blur-3xl pointer-events-none"></div>

            <?php if ($isInstalled): ?>
                <div class="mb-6 p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <span class="font-semibold">Notice: Existing installation detected.</span>
                        <p class="text-slate-400 mt-1">An active <code class="text-amber-300 font-mono">.env</code> configuration already exists. You can proceed with the setup wizard to reconfigure database settings, or visit your panel directly:</p>
                        <div class="mt-2 flex gap-2">
                            <a href="/admin/login" class="inline-flex items-center px-2.5 py-1 bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 rounded text-[11px] font-medium transition">Admin Panel &rarr;</a>
                            <a href="/login" class="inline-flex items-center px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-[11px] font-medium transition">User Portal &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <h2 class="text-xl font-bold text-white tracking-tight">Welcome to the Installation Wizard</h2>
                <p class="text-sm text-slate-300 leading-relaxed">
                    Thank you for choosing <span class="font-semibold text-white">Apex SMM Services</span>. This quick, 4-step wizard will verify your server prerequisites, configure your MySQL database connection, import optimized schema tables, and set up your master administrator account.
                </p>

                <!-- Feature Highlights -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <div class="p-3.5 rounded-xl bg-slate-800/40 border border-slate-800/80 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-white">Standard SMM API v2</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Native support for all major wholesale providers and reseller endpoints.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-800/40 border border-slate-800/80 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-white">Financial Accuracy</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Full BCMath 8-decimal wallet ledgers and multi-currency pricing.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-800/40 border border-slate-800/80 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-white">Payment Integrations</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Integrated Razorpay checkout with HMAC-SHA256 signature verification.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-800/40 border border-slate-800/80 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-white">Automated Background Cron</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Continuous order status reconciliation, refill triggers, and sync jobs.</p>
                        </div>
                    </div>
                </div>

                <!-- Terms notice -->
                <div class="pt-2 text-xs text-slate-400 border-t border-slate-800/80">
                    By clicking <span class="text-slate-200 font-medium">Continue to System Requirements</span>, you confirm that you have server access to MySQL and write permissions for the application root.
                </div>
            </div>

            <!-- Action buttons -->
            <div class="mt-8 pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-800/80">
                <span class="text-xs text-slate-500">Step 1 of 4: Welcome</span>
                <a href="requirements.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-rose-500 hover:bg-rose-600 active:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-lg shadow-rose-500/20 transition">
                    <span>Continue to System Requirements</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; <?= date('Y') ?> Apex SMM Services. Production Installation Suite.
        </p>
    </div>
</body>
</html>
