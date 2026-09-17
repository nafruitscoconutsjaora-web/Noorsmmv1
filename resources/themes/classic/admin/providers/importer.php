<div class="space-y-6 max-w-7xl mx-auto" id="provider-service-importer">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin/providers" class="hover:text-white transition">&larr; Providers</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Service Importer & Sync</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Import Services from <?= e($provider['name']) ?></h1>
            <p class="text-sm text-slate-400 mt-1">Live service discovery, automated markup pricing calculation, and bulk catalog import.</p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
            <strong>Error connecting to provider:</strong> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Import Controls Form -->
    <form action="/admin/providers/<?= $provider['id'] ?>/importer" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-wrap items-end gap-4 shadow-sm">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Assign Category</label>
                <select name="category_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-48">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Profit Markup (%)</label>
                <input type="number" step="1" min="0" max="500" name="markup_percent" value="30" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                    Import Selected Services
                </button>
            </div>
        </div>

        <!-- Available Services Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="select-all" onclick="document.querySelectorAll('.service-checkbox').forEach(c => c.checked = this.checked)" class="rounded bg-slate-950 border-slate-700 text-indigo-600">
                    <label for="select-all" class="text-xs font-semibold text-slate-300 cursor-pointer">Select All (<?= count($services) ?> available)</label>
                </div>
                <span class="text-xs text-slate-400">Endpoint: <?= e($provider['api_url']) ?></span>
            </div>

            <?php if (empty($services)): ?>
                <div class="p-12 text-center text-slate-500 text-sm">
                    No remote services found or API connection failed. Check API credentials in provider settings.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto max-h-[600px]">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-950 z-10 border-b border-slate-800">
                            <tr class="text-slate-400">
                                <th class="py-3 px-4 w-10"></th>
                                <th class="py-3 px-4">Remote ID</th>
                                <th class="py-3 px-4">Service Name</th>
                                <th class="py-3 px-4">Category</th>
                                <th class="py-3 px-4 text-right">Provider Cost</th>
                                <th class="py-3 px-4 text-right">Min / Max</th>
                                <th class="py-3 px-4 text-center">Refill</th>
                                <th class="py-3 px-4 text-center">Cancel</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <?php foreach ($services as $s): 
                                $sId = $s['service'] ?? ($s['id'] ?? '');
                                $sName = $s['name'] ?? 'Unnamed';
                                $sCat = $s['category'] ?? 'General';
                                $sRate = (float)($s['rate'] ?? 0);
                                $sMin = $s['min'] ?? 10;
                                $sMax = $s['max'] ?? 10000;
                                $sRefill = !empty($s['refill']);
                                $sCancel = !empty($s['cancel']);
                            ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-2.5 px-4">
                                        <input type="checkbox" name="selected_services[]" value="<?= htmlspecialchars(json_encode([
                                            'service' => $sId,
                                            'name' => $sName,
                                            'rate' => $sRate,
                                            'min' => $sMin,
                                            'max' => $sMax,
                                            'refill' => $sRefill,
                                            'cancel' => $sCancel,
                                        ])) ?>" class="service-checkbox rounded bg-slate-950 border-slate-700 text-indigo-600">
                                    </td>
                                    <td class="py-2.5 px-4 font-mono text-slate-400">#<?= e((string)$sId) ?></td>
                                    <td class="py-2.5 px-4 font-semibold text-white max-w-sm truncate" title="<?= e($sName) ?>"><?= e($sName) ?></td>
                                    <td class="py-2.5 px-4 text-slate-400"><?= e($sCat) ?></td>
                                    <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-400">₹<?= number_format($sRate, 4) ?></td>
                                    <td class="py-2.5 px-4 text-right font-mono text-slate-400"><?= number_format($sMin) ?> - <?= number_format($sMax) ?></td>
                                    <td class="py-2.5 px-4 text-center">
                                        <?= $sRefill ? '<span class="text-emerald-400">✔</span>' : '<span class="text-slate-600">✖</span>' ?>
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <?= $sCancel ? '<span class="text-emerald-400">✔</span>' : '<span class="text-slate-600">✖</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>
