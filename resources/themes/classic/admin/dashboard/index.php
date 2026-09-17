<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Executive Operations Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Platform-wide overview of order volume, revenue metrics, provider links, and system audits.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/services/new" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
                + Add Service
            </a>
            <a href="/admin/orders" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-md shadow-rose-600/20 transition">
                Manage Orders
            </a>
        </div>
    </div>

    <!-- Stat Counters Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Resellers</div>
            <div class="text-3xl font-extrabold text-white mt-2"><?= number_format((int)$total_users) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Registered clients & API keys</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
            <div class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Catalog Services</div>
            <div class="text-3xl font-extrabold text-indigo-400 mt-2"><?= number_format((int)$total_services) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Active upstream & direct items</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
            <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Gross Platform Volume</div>
            <div class="text-3xl font-extrabold text-emerald-400 font-mono mt-2">₹<?= number_format((float)($stats['total_spent'] ?? 0), 2) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Lifetime completed charges</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
            <div class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Active Pipeline</div>
            <div class="text-3xl font-extrabold text-amber-400 mt-2"><?= number_format((int)($stats['in_progress'] ?? 0) + (int)($stats['pending'] ?? 0)) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Orders in queue or executing</div>
        </div>
    </div>

    <!-- Order Status Summary Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
        <div class="p-3 bg-slate-900/70 border border-slate-800 rounded-xl text-center">
            <div class="text-xs text-slate-400">Total</div>
            <div class="text-lg font-bold text-white mt-0.5"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
        </div>
        <div class="p-3 bg-slate-900/70 border border-slate-800 rounded-xl text-center">
            <div class="text-xs text-amber-400">Pending</div>
            <div class="text-lg font-bold text-amber-400 mt-0.5"><?= number_format((int)($stats['pending'] ?? 0)) ?></div>
        </div>
        <div class="p-3 bg-slate-900/70 border border-slate-800 rounded-xl text-center">
            <div class="text-xs text-blue-400">In Progress</div>
            <div class="text-lg font-bold text-blue-400 mt-0.5"><?= number_format((int)($stats['in_progress'] ?? 0)) ?></div>
        </div>
        <div class="p-3 bg-slate-900/70 border border-slate-800 rounded-xl text-center">
            <div class="text-xs text-emerald-400">Completed</div>
            <div class="text-lg font-bold text-emerald-400 mt-0.5"><?= number_format((int)($stats['completed'] ?? 0)) ?></div>
        </div>
        <div class="p-3 bg-slate-900/70 border border-slate-800 rounded-xl text-center">
            <div class="text-xs text-purple-400">Partial</div>
            <div class="text-lg font-bold text-purple-400 mt-0.5"><?= number_format((int)($stats['partial'] ?? 0)) ?></div>
        </div>
        <div class="p-3 bg-slate-900/70 border border-slate-800 rounded-xl text-center">
            <div class="text-xs text-rose-400">Cancelled / Ref</div>
            <div class="text-lg font-bold text-rose-400 mt-0.5"><?= number_format((int)($stats['cancelled'] ?? 0) + (int)($stats['refunded'] ?? 0)) ?></div>
        </div>
    </div>

    <!-- Active Upstream Providers -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
            <div>
                <h3 class="text-base font-bold text-white">Upstream API Providers</h3>
                <p class="text-xs text-slate-400 mt-0.5">Automated wholesale suppliers connected via standard API v2</p>
            </div>
            <a href="/admin/providers" class="text-xs font-semibold text-rose-400 hover:text-rose-300 transition">
                Manage Providers &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php if (empty($providers)): ?>
                <div class="col-span-3 text-xs text-slate-500 py-4 text-center">No providers configured yet.</div>
            <?php else: ?>
                <?php foreach ($providers as $p): ?>
                    <div class="p-4 bg-slate-950/70 border border-slate-800 rounded-xl flex items-center justify-between">
                        <div>
                            <div class="font-bold text-white text-sm"><?= e($p['name']) ?></div>
                            <div class="text-[11px] text-slate-400 mt-0.5 font-mono truncate max-w-[180px]"><?= e($p['api_url']) ?></div>
                            <div class="text-[11px] font-mono mt-1 text-emerald-400">
                                Balance: <?= e($p['currency']) ?> <?= number_format((float)($p['balance'] ?? 0), 2) ?>
                            </div>
                        </div>
                        <div class="text-right flex flex-col items-end gap-2">
                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold <?= $p['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                            <form action="/admin/providers/<?= $p['id'] ?>/test" method="POST">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-[11px] text-slate-400 hover:text-white underline">
                                    Check Ping
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders Table & Audit Trail Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders (2 Cols) -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-base font-bold text-white">Recent Orders</h3>
                <a href="/admin/orders" class="text-xs font-semibold text-rose-400 hover:text-rose-300 transition">
                    All Orders &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Order ID</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Charge</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="6" class="py-6 text-center text-slate-500">No orders placed yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $ord): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-rose-400">#<?= $ord['id'] ?></td>
                                    <td class="py-3 px-4 text-slate-200"><?= e($ord['username'] ?? 'User #' . $ord['user_id']) ?></td>
                                    <td class="py-3 px-4 font-semibold text-white max-w-[150px] truncate"><?= e($ord['service_name'] ?? 'Service #' . $ord['service_id']) ?></td>
                                    <td class="py-3 px-4 font-bold text-emerald-400 font-mono">₹<?= number_format((float)$ord['charge'], 2) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= order_status_badge($ord['status']) ?>">
                                            <?= e(str_replace('_', ' ', $ord['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="/admin/orders/<?= $ord['id'] ?>" class="text-rose-400 hover:text-rose-300 font-medium">Inspect &rarr;</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Audit Trail (1 Col) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <h3 class="text-base font-bold text-white pb-3 border-b border-slate-800 mb-4">Security & Staff Audit</h3>
            <div class="space-y-3">
                <?php if (empty($recent_audit)): ?>
                    <div class="text-xs text-slate-500 py-4 text-center">No audit logs recorded yet.</div>
                <?php else: ?>
                    <?php foreach ($recent_audit as $log): ?>
                        <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 text-xs">
                            <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1">
                                <span class="font-bold text-rose-400"><?= e($log['admin_username'] ?? 'Admin') ?></span>
                                <span><?= date('M d, H:i', strtotime($log['created_at'])) ?></span>
                            </div>
                            <div class="text-slate-200 font-medium font-mono text-[11px]"><?= e($log['action']) ?></div>
                            <div class="text-slate-500 text-[10px] mt-0.5">Target: <?= e($log['target_type'] ?? 'sys') ?> #<?= $log['target_id'] ?? '-' ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
