<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(config('app.name', 'Apex SMM')) ?> - Client Portal</title>
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
        .glass-panel {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.07);
        }
    </style>
</head>
<body class="min-h-full flex flex-col md:flex-row bg-slate-950 text-slate-100">

    <?php 
    $u = auth_user(); 
    $act = 'flex items-center gap-3 px-3 py-2 rounded-lg bg-indigo-600/15 text-indigo-400 border border-indigo-500/30 font-semibold shadow-sm transition';
    $inact = 'flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white border border-transparent transition group';
    ?>

    <!-- Mobile Slide-out Sidebar Drawer Backdrop -->
    <div id="user-mobile-backdrop" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 transition-opacity duration-300 opacity-0 pointer-events-none md:hidden" aria-hidden="true"></div>

    <!-- Mobile Slide-out Sidebar Drawer -->
    <aside id="user-mobile-sidebar" class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-slate-900 border-r border-slate-800 z-50 flex flex-col transform -translate-x-full transition-transform duration-300 ease-in-out md:hidden shadow-2xl">
        <!-- Drawer Header -->
        <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800 bg-slate-950/40">
            <a href="/dashboard" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-600/30">
                    ⚡
                </div>
                <span class="text-base font-bold tracking-tight text-white"><?= e(config('app.name', 'Apex SMM')) ?></span>
            </a>
            <button id="user-mobile-close-btn" type="button" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition" aria-label="Close navigation">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Mobile Balance Widget -->
        <div class="p-4 mx-4 mt-4 rounded-xl bg-slate-850/80 border border-slate-800">
            <div class="text-xs font-medium text-slate-400">Available Balance</div>
            <div class="text-xl font-extrabold text-emerald-400 mt-1 font-mono">₹<?= number_format((float)($u['balance'] ?? 0), 2) ?></div>
            <a href="/wallet" class="mt-2.5 inline-flex items-center justify-center w-full px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white transition">
                + Add Funds
            </a>
        </div>

        <!-- Mobile Navigation Items -->
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto text-sm font-medium text-slate-300">
            <a href="/dashboard" class="<?= is_active_route('/dashboard', $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>
            <a href="/orders/new" class="<?= is_active_route('/orders/new', $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>New Order</span>
            </a>
            <a href="/orders" class="<?= is_active_route('/orders', $act, $inact, ['/orders/new', '/orders/analytics', '/orders/schedules', '/orders/refills']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Orders History</span>
            </a>
            <a href="/services-list" class="<?= is_active_route(['/services-list', '/services'], $act, $inact, ['/services/favorites', '/services/compare']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Services Catalog</span>
            </a>
            <a href="/services/favorites" class="<?= is_active_route('/services/favorites', $act, $inact) ?>">
                <span class="text-amber-400">★</span>
                <span>Favorite Services</span>
            </a>
            <a href="/services/compare" class="<?= is_active_route('/services/compare', $act, $inact) ?>">
                <span class="text-indigo-400">⚖️</span>
                <span>Compare Services</span>
            </a>
            <a href="/orders/analytics" class="<?= is_active_route('/orders/analytics', $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span>Order Analytics</span>
            </a>
            <a href="/orders/schedules" class="<?= is_active_route('/orders/schedules', $act, $inact) ?>">
                <span class="text-cyan-400">⏰</span>
                <span>Scheduled Orders</span>
            </a>
            <a href="/orders/refills" class="<?= is_active_route('/orders/refills', $act, $inact) ?>">
                <span class="text-emerald-400">🔄</span>
                <span>Refill Requests</span>
            </a>
            <a href="/wallet" class="<?= is_active_route('/wallet', $act, $inact, ['/wallet/activity']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                <span>Add Funds / Wallet</span>
            </a>
            <a href="/wallet/activity" class="<?= is_active_route('/wallet/activity', $act, $inact) ?>">
                <span class="text-indigo-400">📜</span>
                <span>Wallet Transactions</span>
            </a>
            <a href="/tickets" class="<?= is_active_route(['/tickets', '/tickets/new'], $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                <span>Support Tickets</span>
            </a>
            <a href="/notifications" class="<?= is_active_route('/notifications', $act, $inact) ?>">
                <span class="text-rose-400">🔔</span>
                <span>Notifications</span>
            </a>
            <a href="/referrals" class="<?= is_active_route('/referrals', $act, $inact) ?>">
                <span class="text-amber-400">🎁</span>
                <span>Affiliate & Referrals</span>
            </a>
            <a href="/rewards" class="<?= is_active_route('/rewards', $act, $inact) ?>">
                <span class="text-yellow-400">🏆</span>
                <span>VIP Rewards & Loyalty</span>
            </a>
            <a href="/reports/export" class="<?= is_active_route('/reports/export', $act, $inact) ?>">
                <span class="text-cyan-400">📑</span>
                <span>Data Export</span>
            </a>
            <a href="/help" class="<?= is_active_route(['/help', '/help/article'], $act, $inact) ?>">
                <span class="text-slate-400">💡</span>
                <span>Help & Guides</span>
            </a>
            <a href="/account/api" class="<?= is_active_route('/account/api', $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                <span>User API Keys</span>
            </a>
            <a href="/account/security" class="<?= is_active_route('/account/security', $act, $inact) ?>">
                <span class="text-indigo-400">🛡️</span>
                <span>Security & Sessions</span>
            </a>
            <a href="/account" class="<?= is_active_route('/account', $act, $inact, ['/account/api', '/account/security', '/account/preferences', '/account/verification']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Account Profile</span>
            </a>
            <a href="/account/preferences" class="<?= is_active_route('/account/preferences', $act, $inact) ?>">
                <span class="text-slate-400">⚙️</span>
                <span>Preferences</span>
            </a>
            <a href="/account/verification" class="<?= is_active_route('/account/verification', $act, $inact) ?>">
                <span class="text-emerald-400">✔</span>
                <span>Verification</span>
            </a>
        </nav>

        <!-- Mobile User Profile & Logout -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5 truncate">
                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-xs text-indigo-400">
                        <?= strtoupper(substr($u['username'] ?? 'U', 0, 2)) ?>
                    </div>
                    <div class="truncate">
                        <div class="text-xs font-semibold text-white truncate"><?= e($u['username'] ?? '') ?></div>
                        <div class="text-[11px] text-slate-400 truncate"><?= e($u['email'] ?? '') ?></div>
                    </div>
                </div>
                <form action="/logout" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" title="Logout" class="p-1.5 text-slate-400 hover:text-rose-400 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Desktop Sidebar (Fixed) -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex-col fixed inset-y-0 z-30 hidden md:flex">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-slate-800">
            <a href="/dashboard" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-600/30">
                    ⚡
                </div>
                <span class="text-lg font-bold tracking-tight text-white"><?= e(config('app.name', 'Apex SMM')) ?></span>
            </a>
        </div>

        <!-- Balance Widget in Sidebar -->
        <div class="p-4 mx-4 mt-4 rounded-xl bg-slate-850/80 border border-slate-800">
            <div class="text-xs font-medium text-slate-400">Available Balance</div>
            <div class="text-xl font-extrabold text-emerald-400 mt-1 font-mono">₹<?= number_format((float)($u['balance'] ?? 0), 2) ?></div>
            <a href="/wallet" class="mt-2.5 inline-flex items-center justify-center w-full px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white transition">
                + Add Funds
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto text-sm font-medium text-slate-300">
            <a href="/dashboard" class="<?= is_active_route('/dashboard', $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>
            <a href="/orders/new" class="<?= is_active_route('/orders/new', $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>New Order</span>
            </a>
            <a href="/orders" class="<?= is_active_route('/orders', $act, $inact, ['/orders/new', '/orders/analytics', '/orders/schedules', '/orders/refills']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Orders History</span>
            </a>
            <a href="/services-list" class="<?= is_active_route(['/services-list', '/services'], $act, $inact, ['/services/favorites', '/services/compare']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Services Catalog</span>
            </a>
            <a href="/services/favorites" class="<?= is_active_route('/services/favorites', $act, $inact) ?>">
                <span class="text-amber-400">★</span>
                <span>Favorite Services</span>
            </a>
            <a href="/services/compare" class="<?= is_active_route('/services/compare', $act, $inact) ?>">
                <span class="text-indigo-400">⚖️</span>
                <span>Compare Services</span>
            </a>
            <a href="/orders/analytics" class="<?= is_active_route('/orders/analytics', $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span>Order Analytics</span>
            </a>
            <a href="/orders/schedules" class="<?= is_active_route('/orders/schedules', $act, $inact) ?>">
                <span class="text-cyan-400">⏰</span>
                <span>Scheduled Orders</span>
            </a>
            <a href="/orders/refills" class="<?= is_active_route('/orders/refills', $act, $inact) ?>">
                <span class="text-emerald-400">🔄</span>
                <span>Refill Requests</span>
            </a>
            <a href="/wallet" class="<?= is_active_route('/wallet', $act, $inact, ['/wallet/activity']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                <span>Add Funds / Wallet</span>
            </a>
            <a href="/wallet/activity" class="<?= is_active_route('/wallet/activity', $act, $inact) ?>">
                <span class="text-indigo-400">📜</span>
                <span>Wallet Transactions</span>
            </a>
            <a href="/tickets" class="<?= is_active_route(['/tickets', '/tickets/new'], $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                <span>Support Tickets</span>
            </a>
            <a href="/notifications" class="<?= is_active_route('/notifications', $act, $inact) ?>">
                <span class="text-rose-400">🔔</span>
                <span>Notifications</span>
            </a>
            <a href="/referrals" class="<?= is_active_route('/referrals', $act, $inact) ?>">
                <span class="text-amber-400">🎁</span>
                <span>Affiliate & Referrals</span>
            </a>
            <a href="/rewards" class="<?= is_active_route('/rewards', $act, $inact) ?>">
                <span class="text-yellow-400">🏆</span>
                <span>VIP Rewards & Loyalty</span>
            </a>
            <a href="/reports/export" class="<?= is_active_route('/reports/export', $act, $inact) ?>">
                <span class="text-cyan-400">📑</span>
                <span>Data Export</span>
            </a>
            <a href="/help" class="<?= is_active_route(['/help', '/help/article'], $act, $inact) ?>">
                <span class="text-slate-400">💡</span>
                <span>Help & Guides</span>
            </a>
            <a href="/account/api" class="<?= is_active_route('/account/api', $act, $inact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                <span>User API Keys</span>
            </a>
            <a href="/account/security" class="<?= is_active_route('/account/security', $act, $inact) ?>">
                <span class="text-indigo-400">🛡️</span>
                <span>Security & Sessions</span>
            </a>
            <a href="/account" class="<?= is_active_route('/account', $act, $inact, ['/account/api', '/account/security', '/account/preferences', '/account/verification']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Account Profile</span>
            </a>
            <a href="/account/preferences" class="<?= is_active_route('/account/preferences', $act, $inact) ?>">
                <span class="text-slate-400">⚙️</span>
                <span>Preferences</span>
            </a>
            <a href="/account/verification" class="<?= is_active_route('/account/verification', $act, $inact) ?>">
                <span class="text-emerald-400">✔</span>
                <span>Verification</span>
            </a>
        </nav>

        <!-- User Logout Section -->
        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5 truncate">
                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-xs text-indigo-400 shrink-0">
                        <?= strtoupper(substr($u['username'] ?? 'U', 0, 2)) ?>
                    </div>
                    <div class="truncate">
                        <div class="text-xs font-semibold text-white truncate"><?= e($u['username'] ?? '') ?></div>
                        <div class="text-[11px] text-slate-400 truncate"><?= e($u['email'] ?? '') ?></div>
                    </div>
                </div>
                <form action="/logout" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" title="Logout" class="p-1.5 text-slate-400 hover:text-rose-400 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 md:ml-64 flex flex-col min-h-screen">
        <!-- Topbar -->
        <header class="h-16 bg-slate-900 border-b border-slate-800 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20">
            <!-- Left: Mobile Hamburger & Mobile Brand -->
            <div class="flex items-center gap-3">
                <button id="user-mobile-toggle-btn" type="button" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition md:hidden focus:outline-none" aria-label="Open navigation menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <a href="/dashboard" class="flex items-center gap-2 md:hidden">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-md shadow-indigo-600/30">⚡</div>
                    <span class="font-bold text-white tracking-tight"><?= e(config('app.name', 'Apex SMM')) ?></span>
                </a>
                <div class="hidden md:flex items-center gap-3">
                    <span class="text-xs font-medium text-slate-400">Platform Status:</span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        API Providers Online
                    </span>
                </div>
            </div>

            <!-- Right: Header Quick Actions -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <a href="/notifications" class="relative p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white transition" title="Notifications">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </a>
                <div class="flex items-center gap-1.5 sm:gap-2 bg-slate-800/80 border border-slate-700/80 rounded-lg px-2.5 sm:px-3 py-1.5 text-xs">
                    <span class="text-slate-400 hidden sm:inline">Balance:</span>
                    <span class="font-bold text-emerald-400 font-mono">₹<?= number_format((float)($u['balance'] ?? 0), 2) ?></span>
                </div>
                <a href="/orders/new" class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white shadow-sm shadow-indigo-600/30 transition">
                    <span>+ New Order</span>
                </a>
            </div>
        </header>

        <!-- Flash alerts -->
        <div class="px-4 sm:px-6 pt-4">
            <?php if ($success = flash('success')): ?>
                <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?= e($success) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error = flash('error')): ?>
                <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Content Body -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <?= $content ?>
        </main>
    </div>

    <!-- Mobile Slide-out Navigation Controller (Vanilla JS) -->
    <script>
    (function () {
        var toggleBtn = document.getElementById('user-mobile-toggle-btn');
        var closeBtn = document.getElementById('user-mobile-close-btn');
        var backdrop = document.getElementById('user-mobile-backdrop');
        var sidebar = document.getElementById('user-mobile-sidebar');

        function openNav() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            backdrop.classList.add('opacity-100', 'pointer-events-auto');
            document.body.classList.add('overflow-hidden');
        }

        function closeNav() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.remove('opacity-100', 'pointer-events-auto');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('overflow-hidden');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', openNav);
        if (closeBtn) closeBtn.addEventListener('click', closeNav);
        if (backdrop) backdrop.addEventListener('click', closeNav);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('translate-x-0')) {
                closeNav();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768 && sidebar && sidebar.classList.contains('translate-x-0')) {
                closeNav();
            }
        });
    })();
    </script>
</body>
</html>
