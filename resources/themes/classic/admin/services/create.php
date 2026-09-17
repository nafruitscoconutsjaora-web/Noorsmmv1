<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <a href="/admin/services" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-2">
            &larr; Back to Services
        </a>
        <h1 class="text-2xl font-bold text-white tracking-tight">Create New Service</h1>
        <p class="text-xs text-slate-400 mt-1">Add a new service directly or bind it to an upstream provider.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <form action="/admin/services" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Category *</label>
                    <select name="category_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Service Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Instagram High Quality Followers"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>
            </div>

            <!-- Upstream Provider Settings -->
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-4">
                <div class="text-xs font-bold text-rose-400 uppercase tracking-wider">Upstream Provider Integration (Optional)</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Provider</label>
                        <select name="provider_id" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                            <option value="">None (Manual / Direct Fulfillment)</option>
                            <?php foreach ($providers as $prov): ?>
                                <option value="<?= $prov['id'] ?>"><?= e($prov['name']) ?> (<?= e($prov['currency']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Provider Remote Service ID</label>
                        <input type="text" name="provider_service_id" placeholder="e.g. 1024"
                            class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Provider Wholesale Cost</label>
                        <input type="number" step="0.0001" name="provider_cost" value="0.0000"
                            class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Provider Currency</label>
                        <select name="provider_currency" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                            <option value="USD">USD</option>
                            <option value="INR">INR</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Pricing & Limits -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Selling Rate (₹ per 1,000) *</label>
                    <input type="number" step="0.01" name="rate" required placeholder="50.00"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-emerald-400 font-bold font-mono text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Minimum Quantity *</label>
                    <input type="number" name="min_quantity" value="10" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 font-mono text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Maximum Quantity *</label>
                    <input type="number" name="max_quantity" value="10000" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 font-mono text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Description / Instructions for User</label>
                <textarea name="description" rows="3" placeholder="Enter service guarantees, speed notes, link requirements..."
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition"></textarea>
            </div>

            <!-- Features and Flags -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-950/40 border border-slate-800 text-xs text-slate-300">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="refill" value="1" class="rounded bg-slate-900 border-slate-700 text-rose-600 focus:ring-0">
                    <span>Refill Support</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="cancel" value="1" class="rounded bg-slate-900 border-slate-700 text-rose-600 focus:ring-0">
                    <span>Cancel Button</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="drip_feed" value="1" class="rounded bg-slate-900 border-slate-700 text-rose-600 focus:ring-0">
                    <span>Drip-feed Enabled</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="status" value="active" checked class="rounded bg-slate-900 border-slate-700 text-rose-600 focus:ring-0">
                    <span>Active in Catalog</span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs shadow-md shadow-rose-600/20 transition">
                Create Service
            </button>
        </form>
    </div>
</div>
