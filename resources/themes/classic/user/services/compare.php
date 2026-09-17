<div class="space-y-6 max-w-6xl mx-auto" id="service-comparison-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/services-list" class="hover:text-white transition">&larr; Services Catalog</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Comparison Engine</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Service Comparison Matrix</h1>
            <p class="text-sm text-slate-400 mt-1">Compare pricing, minimum/maximum parameters, refill warranties, and speeds across multiple packages.</p>
        </div>
    </div>

    <!-- Service Selector Form -->
    <form method="GET" action="/services/compare" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-wrap items-end gap-4 shadow-sm">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Service 1</label>
            <select name="service_1" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="">Select first service...</option>
                <?php foreach ($all_services as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= (!empty($compared[0]) && $compared[0]['id'] == $s['id']) ? 'selected' : '' ?>>
                        <?= e($s['name']) ?> (₹<?= number_format((float)$s['rate'], 2) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Service 2</label>
            <select name="service_2" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="">Select second service...</option>
                <?php foreach ($all_services as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= (!empty($compared[1]) && $compared[1]['id'] == $s['id']) ? 'selected' : '' ?>>
                        <?= e($s['name']) ?> (₹<?= number_format((float)$s['rate'], 2) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Service 3 (Optional)</label>
            <select name="service_3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="">Select third service...</option>
                <?php foreach ($all_services as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= (!empty($compared[2]) && $compared[2]['id'] == $s['id']) ? 'selected' : '' ?>>
                        <?= e($s['name']) ?> (₹<?= number_format((float)$s['rate'], 2) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
            Compare Side-by-Side
        </button>
    </form>

    <!-- Comparison Table -->
    <?php if (empty($compared)): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center text-slate-500 text-xs">
            Select 2 or 3 services from the dropdowns above to generate a side-by-side comparison matrix.
        </div>
    <?php else: ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-950/60">
                            <th class="py-4 px-5 text-slate-400 font-semibold w-1/4">Feature / Metric</th>
                            <?php foreach ($compared as $cs): ?>
                                <th class="py-4 px-5 text-white font-bold w-1/4">
                                    <div class="text-indigo-400 text-[10px] font-mono uppercase"><?= e($cs['category_name']) ?></div>
                                    <div class="text-sm font-bold mt-1"><?= e($cs['name']) ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <tr>
                            <td class="py-3.5 px-5 font-semibold text-slate-400">Rate / 1,000 Units</td>
                            <?php foreach ($compared as $cs): ?>
                                <td class="py-3.5 px-5 font-bold text-emerald-400 text-base">₹<?= number_format((float)$cs['rate'], 2) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-5 font-semibold text-slate-400">Minimum Order</td>
                            <?php foreach ($compared as $cs): ?>
                                <td class="py-3.5 px-5 font-mono"><?= number_format($cs['min_quantity']) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-5 font-semibold text-slate-400">Maximum Order</td>
                            <?php foreach ($compared as $cs): ?>
                                <td class="py-3.5 px-5 font-mono"><?= number_format($cs['max_quantity']) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-5 font-semibold text-slate-400">Refill Warranty</td>
                            <?php foreach ($compared as $cs): ?>
                                <td class="py-3.5 px-5">
                                    <?php if ($cs['refill']): ?>
                                        <span class="text-emerald-400 font-semibold flex items-center gap-1">✔ Guaranteed Refill</span>
                                    <?php else: ?>
                                        <span class="text-slate-500 flex items-center gap-1">✖ No Refill</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-5 font-semibold text-slate-400">Instant Cancellation</td>
                            <?php foreach ($compared as $cs): ?>
                                <td class="py-3.5 px-5">
                                    <?php if ($cs['cancel']): ?>
                                        <span class="text-emerald-400 font-semibold flex items-center gap-1">✔ Allowed</span>
                                    <?php else: ?>
                                        <span class="text-slate-500 flex items-center gap-1">✖ Not Supported</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="py-4 px-5 font-semibold text-slate-400">Action</td>
                            <?php foreach ($compared as $cs): ?>
                                <td class="py-4 px-5">
                                    <a href="/orders/new?service_id=<?= $cs['id'] ?>" class="inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-sm">
                                        Order This Service &rarr;
                                    </a>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
