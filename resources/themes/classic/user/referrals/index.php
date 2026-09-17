<div class="space-y-6 max-w-5xl mx-auto" id="referrals-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Affiliate & Referral Program</h1>
            <p class="text-sm text-slate-400 mt-1">Invite friends and earn <?= $commission_rate ?>% lifetime commission on every deposit they make.</p>
        </div>
    </div>

    <!-- Referral Link Card -->
    <div class="bg-gradient-to-r from-indigo-900/50 to-slate-900 border border-indigo-500/30 rounded-2xl p-6 shadow-sm">
        <div class="max-w-2xl">
            <h2 class="text-base font-bold text-white mb-2">Your Unique Referral Link</h2>
            <p class="text-xs text-slate-300 mb-4">Share this link across social media, forums, or your website to earn instant commissions.</p>
            <div class="flex items-center gap-2">
                <input id="ref-link-input" type="text" readonly value="<?= e($referral_link) ?>" class="flex-1 bg-slate-950/80 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-indigo-300 font-mono focus:outline-none select-all">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('ref-link-input').value); alert('Referral link copied to clipboard!');" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                    Copy Link
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <span class="text-xs font-medium text-slate-400">Total Referrals</span>
            <div class="text-2xl font-bold text-white mt-1"><?= number_format($stats['total_referred']) ?></div>
            <div class="text-xs text-slate-500 mt-2">Users registered via link</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <span class="text-xs font-medium text-slate-400">Total Commission Earned</span>
            <div class="text-2xl font-bold text-emerald-400 mt-1">₹<?= number_format((float)$stats['total_earned'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">Cumulative earnings</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <span class="text-xs font-medium text-slate-400">Available Payout Balance</span>
            <div class="text-2xl font-bold text-indigo-400 mt-1">₹<?= number_format((float)$stats['available_balance'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">Ready to withdraw</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <span class="text-xs font-medium text-slate-400">Commission Rate</span>
            <div class="text-2xl font-bold text-amber-400 mt-1"><?= $commission_rate ?>%</div>
            <div class="text-xs text-slate-500 mt-2">Per user deposit</div>
        </div>
    </div>

    <!-- Payout Request & History -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Request Withdrawal -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 h-fit">
            <h3 class="text-sm font-bold text-white mb-2">Request Payout</h3>
            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                Withdraw your commission into your account wallet balance or external method. Minimum ₹100.00.
            </p>
            <form action="/referrals/payout" method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Amount (₹)</label>
                    <input type="number" step="0.01" min="100" max="<?= (float)$stats['available_balance'] ?>" name="amount" required placeholder="100.00" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Payment Method</label>
                    <select name="payout_method" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="wallet">Transfer to Panel Wallet Balance</option>
                        <option value="upi">UPI / GPay / Paytm</option>
                        <option value="bank">Bank Wire</option>
                        <option value="crypto">USDT (TRC20)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Account Details / UPI ID</label>
                    <input type="text" name="payout_details" placeholder="e.g. user@okhdfcbank or Wallet" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
                <button type="submit" <?= (float)$stats['available_balance'] < 100 ? 'disabled' : '' ?> class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold rounded-xl transition shadow-sm mt-2">
                    Submit Payout Request
                </button>
            </form>
        </div>

        <!-- Referred Users & Past Payouts Table -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Referred Users List -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h3 class="text-sm font-bold text-white mb-4">Referred Users (<?= count($referrals) ?>)</h3>
                <?php if (empty($referrals)): ?>
                    <div class="p-6 text-center text-slate-500 text-xs">No users have registered with your referral link yet.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-800 text-slate-400">
                                    <th class="pb-2 px-3">Username</th>
                                    <th class="pb-2 px-3">Joined Date</th>
                                    <th class="pb-2 px-3 text-right">Commission Earned</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                                <?php foreach ($referrals as $r): ?>
                                    <tr>
                                        <td class="py-2.5 px-3 font-semibold text-white"><?= e($r['username']) ?></td>
                                        <td class="py-2.5 px-3 text-slate-400 font-mono text-[11px]"><?= substr($r['created_at'], 0, 10) ?></td>
                                        <td class="py-2.5 px-3 text-right font-bold text-emerald-400">₹<?= number_format((float)$r['commission_earned'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payout History -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h3 class="text-sm font-bold text-white mb-4">Payout Requests History</h3>
                <?php if (empty($payouts)): ?>
                    <div class="p-6 text-center text-slate-500 text-xs">No payout requests submitted yet.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-800 text-slate-400">
                                    <th class="pb-2 px-3">Req ID</th>
                                    <th class="pb-2 px-3">Amount</th>
                                    <th class="pb-2 px-3">Method</th>
                                    <th class="pb-2 px-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                                <?php foreach ($payouts as $p): ?>
                                    <tr>
                                        <td class="py-2.5 px-3 font-mono text-slate-400">#<?= $p['id'] ?></td>
                                        <td class="py-2.5 px-3 font-bold text-white">₹<?= number_format((float)$p['amount'], 2) ?></td>
                                        <td class="py-2.5 px-3 uppercase text-[10px] text-slate-300"><?= e($p['payout_method']) ?></td>
                                        <td class="py-2.5 px-3 text-right">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $p['status'] === 'paid' ? 'bg-emerald-500/10 text-emerald-400' : ($p['status'] === 'rejected' ? 'bg-rose-500/10 text-rose-400' : 'bg-amber-500/10 text-amber-400') ?>">
                                                <?= strtoupper(e($p['status'])) ?>
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
    </div>
</div>
