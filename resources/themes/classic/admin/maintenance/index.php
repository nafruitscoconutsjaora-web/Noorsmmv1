<?php
$maintenance_active = $maintenance_active ?? ($is_maintenance ?? false);
?>
<div class="space-y-6 max-w-6xl mx-auto" id="admin-maintenance-backup">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">DevOps & Database</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Maintenance Mode & Database Backup</h1>
            <p class="text-sm text-slate-400 mt-1">Emergency maintenance toggles, database snapshot downloads, and cache purging.</p>
        </div>
    </div>

    <!-- Maintenance Controls -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Maintenance Mode Toggle -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-mono uppercase text-amber-400 font-bold">Platform State</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $maintenance_active ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' ?>">
                        <?= $maintenance_active ? 'MAINTENANCE ACTIVE' : 'LIVE ONLINE' ?>
                    </span>
                </div>
                <h2 class="text-base font-bold text-white">Maintenance Mode Toggle</h2>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    When maintenance mode is enabled, non-admin users will see a friendly maintenance splash screen. Admins retain uninterrupted access to this panel.
                </p>
            </div>
            <form action="/admin/maintenance/toggle" method="POST" class="mt-6">
                <?= csrf_field() ?>
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold transition shadow-sm <?= $maintenance_active ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-amber-600 hover:bg-amber-500 text-white' ?>">
                    <?= $maintenance_active ? 'Turn Platform Live Online' : 'Enable Maintenance Mode' ?>
                </button>
            </form>
        </div>

        <!-- Database Backup Engine -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-mono uppercase text-indigo-400 font-bold">Disaster Recovery</span>
                    <span class="text-xs text-slate-500 font-mono">SQL Dump</span>
                </div>
                <h2 class="text-base font-bold text-white">Generate Instant Database Backup</h2>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Create a full SQL schema and data snapshot of all tables (orders, users, payments, services, tickets) and download it directly.
                </p>
            </div>
            <a href="/admin/maintenance/backup" class="mt-6 w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm text-center">
                Download Backup Snapshot (.sql)
            </a>
        </div>
    </div>

    <!-- Cache Purge & Optimization Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-white">Purge System Template & Route Cache</h3>
            <p class="text-xs text-slate-400 mt-0.5">Flush cached compiled templates and refresh service catalog indexing.</p>
        </div>
        <form action="/admin/maintenance/clear-cache" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-semibold transition">
                Clear System Cache
            </button>
        </form>
    </div>
</div>
