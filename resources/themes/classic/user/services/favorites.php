<div class="space-y-6 max-w-6xl mx-auto" id="favorite-services-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/services-list" class="hover:text-white transition">&larr; Full Catalog</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Favorites</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Bookmarked & Favorite Services</h1>
            <p class="text-sm text-slate-400 mt-1">Quick access to your most frequently used promotional packages and services.</p>
        </div>
        <a href="/orders/new" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white transition shadow-sm">
            + Place New Order
        </a>
    </div>

    <!-- Favorites Grid/List -->
    <?php if (empty($services)): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center">
            <div class="text-4xl mb-3">⭐</div>
            <h2 class="text-base font-bold text-white">No Favorite Services Saved</h2>
            <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-6">
                Click the star icon next to any service on the Services Catalog or New Order page to pin it here for rapid access.
            </p>
            <a href="/services-list" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white transition border border-slate-700">
                Browse Services Catalog
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($services as $s): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-[11px] font-semibold text-indigo-400 uppercase tracking-wider"><?= e($s['category_name']) ?></span>
                            <form action="/services/<?= $s['id'] ?>/favorite" method="POST">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-amber-400 hover:text-amber-300 text-sm transition" title="Remove from favorites">
                                    ★
                                </button>
                            </form>
                        </div>
                        <h3 class="text-sm font-bold text-white mt-2 leading-snug"><?= e($s['name']) ?></h3>
                        <div class="mt-3 flex items-baseline gap-1">
                            <span class="text-lg font-black text-emerald-400">₹<?= number_format((float)$s['rate'], 2) ?></span>
                            <span class="text-[11px] text-slate-500 font-mono">/ 1,000 units</span>
                        </div>
                        <div class="mt-3 flex items-center gap-3 text-xs text-slate-400">
                            <span>Min: <?= number_format($s['min_quantity']) ?></span>
                            <span>•</span>
                            <span>Max: <?= number_format($s['max_quantity']) ?></span>
                        </div>
                    </div>
                    <div class="mt-5 pt-4 border-t border-slate-800 flex items-center justify-between">
                        <span class="text-[11px] text-slate-500 font-mono">ID: #<?= $s['id'] ?></span>
                        <a href="/orders/new?service_id=<?= $s['id'] ?>" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white rounded-lg text-xs font-semibold transition">
                            Order Now &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
