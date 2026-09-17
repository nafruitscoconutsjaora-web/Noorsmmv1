<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Payment Gateways & Ledger</h1>
            <p class="text-xs text-slate-400 mt-1">Audit incoming deposit transactions via Razorpay, test deposits, and manual gateways.</p>
        </div>

        <form action="/admin/payments" method="GET" class="w-full sm:w-72 flex items-center gap-2">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search Order ID, Payment ID..."
                class="w-full px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-white text-xs font-medium transition">
                Search
            </button>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">ID</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">User</th>
                        <th class="py-3.5 px-4">Gateway</th>
                        <th class="py-3.5 px-4">Gateway Order ID</th>
                        <th class="py-3.5 px-4">Payment / Txn ID</th>
                        <th class="py-3.5 px-4">Amount</th>
                        <th class="py-3.5 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($payments['items'])): ?>
                        <tr><td colspan="8" class="py-12 text-center text-slate-500">No payment logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments['items'] as $pay): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-rose-400">#<?= $pay['id'] ?></td>
                                <td class="py-3.5 px-4 text-slate-400 whitespace-nowrap"><?= date('M d, H:i', strtotime($pay['created_at'])) ?></td>
                                <td class="py-3.5 px-4">
                                    <a href="/admin/users/<?= $pay['user_id'] ?>" class="font-semibold text-white hover:underline">
                                        <?= e($pay['username'] ?? 'User #' . $pay['user_id']) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-300 uppercase font-semibold text-[11px]"><?= e($pay['gateway']) ?></td>
                                <td class="py-3.5 px-4 font-mono text-slate-400 max-w-[150px] truncate"><?= e($pay['gateway_order_id'] ?? '-') ?></td>
                                <td class="py-3.5 px-4 font-mono text-indigo-400 max-w-[150px] truncate"><?= e($pay['transaction_id'] ?? '-') ?></td>
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-400">
                                    <?= e($pay['currency'] ?? 'INR') ?> <?= number_format((float)$pay['amount'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($pay['status'] === 'completed'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Completed</span>
                                    <?php elseif ($pay['status'] === 'pending'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">Pending</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20"><?= e($pay['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($payments['last_page'] > 1): ?>
            <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <div>Page <?= $payments['page'] ?> of <?= $payments['last_page'] ?> (<?= $payments['total'] ?> total)</div>
                <div class="flex items-center gap-2">
                    <?php if ($payments['page'] > 1): ?>
                        <a href="/admin/payments?page=<?= $payments['page'] - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Prev</a>
                    <?php endif; ?>
                    <?php if ($payments['page'] < $payments['last_page']): ?>
                        <a href="/admin/payments?page=<?= $payments['page'] + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
