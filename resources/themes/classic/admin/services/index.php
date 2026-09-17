<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Services Catalog</h1>
            <p class="text-xs text-slate-400 mt-1">Configure pricing rates, margin percentages, and upstream API provider bindings.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/categories" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
                Categories
            </a>
            <a href="/admin/services/new" class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-md shadow-rose-600/20 transition">
                + Create Service
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto text-xs pb-2 md:pb-0">
            <a href="/admin/services<?= $search ? '?search=' . urlencode($search) : '' ?>"
                class="px-3 py-1.5 rounded-lg font-semibold transition whitespace-nowrap <?= empty($selected_category_id) ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-750' ?>">
                All Categories
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="/admin/services?category_id=<?= $cat['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                    class="px-3 py-1.5 rounded-lg font-semibold transition whitespace-nowrap <?= $selected_category_id == $cat['id'] ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-750' ?>">
                    <?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form action="/admin/services" method="GET" class="w-full md:w-64 flex items-center gap-2">
            <?php if ($selected_category_id): ?>
                <input type="hidden" name="category_id" value="<?= $selected_category_id ?>">
            <?php endif; ?>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search service name..."
                class="w-full px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-white text-xs font-medium transition">
                Search
            </button>
        </form>
    </div>

    <!-- Services Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">ID</th>
                        <th class="py-3.5 px-4">Name</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Provider</th>
                        <th class="py-3.5 px-4">Cost</th>
                        <th class="py-3.5 px-4">Selling Rate</th>
                        <th class="py-3.5 px-4">Min/Max</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($services['items'])): ?>
                        <tr><td colspan="9" class="py-12 text-center text-slate-500">No services found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($services['items'] as $srv): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-rose-400"><?= $srv['id'] ?></td>
                                <td class="py-3.5 px-4 font-semibold text-white max-w-[200px] truncate">
                                    <a href="/admin/services/<?= $srv['id'] ?>/edit" class="hover:text-rose-400 transition">
                                        <?= e($srv['name']) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400"><?= e($srv['category_name'] ?? '-') ?></td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400">
                                    <?php if (!empty($srv['provider_name'])): ?>
                                        <span class="text-indigo-400 font-medium"><?= e($srv['provider_name']) ?></span>
                                        <span class="text-slate-600">(#<?= e($srv['provider_service_id'] ?? '') ?>)</span>
                                    <?php else: ?>
                                        <span class="text-slate-600">Direct</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    <?= e($srv['provider_currency'] ?? 'USD') ?> <?= number_format((float)($srv['provider_cost'] ?? 0), 4) ?>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-emerald-400 font-mono">
                                    ₹<?= number_format((float)$srv['rate'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    <?= number_format((int)$srv['min_quantity']) ?> - <?= number_format((int)$srv['max_quantity']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold <?= $srv['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' ?>">
                                        <?= ucfirst($srv['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-2">
                                    <a href="/admin/services/<?= $srv['id'] ?>/edit" class="text-indigo-400 hover:text-indigo-300 font-medium">Edit</a>
                                    <form action="/admin/services/<?= $srv['id'] ?>/delete" method="POST" class="inline" onsubmit="return confirm('Delete this service?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 font-medium">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($services['last_page'] > 1): ?>
            <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <div>Page <?= $services['page'] ?> of <?= $services['last_page'] ?> (<?= $services['total'] ?> total)</div>
                <div class="flex items-center gap-2">
                    <?php if ($services['page'] > 1): ?>
                        <a href="/admin/services?page=<?= $services['page'] - 1 ?>&category_id=<?= $selected_category_id ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Prev</a>
                    <?php endif; ?>
                    <?php if ($services['page'] < $services['last_page']): ?>
                        <a href="/admin/services?page=<?= $services['page'] + 1 ?>&category_id=<?= $selected_category_id ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
