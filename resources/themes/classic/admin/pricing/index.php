<div class="space-y-6 max-w-6xl mx-auto" id="admin-pricing-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin/services" class="hover:text-white transition">&larr; Services</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Pricing & Markup Rules</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Pricing & Profit Markup Management</h1>
            <p class="text-sm text-slate-400 mt-1">Configure global profit multipliers and category-specific price rules across all provider catalogs.</p>
        </div>
    </div>

    <!-- Global Markup Card -->
    <div class="bg-gradient-to-r from-indigo-900/50 via-slate-900 to-slate-950 border border-indigo-500/30 rounded-2xl p-6 shadow-sm">
        <h2 class="text-base font-bold text-white mb-2">Global Profit Markup Multiplier</h2>
        <p class="text-xs text-slate-300 mb-5 max-w-2xl">
            Applying this recalculates the retail price for all active services based on their provider cost (or base rate) plus this markup percentage.
        </p>

        <form action="/admin/pricing/global" method="POST" class="flex flex-wrap items-center gap-3 max-w-lg">
            <?= csrf_field() ?>
            <div class="flex items-center gap-2 bg-slate-950/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                <span class="text-slate-400">Markup:</span>
                <input type="number" step="0.5" min="0" max="500" name="percent" value="<?= $global_markup ?>" class="w-20 bg-transparent text-white font-bold focus:outline-none text-right">
                <span class="text-slate-400">%</span>
            </div>
            <button type="submit" onclick="return confirm('Recalculate selling rates for ALL active services?')" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                Apply Global Markup
            </button>
        </form>
    </div>

    <!-- Category Markup Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800">
            <h2 class="text-base font-bold text-white">Category-Specific Profit Rules</h2>
            <p class="text-xs text-slate-400 mt-0.5">Apply custom markup percentages to individual categories.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                        <th class="py-3 px-4">Category Name</th>
                        <th class="py-3 px-4 text-center">Services Count</th>
                        <th class="py-3 px-4 text-right">Average Rate</th>
                        <th class="py-3 px-4 text-right">Apply Markup</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-white"><?= e($cat['name']) ?></td>
                            <td class="py-3.5 px-4 text-center font-mono text-slate-400"><?= number_format($cat['service_count']) ?></td>
                            <td class="py-3.5 px-4 text-right font-mono text-emerald-400 font-semibold">₹<?= number_format((float)$cat['avg_rate'], 2) ?></td>
                            <td class="py-3.5 px-4 text-right">
                                <form action="/admin/pricing/category/<?= $cat['id'] ?>" method="POST" class="inline-flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <div class="flex items-center gap-1 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1">
                                        <input type="number" step="1" min="0" max="500" name="percent" value="25" class="w-14 bg-transparent text-white text-xs font-mono text-right focus:outline-none">
                                        <span class="text-slate-500 text-[11px]">%</span>
                                    </div>
                                    <button type="submit" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-medium transition border border-slate-700">
                                        Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
