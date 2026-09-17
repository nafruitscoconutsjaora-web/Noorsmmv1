<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Service Categories</h1>
            <p class="text-xs text-slate-400 mt-1">Organize social media platforms and service groupings.</p>
        </div>
        <a href="/admin/services" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
            &larr; Return to Services
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- New Category Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white pb-3 border-b border-slate-800">Add New Category</h3>

            <form action="/admin/categories" method="POST" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Category Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Instagram Followers"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Display Icon</label>
                    <input type="text" name="icon" placeholder="e.g. instagram, youtube, layers" value="layers"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="0"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition">
                    Save Category
                </button>
            </form>
        </div>

        <!-- Categories Table -->
        <div class="md:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Existing Categories</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Slug</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="5" class="py-8 text-center text-slate-500">No categories created yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-rose-400"><?= $cat['id'] ?></td>
                                    <td class="py-3 px-4 font-semibold text-white"><?= e($cat['name']) ?></td>
                                    <td class="py-3 px-4 font-mono text-slate-400 text-[11px]"><?= e($cat['slug']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <?= ucfirst($cat['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <form action="/admin/categories/<?= $cat['id'] ?>/delete" method="POST" class="inline" onsubmit="return confirm('Delete this category? Services linked to it may become unassigned.');">
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
        </div>
    </div>
</div>
