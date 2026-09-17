<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(config('app.name', 'Apex SMM Services')) ?> - Leading Social Media Marketing Solutions</title>
    <meta name="description" content="High-speed, premium social media marketing panel offering automated fulfillment, wholesale pricing, instant delivery, and 24/7 support.">
    <meta property="og:title" content="<?= e(config('app.name', 'Apex SMM Services')) ?>">
    <meta property="og:description" content="Wholesale SMM reseller panel with instant API fulfillment, secure Razorpay checkout, and competitive rates.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-full flex flex-col bg-slate-950 text-slate-100">

    <!-- Global Flash Notice -->
    <?php if ($success = flash('success')): ?>
        <div class="bg-emerald-500/10 border-b border-emerald-500/20 text-emerald-400 px-4 py-3 text-center text-sm font-medium flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <?= e($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error = flash('error')): ?>
        <div class="bg-rose-500/10 border-b border-rose-500/20 text-rose-400 px-4 py-3 text-center text-sm font-medium flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 glass-card border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <a href="/" class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-600/30 text-white font-bold text-lg tracking-wider">
                            ⚡
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white"><?= e(config('app.name', 'Apex SMM')) ?></span>
                    </a>
                </div>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <a href="/" class="hover:text-white transition-colors">Home</a>
                    <a href="/services" class="hover:text-white transition-colors">Services List</a>
                    <a href="/api-docs" class="hover:text-white transition-colors">API Docs</a>
                    <a href="/terms" class="hover:text-white transition-colors">Terms</a>
                </nav>

                <!-- Auth / Action Buttons -->
                <div class="flex items-center gap-3">
                    <?php if ($user = auth_user()): ?>
                        <a href="/dashboard" class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-slate-800 border border-slate-700 hover:bg-slate-750 text-sm font-medium text-slate-200 transition">
                            <span>Balance: <strong class="text-emerald-400">₹<?= number_format((float)$user['balance'], 2) ?></strong></span>
                        </a>
                        <a href="/dashboard" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition">
                            Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/login" class="px-4 py-2 text-sm font-semibold text-slate-300 hover:text-white transition">
                            Sign In
                        </a>
                        <a href="/register" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Injection -->
    <main class="flex-1">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-12 text-slate-400 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-sm font-bold">⚡</div>
                    <span class="text-lg font-bold text-white"><?= e(config('app.name', 'Apex SMM')) ?></span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Automated social media marketing fulfillment platform engineered for agencies, resellers, and direct brands.
                </p>
                <div class="text-xs text-slate-500">
                    Base Currency: <span class="font-semibold text-slate-300">INR (₹)</span> | Gateway: <span class="font-semibold text-slate-300">Razorpay</span>
                </div>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-3 text-xs uppercase tracking-wider">Quick Navigation</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="/" class="hover:text-white transition">Home</a></li>
                    <li><a href="/services" class="hover:text-white transition">Services & Pricing</a></li>
                    <li><a href="/api-docs" class="hover:text-white transition">API Documentation</a></li>
                    <li><a href="/terms" class="hover:text-white transition">Terms of Service</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-3 text-xs uppercase tracking-wider">Client Area</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="/login" class="hover:text-white transition">Customer Login</a></li>
                    <li><a href="/register" class="hover:text-white transition">Create Account</a></li>
                    <li><a href="/wallet" class="hover:text-white transition">Add Funds</a></li>
                    <li><a href="/admin/login" class="hover:text-white transition">Admin Portal</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-3 text-xs uppercase tracking-wider">Security & Compliance</h4>
                <p class="text-xs text-slate-400 leading-relaxed mb-3">
                    Transactions are encrypted via 256-bit SSL. Fast automated processing with live status updates.
                </p>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    API & Fulfillment Operational
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pt-8 border-t border-slate-800 text-center text-xs text-slate-500">
            &copy; <?= date('Y') ?> <?= e(config('app.name', 'Apex SMM Services')) ?>. All rights reserved.
        </div>
    </footer>

</body>
</html>
