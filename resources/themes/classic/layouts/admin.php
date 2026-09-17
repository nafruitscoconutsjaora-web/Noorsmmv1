<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMM Admin Portal - <?= e(config('app.name', 'Apex SMM')) ?></title>
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
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col md:flex-row bg-slate-950 text-slate-100">

    <?php
    $adm = session('admin');
    $admAct = 'flex items-center gap-3 px-3 py-2 rounded-lg bg-rose-600/15 text-rose-400 border border-rose-500/30 font-semibold shadow-sm transition';
    $admInact = 'flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white border border-transparent transition group';
    ?>

    <!-- Mobile Slide-out Sidebar Drawer Backdrop -->
    <div id="admin-mobile-backdrop" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 transition-opacity duration-300 opacity-0 pointer-events-none md:hidden" aria-hidden="true"></div>

    <!-- Mobile Slide-out Admin Drawer -->
    <aside id="admin-mobile-sidebar" class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-slate-900 border-r border-slate-800 z-50 flex flex-col transform -translate-x-full transition-transform duration-300 ease-in-out md:hidden shadow-2xl">
        <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800 bg-slate-950/40">
            <a href="/admin/dashboard" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-rose-600 flex items-center justify-center text-white font-extrabold text-sm shadow-md shadow-rose-600/30">
                    🛡️
                </div>
                <div>
                    <div class="text-sm font-bold tracking-tight text-white">Admin Control</div>
                    <div class="text-[10px] text-slate-400 font-mono tracking-widest uppercase">Apex Core Engine</div>
                </div>
            </a>
            <button id="admin-mobile-close-btn" type="button" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition" aria-label="Close navigation">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto text-sm font-medium text-slate-300">
            <a href="/admin/dashboard" class="<?= is_active_route(['/admin', '/admin/dashboard'], $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </a>
            <a href="/admin/orders" class="<?= is_active_route('/admin/orders', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>Orders</span>
            </a>
            <a href="/admin/refills" class="<?= is_active_route('/admin/refills', $admAct, $admInact) ?>">
                <span class="text-rose-400">🔄</span>
                <span>Refills & Cancels</span>
            </a>
            <a href="/admin/schedules" class="<?= is_active_route('/admin/schedules', $admAct, $admInact) ?>">
                <span class="text-rose-400">⏰</span>
                <span>Drip & Schedules</span>
            </a>
            <a href="/admin/services" class="<?= is_active_route('/admin/services', $admAct, $admInact, ['/admin/services/quality']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Services</span>
            </a>
            <?php if (admin_can('pricing')): ?>
            <a href="/admin/pricing" class="<?= is_active_route('/admin/pricing', $admAct, $admInact) ?>">
                <span class="text-rose-400">🏷️</span>
                <span>Pricing & Markups</span>
            </a>
            <?php endif; ?>
            <a href="/admin/services/quality" class="<?= is_active_route('/admin/services/quality', $admAct, $admInact) ?>">
                <span class="text-rose-400">⚡</span>
                <span>Service Quality</span>
            </a>
            <a href="/admin/categories" class="<?= is_active_route('/admin/categories', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                <span>Categories</span>
            </a>
            <a href="/admin/providers" class="<?= is_active_route('/admin/providers', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>API Providers</span>
            </a>
            <a href="/admin/users" class="<?= is_active_route('/admin/users', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>Users & Accounts</span>
            </a>
            <?php if (admin_can('wallets')): ?>
            <a href="/admin/wallets" class="<?= is_active_route('/admin/wallets', $admAct, $admInact) ?>">
                <span class="text-rose-400">💳</span>
                <span>Wallet Liquidity</span>
            </a>
            <?php endif; ?>
            <a href="/admin/payments" class="<?= is_active_route('/admin/payments', $admAct, $admInact, ['/admin/payments/reconciliation']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Payments Log</span>
            </a>
            <a href="/admin/payments/reconciliation" class="<?= is_active_route('/admin/payments/reconciliation', $admAct, $admInact) ?>">
                <span class="text-rose-400">⚖️</span>
                <span>Reconciliation</span>
            </a>
            <a href="/admin/analytics" class="<?= is_active_route('/admin/analytics', $admAct, $admInact, ['/admin/analytics/users']) ?>">
                <span class="text-rose-400">📊</span>
                <span>BI Analytics</span>
            </a>
            <a href="/admin/analytics/users" class="<?= is_active_route('/admin/analytics/users', $admAct, $admInact) ?>">
                <span class="text-rose-400">👥</span>
                <span>User Cohorts</span>
            </a>
            <a href="/admin/referrals" class="<?= is_active_route('/admin/referrals', $admAct, $admInact) ?>">
                <span class="text-rose-400">🎁</span>
                <span>Affiliate Network</span>
            </a>
            <a href="/admin/tickets" class="<?= is_active_route('/admin/tickets', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                <span>Support Tickets</span>
            </a>
            <a href="/admin/notifications" class="<?= is_active_route('/admin/notifications', $admAct, $admInact) ?>">
                <span class="text-rose-400">📢</span>
                <span>Announcements</span>
            </a>
            <a href="/admin/content" class="<?= is_active_route('/admin/content', $admAct, $admInact) ?>">
                <span class="text-rose-400">📝</span>
                <span>Help CMS & FAQs</span>
            </a>
            <a href="/admin/email" class="<?= is_active_route('/admin/email', $admAct, $admInact) ?>">
                <span class="text-rose-400">✉️</span>
                <span>Email Campaigns</span>
            </a>
            <a href="/admin/cron" class="<?= is_active_route('/admin/cron', $admAct, $admInact) ?>">
                <span class="text-rose-400">⚙️</span>
                <span>Cron Tasks</span>
            </a>
            <a href="/admin/system/health" class="<?= is_active_route('/admin/system/health', $admAct, $admInact) ?>">
                <span class="text-rose-400">🩺</span>
                <span>System Health</span>
            </a>
            <a href="/admin/reports" class="<?= is_active_route('/admin/reports', $admAct, $admInact) ?>">
                <span class="text-rose-400">📑</span>
                <span>Data Export</span>
            </a>
            <?php if (admin_can('roles')): ?>
            <a href="/admin/roles" class="<?= is_active_route('/admin/roles', $admAct, $admInact) ?>">
                <span class="text-rose-400">🛡️</span>
                <span>Staff Roles (RBAC)</span>
            </a>
            <?php endif; ?>
            <a href="/admin/fraud" class="<?= is_active_route('/admin/fraud', $admAct, $admInact) ?>">
                <span class="text-rose-400">🚨</span>
                <span>Fraud Monitor</span>
            </a>
            <?php if (admin_can('maintenance')): ?>
            <a href="/admin/maintenance" class="<?= is_active_route('/admin/maintenance', $admAct, $admInact) ?>">
                <span class="text-rose-400">🛠️</span>
                <span>Maintenance & Backup</span>
            </a>
            <?php endif; ?>
            <a href="/admin/api" class="<?= is_active_route('/admin/api', $admAct, $admInact) ?>">
                <span class="text-rose-400">🔑</span>
                <span>API Consumers</span>
            </a>
            <?php if (admin_can('settings')): ?>
            <a href="/admin/settings" class="<?= is_active_route('/admin/settings', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Settings & System</span>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Admin Profile -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <div class="flex items-center justify-between">
                <div class="truncate">
                    <div class="text-xs font-bold text-rose-400 truncate"><?= e($adm['username'] ?? 'Administrator') ?></div>
                    <div class="text-[10px] text-slate-500 font-mono tracking-wider"><?= (int)($adm['role_id'] ?? 0) === 1 ? 'SUPERADMIN' : 'STAFF AGENT' ?></div>
                </div>
                <form action="/admin/logout" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 transition" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Desktop Admin Sidebar (Fixed) -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex-col fixed inset-y-0 z-30 hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-950/40">
            <a href="/admin/dashboard" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-rose-600 flex items-center justify-center text-white font-extrabold text-sm shadow-md shadow-rose-600/30">
                    🛡️
                </div>
                <div>
                    <div class="text-sm font-bold tracking-tight text-white">Admin Control</div>
                    <div class="text-[10px] text-slate-400 font-mono tracking-widest uppercase">Apex Core Engine</div>
                </div>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto text-sm font-medium text-slate-300">
            <a href="/admin/dashboard" class="<?= is_active_route(['/admin', '/admin/dashboard'], $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </a>
            <a href="/admin/orders" class="<?= is_active_route('/admin/orders', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>Orders</span>
            </a>
            <a href="/admin/refills" class="<?= is_active_route('/admin/refills', $admAct, $admInact) ?>">
                <span class="text-rose-400">🔄</span>
                <span>Refills & Cancels</span>
            </a>
            <a href="/admin/schedules" class="<?= is_active_route('/admin/schedules', $admAct, $admInact) ?>">
                <span class="text-rose-400">⏰</span>
                <span>Drip & Schedules</span>
            </a>
            <a href="/admin/services" class="<?= is_active_route('/admin/services', $admAct, $admInact, ['/admin/services/quality']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Services</span>
            </a>
            <?php if (admin_can('pricing')): ?>
            <a href="/admin/pricing" class="<?= is_active_route('/admin/pricing', $admAct, $admInact) ?>">
                <span class="text-rose-400">🏷️</span>
                <span>Pricing & Markups</span>
            </a>
            <?php endif; ?>
            <a href="/admin/services/quality" class="<?= is_active_route('/admin/services/quality', $admAct, $admInact) ?>">
                <span class="text-rose-400">⚡</span>
                <span>Service Quality</span>
            </a>
            <a href="/admin/categories" class="<?= is_active_route('/admin/categories', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                <span>Categories</span>
            </a>
            <a href="/admin/providers" class="<?= is_active_route('/admin/providers', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>API Providers</span>
            </a>
            <a href="/admin/users" class="<?= is_active_route('/admin/users', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>Users & Accounts</span>
            </a>
            <?php if (admin_can('wallets')): ?>
            <a href="/admin/wallets" class="<?= is_active_route('/admin/wallets', $admAct, $admInact) ?>">
                <span class="text-rose-400">💳</span>
                <span>Wallet Liquidity</span>
            </a>
            <?php endif; ?>
            <a href="/admin/payments" class="<?= is_active_route('/admin/payments', $admAct, $admInact, ['/admin/payments/reconciliation']) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Payments Log</span>
            </a>
            <a href="/admin/payments/reconciliation" class="<?= is_active_route('/admin/payments/reconciliation', $admAct, $admInact) ?>">
                <span class="text-rose-400">⚖️</span>
                <span>Reconciliation</span>
            </a>
            <a href="/admin/analytics" class="<?= is_active_route('/admin/analytics', $admAct, $admInact, ['/admin/analytics/users']) ?>">
                <span class="text-rose-400">📊</span>
                <span>BI Analytics</span>
            </a>
            <a href="/admin/analytics/users" class="<?= is_active_route('/admin/analytics/users', $admAct, $admInact) ?>">
                <span class="text-rose-400">👥</span>
                <span>User Cohorts</span>
            </a>
            <a href="/admin/referrals" class="<?= is_active_route('/admin/referrals', $admAct, $admInact) ?>">
                <span class="text-rose-400">🎁</span>
                <span>Affiliate Network</span>
            </a>
            <a href="/admin/tickets" class="<?= is_active_route('/admin/tickets', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                <span>Support Tickets</span>
            </a>
            <a href="/admin/notifications" class="<?= is_active_route('/admin/notifications', $admAct, $admInact) ?>">
                <span class="text-rose-400">📢</span>
                <span>Announcements</span>
            </a>
            <a href="/admin/content" class="<?= is_active_route('/admin/content', $admAct, $admInact) ?>">
                <span class="text-rose-400">📝</span>
                <span>Help CMS & FAQs</span>
            </a>
            <a href="/admin/email" class="<?= is_active_route('/admin/email', $admAct, $admInact) ?>">
                <span class="text-rose-400">✉️</span>
                <span>Email Campaigns</span>
            </a>
            <a href="/admin/cron" class="<?= is_active_route('/admin/cron', $admAct, $admInact) ?>">
                <span class="text-rose-400">⚙️</span>
                <span>Cron Tasks</span>
            </a>
            <a href="/admin/system/health" class="<?= is_active_route('/admin/system/health', $admAct, $admInact) ?>">
                <span class="text-rose-400">🩺</span>
                <span>System Health</span>
            </a>
            <a href="/admin/reports" class="<?= is_active_route('/admin/reports', $admAct, $admInact) ?>">
                <span class="text-rose-400">📑</span>
                <span>Data Export</span>
            </a>
            <?php if (admin_can('roles')): ?>
            <a href="/admin/roles" class="<?= is_active_route('/admin/roles', $admAct, $admInact) ?>">
                <span class="text-rose-400">🛡️</span>
                <span>Staff Roles (RBAC)</span>
            </a>
            <?php endif; ?>
            <a href="/admin/fraud" class="<?= is_active_route('/admin/fraud', $admAct, $admInact) ?>">
                <span class="text-rose-400">🚨</span>
                <span>Fraud Monitor</span>
            </a>
            <?php if (admin_can('maintenance')): ?>
            <a href="/admin/maintenance" class="<?= is_active_route('/admin/maintenance', $admAct, $admInact) ?>">
                <span class="text-rose-400">🛠️</span>
                <span>Maintenance & Backup</span>
            </a>
            <?php endif; ?>
            <a href="/admin/api" class="<?= is_active_route('/admin/api', $admAct, $admInact) ?>">
                <span class="text-rose-400">🔑</span>
                <span>API Consumers</span>
            </a>
            <?php if (admin_can('settings')): ?>
            <a href="/admin/settings" class="<?= is_active_route('/admin/settings', $admAct, $admInact) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Settings & System</span>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Admin Profile -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <div class="flex items-center justify-between">
                <div class="truncate">
                    <div class="text-xs font-bold text-rose-400 truncate"><?= e($adm['username'] ?? 'Administrator') ?></div>
                    <div class="text-[10px] text-slate-500 font-mono tracking-wider"><?= (int)($adm['role_id'] ?? 0) === 1 ? 'SUPERADMIN' : 'STAFF AGENT' ?></div>
                </div>
                <form action="/admin/logout" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 transition" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 md:ml-64 flex flex-col min-h-screen">
        <header class="h-16 bg-slate-900 border-b border-slate-800 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button id="admin-mobile-toggle-btn" type="button" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition md:hidden focus:outline-none" aria-label="Open navigation menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2 md:hidden">
                    <div class="w-7 h-7 rounded-lg bg-rose-600 flex items-center justify-center text-white font-extrabold text-xs shadow-md shadow-rose-600/30">🛡️</div>
                    <span class="font-bold text-white tracking-tight">Admin Portal</span>
                </div>
                <a href="/" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-300 transition">
                    <span>View Client Site &rarr;</span>
                </a>
            </div>

            <div class="flex items-center gap-3 sm:gap-4 text-xs">
                <div class="text-slate-400 hidden sm:block">Server Time: <span class="text-slate-200 font-mono"><?= date('H:i:s') ?> UTC</span></div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="hidden sm:inline">Engine Live</span>
                </div>
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

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <?= $content ?>
        </main>
    </div>

    <!-- Mobile Slide-out Navigation Controller (Vanilla JS) -->
    <script>
    (function () {
        var toggleBtn = document.getElementById('admin-mobile-toggle-btn');
        var closeBtn = document.getElementById('admin-mobile-close-btn');
        var backdrop = document.getElementById('admin-mobile-backdrop');
        var sidebar = document.getElementById('admin-mobile-sidebar');

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
