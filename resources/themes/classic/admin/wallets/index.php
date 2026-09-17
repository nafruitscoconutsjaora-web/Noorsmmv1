<?php
$stats = $stats ?? [
    'total_user_balance' => 0.0,
    'total_users' => 0,
    'avg_balance' => 0.0,
];
$transactions = $transactions ?? [];
$users = $users ?? [];
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
?>
<div class="space-y-6 max-w-6xl mx-auto" id="admin-wallet-management-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Wallet Management</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Wallet & Funds Administration</h1>
            <p class="text-sm text-slate-400 mt-1">Platform liquidity totals, manual user credit/debit adjustments, and audit ledger.</p>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Total System User Balance</span>
            <div class="text-2xl font-extrabold text-emerald-400 mt-1">₹<?= number_format((float)$stats['total_user_balance'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">Circulating user wallet liabilities</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Total User Accounts</span>
            <div class="text-2xl font-extrabold text-white mt-1"><?= number_format($stats['total_users']) ?></div>
            <div class="text-xs text-slate-500 mt-2">Registered member wallets</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Average Balance Per User</span>
            <div class="text-2xl font-extrabold text-indigo-400 mt-1">₹<?= number_format((float)$stats['avg_balance'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">Active account average</div>
        </div>
    </div>

    <!-- Manual Adjustment Form & Ledger -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Adjustment Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 h-fit shadow-sm">
            <h2 class="text-base font-bold text-white mb-2">Adjust User Balance</h2>
            <p class="text-xs text-slate-400 mb-5">Credit or debit a user's wallet. All adjustments are permanently audited.</p>

            <form action="/admin/wallets/adjust" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Select User</label>
                    <select name="user_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Choose User...</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= e($u['username']) ?> (₹<?= number_format((float)$u['balance'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Action</label>
                        <select name="type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                            <option value="credit">Credit (+)</option>
                            <option value="debit">Debit (-)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Amount (₹)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="100.00" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Reason / Note</label>
                    <input type="text" name="reason" required placeholder="e.g. Bank deposit verification, Bonus, Compensation" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm mt-2">
                    Execute Adjustment
                </button>
            </form>
        </div>

        <!-- System-wide Ledger -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-bold text-white">System Wallet Audit Trail</h2>
                <span class="text-xs text-slate-400">Last 50 events</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Reason / Desc</th>
                            <th class="py-3 px-4 text-right">Amount</th>
                            <th class="py-3 px-4 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td class="py-2.5 px-4 font-semibold text-white"><?= e($t['username']) ?></td>
                                <td class="py-2.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $t['type'] === 'credit' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <?= strtoupper(e($t['type'])) ?>
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-slate-300 max-w-xs truncate"><?= e($t['description']) ?></td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold <?= $t['type'] === 'credit' ? 'text-emerald-400' : 'text-slate-200' ?>">
                                    <?= $t['type'] === 'credit' ? '+' : '-' ?>₹<?= number_format((float)$t['amount'], 2) ?>
                                </td>
                                <td class="py-2.5 px-4 text-right text-slate-400 font-mono text-[11px]"><?= substr($t['created_at'], 0, 16) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
