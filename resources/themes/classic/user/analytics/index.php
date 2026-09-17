<div class="space-y-6 max-w-7xl mx-auto" id="user-analytics-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Order Analytics & Insights</h1>
            <p class="text-sm text-slate-400 mt-1">Real-time expenditure, delivery completion rates, and service breakdown from live orders.</p>
        </div>
        <a href="/reports/export" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 border border-slate-700 transition">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Export CSV
        </a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="/orders/analytics" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-wrap items-end gap-4 shadow-sm">
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Start Date</label>
            <input type="date" name="start_date" value="<?= e($filters['start_date'] ?? '') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
        </div>
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-medium text-slate-400 mb-1.5">End Date</label>
            <input type="date" name="end_date" value="<?= e($filters['end_date'] ?? '') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
        </div>
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Filter by Service</label>
            <select name="service_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Services</option>
                <?php foreach ($user_services as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($filters['service_id'] == $s['id']) ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Status</label>
            <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="all">All Statuses</option>
                <option value="completed" <?= ($filters['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                <option value="pending" <?= ($filters['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= ($filters['status'] === 'processing') ? 'selected' : '' ?>>Processing</option>
                <option value="cancelled" <?= ($filters['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                Apply Filters
            </button>
            <a href="/orders/analytics" class="px-3 py-2.5 text-xs text-slate-400 hover:text-white transition">Reset</a>
        </div>
    </form>

    <!-- Stat Highlights -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-xs text-slate-400">Total Orders</span>
            <div class="text-2xl font-bold text-white mt-1"><?= number_format((int)$summary['total_orders']) ?></div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-xs text-slate-400">Total Spent</span>
            <div class="text-2xl font-bold text-emerald-400 mt-1">₹<?= number_format((float)$summary['total_spending'], 2) ?></div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-xs text-emerald-400 font-medium">Completed</span>
            <div class="text-2xl font-bold text-emerald-400 mt-1"><?= number_format((int)$summary['completed_orders']) ?></div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-xs text-amber-400 font-medium">Processing</span>
            <div class="text-2xl font-bold text-amber-400 mt-1"><?= number_format((int)$summary['processing_orders']) ?></div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-xs text-sky-400 font-medium">Pending</span>
            <div class="text-2xl font-bold text-sky-400 mt-1"><?= number_format((int)$summary['pending_orders']) ?></div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <span class="text-xs text-rose-400 font-medium">Cancelled / Failed</span>
            <div class="text-2xl font-bold text-rose-400 mt-1"><?= number_format((int)$summary['cancelled_orders']) ?></div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Spending Canvas Bar Chart -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4">Monthly Spending Overview (₹)</h3>
            <?php if (empty($monthly)): ?>
                <div class="h-56 flex items-center justify-center text-slate-500 text-xs">No expenditure history recorded yet.</div>
            <?php else: ?>
                <div class="h-56 flex items-end justify-between gap-3 pt-6 px-2">
                    <?php 
                        $maxSpend = 1;
                        foreach ($monthly as $m) {
                            if ((float)$m['monthly_spent'] > $maxSpend) $maxSpend = (float)$m['monthly_spent'];
                        }
                    ?>
                    <?php foreach ($monthly as $m): 
                        $pct = round(((float)$m['monthly_spent'] / $maxSpend) * 100);
                    ?>
                        <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end group">
                            <div class="text-[10px] font-mono text-indigo-400 opacity-0 group-hover:opacity-100 transition">₹<?= number_format((float)$m['monthly_spent'], 0) ?></div>
                            <div class="w-full max-w-[42px] bg-indigo-600/80 hover:bg-indigo-500 rounded-t-lg transition-all" style="height: <?= max(8, $pct) ?>%"></div>
                            <span class="text-[10px] text-slate-400 whitespace-nowrap"><?= e($m['month_label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 14-Day Activity Sparkline / Bars -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4">Daily Orders Trend (Past 14 Days)</h3>
            <?php if (empty($daily)): ?>
                <div class="h-56 flex items-center justify-center text-slate-500 text-xs">No orders recorded in the past 14 days.</div>
            <?php else: ?>
                <div class="h-56 flex items-end justify-between gap-2 pt-6 px-2">
                    <?php 
                        $maxOrders = 1;
                        foreach ($daily as $d) {
                            if ((int)$d['count'] > $maxOrders) $maxOrders = (int)$d['count'];
                        }
                    ?>
                    <?php foreach ($daily as $d): 
                        $pct = round(((int)$d['count'] / $maxOrders) * 100);
                    ?>
                        <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end group">
                            <div class="text-[10px] font-mono text-emerald-400 opacity-0 group-hover:opacity-100 transition"><?= $d['count'] ?></div>
                            <div class="w-full bg-emerald-600/70 hover:bg-emerald-500 rounded-t-lg transition-all" style="height: <?= max(8, $pct) ?>%"></div>
                            <span class="text-[9px] text-slate-500 transform -rotate-45 origin-top-left mt-1"><?= substr($d['order_date'], 5) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Service-wise Distribution -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
        <h3 class="text-sm font-bold text-white mb-4">Most Utilized Services</h3>
        <?php if (empty($services_stats)): ?>
            <div class="p-8 text-center text-slate-500 text-sm">No service utilization data found matching criteria.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 font-semibold">
                            <th class="pb-3 px-3">Service Name</th>
                            <th class="pb-3 px-3 text-right">Orders Placed</th>
                            <th class="pb-3 px-3 text-right">Total Invested</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($services_stats as $st): ?>
                            <tr>
                                <td class="py-3 px-3 font-medium text-white"><?= e($st['service_name']) ?></td>
                                <td class="py-3 px-3 text-right font-mono"><?= number_format($st['orders_count']) ?></td>
                                <td class="py-3 px-3 text-right font-bold text-emerald-400">₹<?= number_format((float)$st['total_spent'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
