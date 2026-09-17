<div class="space-y-6 max-w-6xl mx-auto" id="wallet-activity-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/wallet" class="hover:text-white transition">&larr; Wallet & Add Funds</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Ledger & Audit History</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Wallet Transactions & Activity</h1>
            <p class="text-sm text-slate-400 mt-1">Full statement of credits, order debits, deposit gateway logs, and refunds.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/reports/export/wallet" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 border border-slate-700 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download Statement (CSV)
            </a>
            <a href="/wallet" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white transition shadow-sm">
                + Add Funds
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="/wallet/activity" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-wrap items-end gap-3 shadow-sm">
        <div class="flex-1 min-w-[130px]">
            <label class="block text-xs font-medium text-slate-400 mb-1">Type</label>
            <select name="type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Transactions</option>
                <option value="credit" <?= ($filters['type'] === 'credit') ? 'selected' : '' ?>>Credits (Deposits & Refunds)</option>
                <option value="debit" <?= ($filters['type'] === 'debit') ? 'selected' : '' ?>>Debits (Orders)</option>
                <option value="refund" <?= ($filters['type'] === 'refund') ? 'selected' : '' ?>>Refunds Only</option>
            </select>
        </div>
        <div class="flex-1 min-w-[130px]">
            <label class="block text-xs font-medium text-slate-400 mb-1">From Date</label>
            <input type="date" name="start_date" value="<?= e($filters['start_date'] ?? '') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
        </div>
        <div class="flex-1 min-w-[130px]">
            <label class="block text-xs font-medium text-slate-400 mb-1">To Date</label>
            <input type="date" name="end_date" value="<?= e($filters['end_date'] ?? '') ?>" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition">
                Filter
            </button>
            <a href="/wallet/activity" class="px-3 py-2 text-xs text-slate-400 hover:text-white transition">Clear</a>
        </div>
    </form>

    <!-- Ledger Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white">Wallet Transaction Journal</h2>
            <span class="text-xs text-slate-400">Showing <?= count($transactions) ?> of <?= $total ?> total</span>
        </div>

        <?php if (empty($transactions)): ?>
            <div class="p-12 text-center text-slate-500 text-sm">
                No ledger transactions found matching the selected filters.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 font-semibold bg-slate-950/40">
                            <th class="py-3 px-4">Tx ID</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Description / Reference</th>
                            <th class="py-3 px-4 text-right">Amount</th>
                            <th class="py-3 px-4 text-right">Balance Before</th>
                            <th class="py-3 px-4 text-right">Balance After</th>
                            <th class="py-3 px-4 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($transactions as $t): ?>
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-400">#<?= $t['id'] ?></td>
                                <td class="py-3.5 px-4">
                                    <?php if ($t['type'] === 'credit'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">CREDIT</span>
                                    <?php elseif ($t['type'] === 'debit'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/20">DEBIT</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20"><?= strtoupper(e($t['type'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-white font-medium"><?= e($t['description']) ?></div>
                                    <?php if (!empty($t['reference_id'])): ?>
                                        <div class="text-[10px] font-mono text-slate-500 mt-0.5">Ref: <?= e($t['reference_id']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold <?= $t['type'] === 'credit' ? 'text-emerald-400' : 'text-slate-200' ?>">
                                    <?= $t['type'] === 'credit' ? '+' : '-' ?>₹<?= number_format((float)$t['amount'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-400">
                                    ₹<?= number_format((float)$t['balance_before'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-white">
                                    ₹<?= number_format((float)$t['balance_after'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-400 font-mono text-[11px]">
                                    <?= substr($t['created_at'], 0, 16) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Page <?= $page ?> of <?= $total_pages ?></span>
                    <div class="flex items-center gap-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&type=<?= urlencode($filters['type'] ?? '') ?>&start_date=<?= urlencode($filters['start_date'] ?? '') ?>&end_date=<?= urlencode($filters['end_date'] ?? '') ?>" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white transition">&larr; Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&type=<?= urlencode($filters['type'] ?? '') ?>&start_date=<?= urlencode($filters['start_date'] ?? '') ?>&end_date=<?= urlencode($filters['end_date'] ?? '') ?>" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white transition">Next &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
