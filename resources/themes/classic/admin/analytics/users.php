<div class="space-y-6 max-w-7xl mx-auto" id="admin-user-analytics">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin/analytics" class="hover:text-white transition">&larr; Overview Analytics</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">User Cohorts & Retention</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">User Behavior & Cohort Analytics</h1>
            <p class="text-sm text-slate-400 mt-1">Active customer engagement, repeat ordering frequency, and customer lifetime value (CLV).</p>
        </div>
    </div>

    <!-- User Cohort Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Active Purchasing Customers</span>
            <div class="text-2xl font-extrabold text-white mt-1"><?= number_format($cohorts['paying_users_count']) ?></div>
            <div class="text-xs text-slate-500 mt-2">Users who placed ≥ 1 order</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Average Customer Lifetime Value</span>
            <div class="text-2xl font-extrabold text-emerald-400 mt-1">₹<?= number_format((float)$cohorts['avg_clv'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">Mean spend per purchasing user</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Repeat Buyer Rate</span>
            <div class="text-2xl font-extrabold text-indigo-400 mt-1"><?= number_format((float)$cohorts['repeat_buyer_rate'], 1) ?>%</div>
            <div class="text-xs text-slate-500 mt-2">Users with 2+ completed orders</div>
        </div>
    </div>

    <!-- Top Spenders / Whales Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Top 25 High-Value Customers (Whales)</h2>
            <span class="text-xs text-slate-400">Sorted by lifetime spend</span>
        </div>

        <?php if (empty($top_users)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">No user spend data recorded yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4 text-center">Total Orders</th>
                            <th class="py-3 px-4 text-right">Current Balance</th>
                            <th class="py-3 px-4 text-right">Lifetime Spend</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($top_users as $u): ?>
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white">
                                    <a href="/admin/users/<?= $u['id'] ?>" class="hover:text-indigo-400 transition">
                                        <?= e($u['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-400"><?= e($u['email']) ?></td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-200"><?= number_format($u['orders_count']) ?></td>
                                <td class="py-3 px-4 text-right font-mono text-slate-300">₹<?= number_format((float)$u['balance'], 2) ?></td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">₹<?= number_format((float)$u['total_spent'], 2) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <a href="/admin/users/<?= $u['id'] ?>" class="text-indigo-400 hover:underline">View Profile &rarr;</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
