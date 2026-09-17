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
<body class="min-h-full flex bg-slate-950 text-slate-100">

    <!-- Admin Sidebar -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col fixed inset-y-0 z-30 hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-950/40">
            <a href="/admin" class="flex items-center gap-2.5">
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
            <a href="/admin/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
            <a href="/admin/orders" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>Orders</span>
            </a>
            <a href="/admin/refills" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">🔄</span>
                <span>Refills & Cancels</span>
            </a>
            <a href="/admin/schedules" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">⏰</span>
                <span>Drip & Schedules</span>
            </a>
            <a href="/admin/services" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Services
            </a>
            <a href="/admin/pricing" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">🏷️</span>
                Pricing & Markups
            </a>
            <a href="/admin/services/quality" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">⚡</span>
                Service Quality
            </a>
            <a href="/admin/categories" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                Categories
            </a>
            <a href="/admin/providers" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                API Providers
            </a>
            <a href="/admin/users" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Users & Wallets
            </a>
            <a href="/admin/wallets" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">💳</span>
                Wallet Liquidity
            </a>
            <a href="/admin/payments" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Payments Log
            </a>
            <a href="/admin/payments/reconciliation" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">⚖️</span>
                Reconciliation
            </a>
            <a href="/admin/analytics" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">📊</span>
                BI Analytics
            </a>
            <a href="/admin/analytics/users" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">👥</span>
                User Cohorts
            </a>
            <a href="/admin/referrals" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">🎁</span>
                Affiliate Network
            </a>
            <a href="/admin/tickets" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                Support Tickets
            </a>
            <a href="/admin/notifications" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">📢</span>
                Announcements
            </a>
            <a href="/admin/content" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">📝</span>
                Help CMS & FAQs
            </a>
            <a href="/admin/email" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">✉️</span>
                Email Campaigns
            </a>
            <a href="/admin/cron" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">⚙️</span>
                Cron Tasks
            </a>
            <a href="/admin/system/health" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">🩺</span>
                System Health
            </a>
            <a href="/admin/reports" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">📑</span>
                Data Export
            </a>
            <a href="/admin/roles" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">🛡️</span>
                Staff Roles (RBAC)
            </a>
            <a href="/admin/fraud" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">🚨</span>
                Fraud Monitor
            </a>
            <a href="/admin/maintenance" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">🛠️</span>
                Maintenance & Backup
            </a>
            <a href="/admin/api" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <span class="text-rose-400">🔑</span>
                API Consumers
            </a>
            <a href="/admin/settings" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition group">
                <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Settings & Cron
            </a>
        </nav>

        <!-- Admin Profile -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/30">
            <?php $adm = session('admin'); ?>
            <div class="flex items-center justify-between">
                <div class="truncate">
                    <div class="text-xs font-bold text-rose-400 truncate"><?= e($adm['username'] ?? 'Administrator') ?></div>
                    <div class="text-[10px] text-slate-500 font-mono">SUPERADMIN</div>
                </div>
                <form action="/admin/logout" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 transition" title="Logout">
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
                <a href="/" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-750 text-xs font-medium text-slate-300 transition">
                    <span>View Client Site &rarr;</span>
                </a>
            </div>

            <div class="flex items-center gap-4 text-xs">
                <div class="text-slate-400">Server Time: <span class="text-slate-200 font-mono"><?= date('H:i:s') ?> UTC</span></div>
                <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
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

</body>
</html>
