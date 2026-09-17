<div class="space-y-6">
    <div>
        <a href="/admin/providers" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-2">
            &larr; Back to Providers
        </a>
        <h1 class="text-2xl font-bold text-white tracking-tight">Import Services from <?= e($provider['name']) ?></h1>
        <p class="text-xs text-slate-400 mt-1">Select remote wholesale services to automatically calculate profit margins and add to your catalog.</p>
    </div>

    <form action="/admin/providers/<?= $provider['id'] ?>/import" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Import Config Header -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Profit Margin (%) *</label>
                <div class="relative">
                    <input type="number" step="0.5" name="margin_percentage" value="35" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-emerald-400 font-bold font-mono text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                    <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-semibold">%</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-1">Selling price will be wholesale cost + margin%</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Fallback Category</label>
                <select name="default_category_id" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10px] text-slate-500 mt-1">Used if remote category is not matched</p>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-600/20 transition">
                    Execute Bulk Import &rarr;
                </button>
            </div>
        </div>

        <!-- Remote Services List -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" class="rounded bg-slate-950 border-slate-750 text-rose-600 focus:ring-0">
                    <label for="selectAllCheckbox" class="text-xs font-bold text-white cursor-pointer">Select All Remote Services (<?= count($remote_services) ?> Available)</label>
                </div>
                <div class="text-xs text-slate-400">Currency: <span class="font-mono text-white"><?= e($provider['currency']) ?></span></div>
            </div>

            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800 sticky top-0 z-10">
                        <tr>
                            <th class="py-3 px-4 w-10"></th>
                            <th class="py-3 px-4">Remote ID</th>
                            <th class="py-3 px-4">Service Name</th>
                            <th class="py-3 px-4">Remote Category</th>
                            <th class="py-3 px-4">Wholesale Rate</th>
                            <th class="py-3 px-4">Min/Max</th>
                            <th class="py-3 px-4">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($remote_services)): ?>
                            <tr><td colspan="7" class="py-12 text-center text-slate-500">No services returned by the provider API.</td></tr>
                        <?php else: ?>
                            <?php foreach ($remote_services as $s): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" name="services[]" value="<?= e($s['service']) ?>" class="service-checkbox rounded bg-slate-950 border-slate-750 text-rose-600 focus:ring-0">
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-rose-400">#<?= e($s['service']) ?></td>
                                    <td class="py-3 px-4 font-medium text-white max-w-[280px] truncate"><?= e($s['name']) ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?= e($s['category'] ?? '-') ?></td>
                                    <td class="py-3 px-4 font-mono font-semibold text-emerald-400">
                                        <?= e($provider['currency']) ?> <?= number_format((float)($s['rate'] ?? 0), 4) ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-400">
                                        <?= number_format((int)($s['min'] ?? 1)) ?> - <?= number_format((int)($s['max'] ?? 10000)) ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[11px] text-slate-400"><?= e($s['type'] ?? 'default') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<script>
    function toggleSelectAll(source) {
        const checkboxes = document.querySelectorAll('.service-checkbox');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }
</script>
