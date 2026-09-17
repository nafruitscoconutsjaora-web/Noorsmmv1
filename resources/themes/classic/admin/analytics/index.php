<?php
$metrics = $metrics ?? [
    'total_revenue' => $stats['financials']['total_revenue'] ?? 0,
    'estimated_profit' => ($stats['financials']['total_revenue'] ?? 0) * 0.25,
    'total_orders' => $stats['financials']['total_orders'] ?? 0,
    'completion_rate' => !empty($stats['financials']['total_orders']) ? (($stats['financials']['completed_orders'] ?? 0) / $stats['financials']['total_orders'] * 100) : 0,
];
?>
<div class="space-y-6 max-w-7xl mx-auto" id="admin-advanced-analytics">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Business Intelligence</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Platform Analytics & Financial Performance</h1>
            <p class="text-sm text-slate-400 mt-1">Real-time revenue, gross profit margins, order velocity, and top selling services from MySQL data.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/analytics/users" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold border border-slate-700 transition">
                User Behavior Analytics &rarr;
            </a>
        </div>
    </div>

    <!-- Metric KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Total Revenue (Gross)</span>
            <div class="text-2xl font-extrabold text-emerald-400 mt-1">₹<?= number_format((float)$metrics['total_revenue'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">All completed customer orders</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Estimated Net Profit</span>
            <div class="text-2xl font-extrabold text-indigo-400 mt-1">₹<?= number_format((float)$metrics['estimated_profit'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">After provider cost deductions</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Orders Dispatched</span>
            <div class="text-2xl font-extrabold text-white mt-1"><?= number_format($metrics['total_orders']) ?></div>
            <div class="text-xs text-slate-500 mt-2">Platform lifetime orders</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Order Success Rate</span>
            <div class="text-2xl font-extrabold text-cyan-400 mt-1"><?= number_format((float)$metrics['completion_rate'], 1) ?>%</div>
            <div class="text-xs text-slate-500 mt-2">Completed / Non-failed orders</div>
        </div>
    </div>

    <!-- Grid for Top Services & Provider Volume -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Performing Services -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-base font-bold text-white mb-1">Top Selling Services by Revenue</h2>
            <p class="text-xs text-slate-400 mb-4">Services generating the highest sales volume.</p>

            <?php if (empty($top_services)): ?>
                <div class="p-8 text-center text-slate-500 text-xs">No order data available yet.</div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($top_services as $ts): ?>
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between">
                            <div class="truncate max-w-xs">
                                <div class="text-xs font-bold text-white truncate"><?= e($ts['name']) ?></div>
                                <div class="text-[11px] text-slate-400 font-mono"><?= number_format($ts['order_count']) ?> orders placed</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-mono font-bold text-emerald-400">₹<?= number_format((float)$ts['total_spent'], 2) ?></div>
                                <div class="text-[10px] text-slate-500 font-mono"><?= number_format($ts['total_quantity']) ?> units</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Provider Fulfillment Share -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-base font-bold text-white mb-1">Provider Volume & Cost Share</h2>
            <p class="text-xs text-slate-400 mb-4">Orders routed per connected API provider.</p>

            <?php if (empty($provider_stats)): ?>
                <div class="p-8 text-center text-slate-500 text-xs">No provider order volume logged.</div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($provider_stats as $ps): ?>
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-white"><?= e($ps['name']) ?></div>
                                <div class="text-[11px] text-slate-400 font-mono"><?= number_format($ps['total_orders']) ?> routed orders</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-mono font-bold text-indigo-400">₹<?= number_format((float)$ps['total_cost'], 2) ?></div>
                                <div class="text-[10px] text-slate-500">Provider API Cost</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
