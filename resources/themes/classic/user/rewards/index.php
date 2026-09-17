<div class="space-y-6 max-w-5xl mx-auto" id="loyalty-rewards-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white">VIP Club & Loyalty Rewards</h1>
            <p class="text-sm text-slate-400 mt-1">Earn rewards points on every order placed and unlock exclusive tier discounts.</p>
        </div>
    </div>

    <!-- Tier Overview Card -->
    <div class="bg-gradient-to-br from-indigo-900/60 via-slate-900 to-slate-950 border border-indigo-500/30 rounded-2xl p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold uppercase tracking-wider">
                    ⭐ Current Tier: <?= strtoupper(e($tier['name'])) ?>
                </div>
                <h2 class="text-2xl font-extrabold text-white mt-3"><?= number_format($points) ?> Reward Points</h2>
                <p class="text-xs text-slate-300 mt-1">Lifetime Spent: <strong class="text-white">₹<?= number_format($lifetimeSpent, 2) ?></strong></p>
            </div>
            <div class="flex flex-col items-start sm:items-end gap-2 bg-slate-900/80 p-4 rounded-xl border border-slate-800">
                <span class="text-xs text-slate-400">Redeem Points for Wallet Credit</span>
                <span class="text-xs font-mono text-emerald-400">100 Points = ₹1.00</span>
                <form action="/rewards/redeem" method="POST" class="mt-1 flex items-center gap-2">
                    <?= csrf_field() ?>
                    <input type="number" name="points" min="100" max="<?= $points ?>" step="100" placeholder="Min 100" class="w-28 bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none">
                    <button type="submit" <?= $points < 100 ? 'disabled' : '' ?> class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition">
                        Redeem
                    </button>
                </form>
            </div>
        </div>

        <!-- Progress to Next Tier -->
        <?php if ($tier['next_threshold']): 
            $currentBase = $tier['threshold'];
            $target = $tier['next_threshold'];
            $progressPct = min(100, round((($lifetimeSpent - $currentBase) / max(1, $target - $currentBase)) * 100));
        ?>
            <div class="mt-6 pt-6 border-t border-slate-800">
                <div class="flex items-center justify-between text-xs text-slate-300 mb-2">
                    <span>Progress to <?= ucfirst(e($tier['next_tier'])) ?> Tier</span>
                    <span class="font-bold text-indigo-400">₹<?= number_format($lifetimeSpent, 2) ?> / ₹<?= number_format($target, 2) ?> (<?= $progressPct ?>%)</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-emerald-400 h-2.5 rounded-full" style="width: <?= $progressPct ?>%"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tier Matrix Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-slate-900 border <?= $tier['name'] === 'bronze' ? 'border-indigo-500 ring-1 ring-indigo-500/20' : 'border-slate-800' ?> rounded-2xl p-5">
            <span class="text-xs font-mono uppercase text-amber-500 font-bold">Bronze</span>
            <div class="text-base font-bold text-white mt-1">₹0+ Spent</div>
            <ul class="text-xs text-slate-400 space-y-1.5 mt-3">
                <li>• Standard Catalog Rates</li>
                <li>• Standard Support Queue</li>
                <li>• 1 Pt per ₹10 Spent</li>
            </ul>
        </div>
        <div class="bg-slate-900 border <?= $tier['name'] === 'silver' ? 'border-indigo-500 ring-1 ring-indigo-500/20' : 'border-slate-800' ?> rounded-2xl p-5">
            <span class="text-xs font-mono uppercase text-slate-300 font-bold">Silver</span>
            <div class="text-base font-bold text-white mt-1">₹5,000+ Spent</div>
            <ul class="text-xs text-slate-400 space-y-1.5 mt-3">
                <li>• 2% Auto Rate Discount</li>
                <li>• Faster Ticket Response</li>
                <li>• 1.2 Pt per ₹10 Spent</li>
            </ul>
        </div>
        <div class="bg-slate-900 border <?= $tier['name'] === 'gold' ? 'border-indigo-500 ring-1 ring-indigo-500/20' : 'border-slate-800' ?> rounded-2xl p-5">
            <span class="text-xs font-mono uppercase text-amber-400 font-bold">Gold</span>
            <div class="text-base font-bold text-white mt-1">₹25,000+ Spent</div>
            <ul class="text-xs text-slate-400 space-y-1.5 mt-3">
                <li>• 5% Auto Rate Discount</li>
                <li>• Priority Ticket Queue</li>
                <li>• 1.5 Pt per ₹10 Spent</li>
            </ul>
        </div>
        <div class="bg-slate-900 border <?= $tier['name'] === 'platinum' ? 'border-indigo-500 ring-1 ring-indigo-500/20' : 'border-slate-800' ?> rounded-2xl p-5">
            <span class="text-xs font-mono uppercase text-cyan-400 font-bold">Platinum</span>
            <div class="text-base font-bold text-white mt-1">₹100,000+ Spent</div>
            <ul class="text-xs text-slate-400 space-y-1.5 mt-3">
                <li>• 10% Wholesale Pricing</li>
                <li>• Dedicated Telegram Rep</li>
                <li>• 2.0 Pt per ₹10 Spent</li>
            </ul>
        </div>
    </div>

    <!-- Points Ledger -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white">Points History</h3>
        </div>

        <?php if (empty($history)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No points history recorded yet. Place orders to begin accumulating loyalty perks!</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Event Description</th>
                            <th class="py-3 px-4 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="py-2.5 px-4 font-mono text-slate-400"><?= substr($h['created_at'], 0, 16) ?></td>
                                <td class="py-2.5 px-4 text-white font-medium"><?= e($h['description']) ?></td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold <?= (int)$h['points'] > 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                                    <?= (int)$h['points'] > 0 ? '+' : '' ?><?= number_format((int)$h['points']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
