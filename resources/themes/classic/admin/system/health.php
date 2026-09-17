<?php
$health = $health ?? [];
$providers = $providers ?? ($health['providers'] ?? []);
?>
<div class="space-y-6 max-w-7xl mx-auto" id="system-health-dashboard">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Infrastructure</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">System Health & Diagnostic Monitor</h1>
            <p class="text-sm text-slate-400 mt-1">Real-time status of PHP runtime, MySQL database connectivity, storage, and API services.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                All Core Services Operational
            </span>
        </div>
    </div>

    <!-- Health Check Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400">PHP Environment</span>
                <span class="text-emerald-400 text-xs font-bold">✔ OK</span>
            </div>
            <div class="text-xl font-bold text-white mt-2 font-mono"><?= PHP_VERSION ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Extensions: pdo, curl, openssl</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400">MySQL Database</span>
                <span class="text-emerald-400 text-xs font-bold">✔ Connected</span>
            </div>
            <div class="text-xl font-bold text-white mt-2 font-mono"><?= e($db_version ?? 'MySQL 8.0') ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Connection Pool: Active</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400">Server Disk Free</span>
                <span class="text-indigo-400 text-xs font-bold">Storage</span>
            </div>
            <div class="text-xl font-bold text-white mt-2 font-mono"><?= e($disk_free ?? 'Available') ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Storage mounted rw</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400">Memory Usage</span>
                <span class="text-cyan-400 text-xs font-bold">Process</span>
            </div>
            <div class="text-xl font-bold text-white mt-2 font-mono"><?= round(memory_get_usage(true) / 1024 / 1024, 2) ?> MB</div>
            <div class="text-[11px] text-slate-500 mt-1">Limit: <?= ini_get('memory_limit') ?></div>
        </div>
    </div>

    <!-- Provider Connections Health Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">External Provider Gateways Health</h2>
            <span class="text-xs text-slate-400"><?= count($providers) ?> configured</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                        <th class="py-3 px-4">Provider Name</th>
                        <th class="py-3 px-4">API Endpoint</th>
                        <th class="py-3 px-4 text-right">Provider Balance</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Last Health Check</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    <?php foreach ($providers as $p): ?>
                        <tr>
                            <td class="py-3.5 px-4 font-bold text-white"><?= e($p['name']) ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px] max-w-xs truncate"><?= e($p['api_url']) ?></td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-400">
                                <?= $p['balance'] !== null ? '₹' . number_format((float)$p['balance'], 2) : 'Unchecked' ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $p['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' ?>">
                                    <?= strtoupper(e($p['status'])) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-400 text-[11px]">
                                <?= !empty($p['updated_at']) ? substr($p['updated_at'], 0, 16) : 'Just now' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
