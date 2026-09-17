<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="/admin/users" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-2">
            &larr; Back to Users
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">User: <?= e($user['username']) ?></h1>
                <p class="text-xs text-slate-400 mt-1"><?= e($user['email']) ?> &bull; Joined <?= date('F d, Y', strtotime($user['created_at'])) ?></p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $user['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                    <?= ucfirst($user['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- User Profile and Balance Adjustment Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Account Overview Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white pb-3 border-b border-slate-800">Account Credentials & Status</h3>

            <div class="space-y-2.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">User ID:</span>
                    <span class="font-mono text-white">#<?= $user['id'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Current Balance:</span>
                    <span class="font-mono font-extrabold text-emerald-400 text-sm">₹<?= number_format((float)$user['balance'], 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Role:</span>
                    <span class="font-semibold text-white capitalize"><?= e($user['role'] ?? 'Client') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Custom Discount:</span>
                    <span class="font-mono text-slate-300"><?= (float)($user['custom_rate'] ?? 0) ?>%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Reseller API Key:</span>
                    <span class="font-mono text-[11px] text-indigo-400 truncate max-w-[200px]"><?= e($user['api_key'] ?? 'None') ?></span>
                </div>
            </div>

            <!-- Toggle Status Form -->
            <form action="/admin/users/<?= $user['id'] ?>/status" method="POST" class="pt-4 border-t border-slate-800 flex items-center justify-between">
                <?= csrf_field() ?>
                <span class="text-xs text-slate-400">Access Control:</span>
                <input type="hidden" name="status" value="<?= $user['status'] === 'active' ? 'suspended' : 'active' ?>">
                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= $user['status'] === 'active' ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20' ?>">
                    <?= $user['status'] === 'active' ? 'Suspend Account' : 'Reactivate Account' ?>
                </button>
            </form>
        </div>

        <!-- Manual Balance Adjustment Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white pb-3 border-b border-slate-800">Adjust Wallet Balance</h3>

            <form action="/admin/users/<?= $user['id'] ?>/balance" method="POST" class="space-y-3">
                <?= csrf_field() ?>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Action Type</label>
                        <select name="type" required class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                            <option value="credit">Credit (+) Add Funds</option>
                            <option value="debit">Debit (-) Deduct Funds</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Amount (₹)</label>
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="100.00"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-750 text-emerald-400 font-bold font-mono text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Reason / Reference Note *</label>
                    <input type="text" name="note" required placeholder="e.g. Bank transfer reference #8392 or refund adjustment"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition">
                    Apply Balance Adjustment
                </button>
            </form>
        </div>
    </div>

    <!-- Recent Transactions for this user -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white">Wallet Ledger History</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Txn ID</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Amount</th>
                        <th class="py-3 px-4">Post-Balance</th>
                        <th class="py-3 px-4">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($transactions['items'])): ?>
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">No transactions recorded for this user.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions['items'] as $tx): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 font-mono font-bold text-rose-400">#<?= $tx['id'] ?></td>
                                <td class="py-3 px-4 text-slate-400 whitespace-nowrap"><?= date('M d, H:i', strtotime($tx['created_at'])) ?></td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold <?= $tx['type'] === 'credit' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                                        <?= strtoupper($tx['type']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold <?= $tx['type'] === 'credit' ? 'text-emerald-400' : 'text-rose-400' ?>">
                                    <?= $tx['type'] === 'credit' ? '+' : '-' ?>₹<?= number_format((float)$tx['amount'], 2) ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-300">₹<?= number_format((float)$tx['balance_after'], 2) ?></td>
                                <td class="py-3 px-4 text-slate-400 max-w-[220px] truncate"><?= e($tx['description'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
