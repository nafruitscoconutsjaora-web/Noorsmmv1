<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Orders History</h1>
            <p class="text-xs text-slate-400 mt-1">Track real-time progress, counters, and status updates for your campaigns.</p>
        </div>
        <a href="/orders/new" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 transition">
            + New Order
        </a>
    </div>

    <!-- Status Tabs & Search Filter -->
    <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex flex-col md:flex-row gap-4 items-center justify-between">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 text-xs">
            <?php
            $tabs = [
                'all' => 'All Orders',
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'partial' => 'Partial',
                'cancelled' => 'Cancelled / Refunded',
            ];
            ?>
            <?php foreach ($tabs as $k => $label): ?>
                <a href="/orders?status=<?= $k ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                    class="px-3 py-1.5 rounded-lg font-semibold transition whitespace-nowrap <?= $current_status === $k ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-750' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search Form -->
        <form action="/orders" method="GET" class="w-full md:w-72 flex items-center gap-2">
            <input type="hidden" name="status" value="<?= e($current_status) ?>">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search Order ID or Link..."
                class="w-full px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-medium transition">
                Search
            </button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Order ID</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Service</th>
                        <th class="py-3.5 px-4">Link</th>
                        <th class="py-3.5 px-4">Qty</th>
                        <th class="py-3.5 px-4">Charge</th>
                        <th class="py-3.5 px-4">Start Count</th>
                        <th class="py-3.5 px-4">Remains</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($orders['items'])): ?>
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-500">
                                No orders found matching the filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders['items'] as $ord): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-400">#<?= $ord['id'] ?></td>
                                <td class="py-3.5 px-4 text-slate-400 whitespace-nowrap"><?= date('M d, H:i', strtotime($ord['created_at'])) ?></td>
                                <td class="py-3.5 px-4 font-semibold text-white max-w-xs truncate">
                                    <?= e($ord['service_name'] ?? 'Service #' . $ord['service_id']) ?>
                                </td>
                                <td class="py-3.5 px-4 max-w-xs truncate text-slate-400">
                                    <a href="<?= e($ord['link']) ?>" target="_blank" class="hover:text-indigo-400 hover:underline"><?= e($ord['link']) ?></a>
                                </td>
                                <td class="py-3.5 px-4 font-mono"><?= number_format((int)$ord['quantity']) ?></td>
                                <td class="py-3.5 px-4 font-bold text-emerald-400 font-mono">₹<?= number_format((float)$ord['charge'], 2) ?></td>
                                <td class="py-3.5 px-4 font-mono text-slate-400"><?= $ord['start_counter'] ?? '0' ?></td>
                                <td class="py-3.5 px-4 font-mono text-slate-400"><?= $ord['remains'] ?? '0' ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= order_status_badge($ord['status']) ?>">
                                        <?= e(str_replace('_', ' ', $ord['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <?php if ($ord['status'] === 'pending'): ?>
                                        <form action="/orders/<?= $ord['id'] ?>/cancel" method="POST" onsubmit="return confirm('Cancel this pending order and refund charge to wallet?');" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="px-2.5 py-1 rounded bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-semibold transition">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-[11px]">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($orders['last_page'] > 1): ?>
            <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <div>Page <?= $orders['page'] ?> of <?= $orders['last_page'] ?> (<?= $orders['total'] ?> total orders)</div>
                <div class="flex items-center gap-2">
                    <?php if ($orders['page'] > 1): ?>
                        <a href="/orders?page=<?= $orders['page'] - 1 ?>&status=<?= e($current_status) ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Prev</a>
                    <?php endif; ?>
                    <?php if ($orders['page'] < $orders['last_page']): ?>
                        <a href="/orders?page=<?= $orders['page'] + 1 ?>&status=<?= e($current_status) ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
