<?php
$stats = $stats ?? [
    'total_referrals' => count($referrals ?? []),
    'total_commission_paid' => 0,
    'commission_rate' => $commission_rate ?? 5.0,
];
?>
<div class="space-y-6 max-w-6xl mx-auto" id="admin-referral-management">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Affiliate Network</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Affiliate & Referral Program Management</h1>
            <p class="text-sm text-slate-400 mt-1">Review user affiliate referrals, commissions earned, and approve commission payouts.</p>
        </div>
    </div>

    <!-- Referral Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Total Referred Users</span>
            <div class="text-2xl font-extrabold text-white mt-1"><?= number_format($stats['total_referrals']) ?></div>
            <div class="text-xs text-slate-500 mt-2">Active tracked invitation links</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Total Commissions Generated</span>
            <div class="text-2xl font-extrabold text-emerald-400 mt-1">₹<?= number_format((float)$stats['total_commission_paid'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">Paid out to affiliates</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <span class="text-xs font-medium text-slate-400">Standard Affiliate Commission</span>
            <div class="text-2xl font-extrabold text-indigo-400 mt-1"><?= e($stats['commission_rate']) ?>%</div>
            <div class="text-xs text-slate-500 mt-2">Per referred customer deposit</div>
        </div>
    </div>

    <!-- Referrals Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Referral Link Tracking</h2>
            <span class="text-xs text-slate-400">Last 50 links</span>
        </div>

        <?php if (empty($referrals)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">No referrals logged yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Affiliate Referrer</th>
                            <th class="py-3 px-4">Referred Member</th>
                            <th class="py-3 px-4 text-right">Commission Earned</th>
                            <th class="py-3 px-4 text-right">Registration Date</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($referrals as $r): ?>
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white"><?= e($r['referrer_username']) ?></td>
                                <td class="py-3 px-4 font-semibold text-indigo-400"><?= e($r['referred_username']) ?></td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">₹<?= number_format((float)$r['commission_amount'], 2) ?></td>
                                <td class="py-3 px-4 text-right font-mono text-slate-400 text-[11px]"><?= substr($r['created_at'], 0, 16) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $r['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' ?>">
                                        <?= strtoupper(e($r['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
