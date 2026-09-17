<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Payment Gateways Management</h1>
            <p class="text-xs text-slate-400 mt-1">Configure and monitor all 46+ integrated payment adapters, merchant credentials, fees, and deposit bonuses.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <?= (int)$metrics['active'] ?> Active Gateways
            </span>
        </div>
    </div>

    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total</div>
            <div class="text-2xl font-extrabold text-white mt-1"><?= (int)$metrics['total'] ?></div>
            <div class="text-[10px] text-slate-500 mt-0.5">Built-in adapters</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div class="text-[11px] font-semibold text-emerald-400 uppercase tracking-wider">Active</div>
            <div class="text-2xl font-extrabold text-emerald-400 mt-1"><?= (int)$metrics['active'] ?></div>
            <div class="text-[10px] text-slate-500 mt-0.5">Customer checkout</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div class="text-[11px] font-semibold text-indigo-400 uppercase tracking-wider">Indian (UPI)</div>
            <div class="text-2xl font-extrabold text-indigo-300 mt-1"><?= (int)$metrics['indian'] ?></div>
            <div class="text-[10px] text-slate-500 mt-0.5">Razorpay, Cashfree, etc.</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div class="text-[11px] font-semibold text-blue-400 uppercase tracking-wider">International</div>
            <div class="text-2xl font-extrabold text-blue-300 mt-1"><?= (int)$metrics['international'] ?></div>
            <div class="text-[10px] text-slate-500 mt-0.5">Stripe, PayPal, Adyen</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Crypto</div>
            <div class="text-2xl font-extrabold text-amber-300 mt-1"><?= (int)$metrics['crypto'] ?></div>
            <div class="text-[10px] text-slate-500 mt-0.5">Binance, Coinbase, etc.</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div class="text-[11px] font-semibold text-purple-400 uppercase tracking-wider">Manual</div>
            <div class="text-2xl font-extrabold text-purple-300 mt-1"><?= (int)$metrics['manual'] ?></div>
            <div class="text-[10px] text-slate-500 mt-0.5">Bank & Custom QR</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <!-- Category Filter Tabs -->
        <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto">
            <?php
                $tabs = [
                    'all' => 'All Gateways (' . $metrics['total'] . ')',
                    'indian' => '🇮🇳 Indian (' . $metrics['indian'] . ')',
                    'international' => '🌐 International (' . $metrics['international'] . ')',
                    'crypto' => '⚡ Crypto (' . $metrics['crypto'] . ')',
                    'manual' => '🏦 Manual (' . $metrics['manual'] . ')',
                ];
            ?>
            <?php foreach ($tabs as $key => $label): ?>
                <a href="/admin/gateways?category=<?= e($key) ?>&search=<?= urlencode($search) ?>"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= $category === $key ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search Box -->
        <form action="/admin/gateways" method="GET" class="relative w-full sm:w-64">
            <input type="hidden" name="category" value="<?= e($category) ?>">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search gateways..."
                   class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 text-xs pointer-events-none">🔍</span>
        </form>
    </div>

    <!-- Gateways Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Gateway</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Currency</th>
                        <th class="py-3.5 px-4">Min / Max Limits</th>
                        <th class="py-3.5 px-4">Fee Structure</th>
                        <th class="py-3.5 px-4">Bonus Perk</th>
                        <th class="py-3.5 px-4">Credentials</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($gateways)): ?>
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500">No payment gateways matched the filter criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gateways as $gw): ?>
                            <?php
                                $categoryBadge = match($gw['category'] ?? '') {
                                    'indian', 'india' => '<span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Indian UPI</span>',
                                    'international' => '<span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">International</span>',
                                    'crypto' => '<span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Crypto</span>',
                                    'manual' => '<span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">Manual</span>',
                                    default => '<span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300">Default</span>',
                                };

                                $hasCreds = !empty($gw['has_credentials']);
                                $isEnabled = !empty($gw['is_enabled']);
                            ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white text-xs flex items-center gap-2">
                                        <span><?= e($gw['name']) ?></span>
                                    </div>
                                    <div class="font-mono text-[10px] text-slate-500 mt-0.5"><?= e($gw['code']) ?></div>
                                </td>
                                <td class="py-3.5 px-4"><?= $categoryBadge ?></td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-slate-200"><?= e($gw['currency'] ?? 'INR') ?></td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-300">
                                    ₹<?= number_format((float)$gw['min_amount'], 0) ?> &ndash; ₹<?= number_format((float)$gw['max_amount'], 0) ?>
                                </td>
                                <td class="py-3.5 px-4 text-[11px]">
                                    <?php if ((float)$gw['percent_fee'] > 0 || (float)$gw['fixed_fee'] > 0): ?>
                                        <span class="text-amber-400 font-semibold font-mono">
                                            <?= (float)$gw['percent_fee'] ?>% + ₹<?= number_format((float)$gw['fixed_fee'], 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-500">0% (Free)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($gw['bonus_enabled']) && (float)$gw['bonus_value'] > 0): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            +<?= (float)$gw['bonus_value'] ?><?= $gw['bonus_type'] === 'percentage' ? '%' : '₹' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-500">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($hasCreds): ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Set
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Missing
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <form action="/admin/gateways/<?= (int)$gw['id'] ?>/toggle" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider transition <?= $isEnabled ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/25' : 'bg-slate-800 text-slate-400 hover:text-white border border-slate-700' ?>">
                                            <?= $isEnabled ? 'Active' : 'Disabled' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/admin/gateways/<?= (int)$gw['id'] ?>/edit"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-600/15 hover:bg-indigo-600/30 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-semibold transition">
                                        <span>Configure</span> &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
