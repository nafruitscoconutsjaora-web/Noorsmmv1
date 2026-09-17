<div class="space-y-6 max-w-6xl mx-auto" id="scheduled-orders-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/orders" class="hover:text-white transition">&larr; Orders</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Recurring & Drip Delivery</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Scheduled & Recurring Orders</h1>
            <p class="text-sm text-slate-400 mt-1">Automate periodic order dispatch at scheduled intervals (e.g. daily, weekly drip).</p>
        </div>
    </div>

    <!-- Create Schedule Form & Existing List -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Create Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 h-fit shadow-sm">
            <h2 class="text-base font-bold text-white mb-2">Schedule Recurring Order</h2>
            <p class="text-xs text-slate-400 mb-5">Set up an automated batch to run repeatedly at specified hours.</p>

            <form action="/orders/schedules" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Select Service</label>
                    <select name="service_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Choose Service...</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= e($s['name']) ?> (₹<?= number_format((float)$s['rate'], 2) ?>/k)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Target Link</label>
                    <input type="url" name="link" required placeholder="https://..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Quantity Per Run</label>
                        <input type="number" name="quantity" required min="1" placeholder="1000" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Total Runs</label>
                        <input type="number" name="runs_total" required min="1" max="100" value="5" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Interval (Hours Between Runs)</label>
                    <input type="number" name="interval_hours" required min="1" max="720" value="24" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <p class="text-[11px] text-slate-500 mt-1">E.g. 24 = once every day; 1 = every hour.</p>
                </div>
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm mt-2">
                    Create Recurring Schedule
                </button>
            </form>
        </div>

        <!-- Schedules List -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-bold text-white">Active & Past Schedules (<?= count($schedules) ?>)</h2>
            </div>

            <?php if (empty($schedules)): ?>
                <div class="p-12 text-center text-slate-500 text-xs">
                    No scheduled order automations set up yet.
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-800/60">
                    <?php foreach ($schedules as $sc): ?>
                        <div class="p-5 hover:bg-slate-800/20 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">#<?= $sc['id'] ?></span>
                                    <h3 class="text-sm font-bold text-white"><?= e($sc['service_name']) ?></h3>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $sc['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($sc['status'] === 'completed' ? 'bg-indigo-500/10 text-indigo-400' : 'bg-slate-800 text-slate-400') ?>">
                                        <?= strtoupper(e($sc['status'])) ?>
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 truncate max-w-md">Link: <span class="font-mono text-slate-300"><?= e($sc['link']) ?></span></div>
                                <div class="flex items-center gap-4 text-xs text-slate-500 pt-1">
                                    <span>Quantity: <strong class="text-slate-300"><?= number_format($sc['quantity']) ?></strong></span>
                                    <span>Progress: <strong class="text-indigo-400"><?= $sc['runs_completed'] ?> / <?= $sc['runs_total'] ?> runs</strong></span>
                                    <span>Interval: <strong class="text-slate-300">Every <?= $sc['interval_hours'] ?>h</strong></span>
                                </div>
                                <?php if ($sc['status'] === 'active' && !empty($sc['next_run_at'])): ?>
                                    <div class="text-[11px] text-emerald-400 font-mono">Next automated trigger: <?= substr($sc['next_run_at'], 0, 16) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <?php if ($sc['status'] !== 'completed' && $sc['status'] !== 'cancelled'): ?>
                                    <form action="/orders/schedules/<?= $sc['id'] ?>/toggle" method="POST">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 border border-slate-700 transition">
                                            <?= $sc['status'] === 'active' ? 'Pause' : 'Resume' ?>
                                        </button>
                                    </form>
                                    <form action="/orders/schedules/<?= $sc['id'] ?>/cancel" method="POST">
                                        <?= csrf_field() ?>
                                        <button type="submit" onclick="return confirm('Cancel this recurring schedule?')" class="px-3 py-1.5 rounded-lg bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 border border-rose-600/20 text-xs transition">
                                            Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
