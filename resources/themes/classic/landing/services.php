<div class="py-12 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Services & Pricing</h1>
            <p class="text-sm text-slate-400 mt-1">Browse our real-time wholesale catalog. All rates shown are per 1,000 units in INR (₹).</p>
        </div>

        <!-- Filter Controls -->
        <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="w-full md:w-72">
                <input type="text" id="serviceSearch" placeholder="Search service name or ID..." 
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="w-full md:w-auto flex items-center gap-2 overflow-x-auto pb-2 md:pb-0">
                <button onclick="filterCategory('all')" class="cat-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white transition whitespace-nowrap" data-cat="all">
                    All Categories (<?= count($services) ?>)
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button onclick="filterCategory('<?= $cat['id'] ?>')" class="cat-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition whitespace-nowrap" data-cat="<?= $cat['id'] ?>">
                        <?= e($cat['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Services Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="servicesTable">
                    <thead class="bg-slate-950/70 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">ID</th>
                            <th class="py-3.5 px-4 font-semibold">Service Name</th>
                            <th class="py-3.5 px-4 font-semibold">Category</th>
                            <th class="py-3.5 px-4 font-semibold">Rate / 1000</th>
                            <th class="py-3.5 px-4 font-semibold">Min / Max</th>
                            <th class="py-3.5 px-4 font-semibold">Features</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Order</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300 text-xs">
                        <?php foreach ($services as $srv): ?>
                            <tr class="service-row hover:bg-slate-800/40 transition" data-name="<?= strtolower(e($srv['name'])) ?>" data-id="<?= $srv['id'] ?>" data-category="<?= $srv['category_id'] ?>">
                                <td class="py-3.5 px-4 font-mono text-slate-500">#<?= $srv['id'] ?></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-white"><?= e($srv['name']) ?></div>
                                    <?php if (!empty($srv['description'])): ?>
                                        <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1"><?= e($srv['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                        <?= e($srv['category_name']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-emerald-400">
                                    ₹<?= number_format((float)$srv['rate'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    <?= number_format((int)$srv['min_quantity']) ?> - <?= number_format((int)$srv['max_quantity']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <?php if ($srv['refill']): ?>
                                            <span class="px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px]" title="Refill Supported">Refill</span>
                                        <?php endif; ?>
                                        <?php if ($srv['cancel']): ?>
                                            <span class="px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px]" title="Cancel Supported">Cancel</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/orders/new?service_id=<?= $srv['id'] ?>" class="inline-flex items-center px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs transition">
                                        Order
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    let currentCategory = 'all';
    const searchInput = document.getElementById('serviceSearch');
    const rows = document.querySelectorAll('.service-row');
    const catButtons = document.querySelectorAll('.cat-filter-btn');

    function filterCategory(catId) {
        currentCategory = catId;
        catButtons.forEach(btn => {
            if (btn.dataset.cat === catId) {
                btn.className = 'cat-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white transition whitespace-nowrap';
            } else {
                btn.className = 'cat-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition whitespace-nowrap';
            }
        });
        applyFilter();
    }

    searchInput.addEventListener('input', applyFilter);

    function applyFilter() {
        const query = searchInput.value.toLowerCase().trim();
        rows.forEach(row => {
            const name = row.dataset.name;
            const id = row.dataset.id;
            const category = row.dataset.category;

            const matchesCategory = (currentCategory === 'all' || category === currentCategory);
            const matchesSearch = (!query || name.includes(query) || id.includes(query));

            row.style.display = (matchesCategory && matchesSearch) ? '' : 'none';
        });
    }
</script>
