<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">API Providers</h1>
            <p class="text-xs text-slate-400 mt-1">Connect upstream wholesale SMM panels using standard SMM API v2 specification.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Provider Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white pb-3 border-b border-slate-800">Connect New Provider</h3>

            <form action="/admin/providers" method="POST" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Provider Name *</label>
                    <input type="text" name="name" required placeholder="e.g. JustAnotherPanel, SMMKings"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">API Endpoint URL *</label>
                    <input type="url" name="api_url" required placeholder="https://provider.com/api/v2"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs font-mono focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">API Key *</label>
                    <input type="text" name="api_key" required placeholder="Provider Secret API Key"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs font-mono focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Currency *</label>
                        <select name="currency" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                            <option value="USD">USD ($)</option>
                            <option value="INR">INR (₹)</option>
                            <option value="EUR">EUR (€)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition">
                    Save Provider Connection
                </button>
            </form>
        </div>

        <!-- Providers List -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Configured Upstream Connections</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Provider</th>
                            <th class="py-3 px-4">API URL</th>
                            <th class="py-3 px-4">Current Balance</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($providers)): ?>
                            <tr><td colspan="5" class="py-8 text-center text-slate-500">No providers configured yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($providers as $p): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-white"><?= e($p['name']) ?></div>
                                        <div class="text-[10px] text-slate-500 font-mono">ID: #<?= $p['id'] ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-slate-400 max-w-[180px] truncate">
                                        <?= e($p['api_url']) ?>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-semibold text-emerald-400">
                                        <?= e($p['currency']) ?> <?= number_format((float)($p['balance'] ?? 0), 2) ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold <?= $p['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                        <form action="/admin/providers/<?= $p['id'] ?>/test" method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">Ping Balance</button>
                                        </form>
                                        <a href="/admin/providers/<?= $p['id'] ?>/import" class="inline-block px-2.5 py-1 rounded bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 border border-rose-500/20 text-xs font-semibold transition">
                                            Import Services &rarr;
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
</div>
