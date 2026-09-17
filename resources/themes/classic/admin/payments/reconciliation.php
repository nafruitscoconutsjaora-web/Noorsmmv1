<div class="space-y-6 max-w-6xl mx-auto" id="admin-reconciliation-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin/payments" class="hover:text-white transition">&larr; Payments</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Payment Reconciliation</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Payment Reconciliation & Gateway Ledger</h1>
            <p class="text-sm text-slate-400 mt-1">Audit unmatched gateway transactions, reconcile manual bank deposits, and resolve discrepancies.</p>
        </div>
    </div>

    <!-- Payments Ledger Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Deposit Transactions</h2>
            <span class="text-xs text-slate-400">Total records: <?= count($payments) ?></span>
        </div>

        <?php if (empty($payments)): ?>
            <div class="p-12 text-center text-slate-500 text-sm">No payment records found.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Payment ID</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Gateway</th>
                            <th class="py-3 px-4">Transaction / UTR</th>
                            <th class="py-3 px-4 text-right">Amount</th>
                            <th class="py-3 px-4 text-right">Net Received</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-400">#<?= $p['id'] ?></td>
                                <td class="py-3 px-4 font-semibold text-white">
                                    <a href="/admin/users/<?= $p['user_id'] ?>" class="hover:text-indigo-400 transition">
                                        <?= e($p['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4 uppercase text-[10px] font-bold text-slate-300"><?= e($p['gateway'] ?? $p['method'] ?? 'N/A') ?></td>
                                <td class="py-3 px-4 font-mono text-slate-400 truncate max-w-xs" title="<?= e($p['transaction_id'] ?? $p['payment_id'] ?? '') ?>">
                                    <?= e($p['transaction_id'] ?? $p['payment_id'] ?? $p['order_id'] ?? 'Pending / None') ?>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-400 font-mono">₹<?= number_format((float)$p['amount'], 2) ?></td>
                                <td class="py-3 px-4 text-right font-mono text-slate-400">₹<?= number_format((float)($p['net_amount'] ?? $p['amount']), 2) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $p['status'] === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($p['status'] === 'failed' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') ?>">
                                        <?= strtoupper(e($p['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <?php if ($p['status'] === 'pending'): ?>
                                        <form action="/admin/payments/<?= $p['id'] ?>/reconcile" method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" onclick="return confirm('Reconcile and instantly credit ₹<?= number_format((float)$p['amount'], 2) ?> to <?= e($p['username']) ?>?')" class="px-2.5 py-1 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white rounded-lg text-xs font-semibold transition border border-emerald-500/30">
                                                Confirm Credit
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-[11px]">Reconciled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
