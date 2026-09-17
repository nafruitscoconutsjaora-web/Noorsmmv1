<div class="space-y-6 max-w-7xl mx-auto" id="service-quality-monitor">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin/services" class="hover:text-white transition">&larr; Services</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Quality & Delivery Health</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Service Quality & Speed Assurance</h1>
            <p class="text-sm text-slate-400 mt-1">Monitor real-time fulfillment success rates, failure ratios, and drop risks per service package.</p>
        </div>
    </div>

    <!-- Quality Overview Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Quality Matrix by Service</h2>
            <span class="text-xs text-slate-400"><?= count($services) ?> monitored services</span>
        </div>

        <?php if (empty($services)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">No service data available.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4 text-center">Total Orders</th>
                            <th class="py-3 px-4 text-center">Completed</th>
                            <th class="py-3 px-4 text-center">Failed / Canceled</th>
                            <th class="py-3 px-4 text-center">Success Rate</th>
                            <th class="py-3 px-4 text-center">Quality Grade</th>
                            <th class="py-3 px-4 text-right">Quick Toggle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($services as $s): 
                            $total = (int)($s['total_orders'] ?? 0);
                            $completed = (int)($s['completed_orders'] ?? 0);
                            $failed = (int)($s['failed_orders'] ?? 0);
                            $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 100;
                        ?>
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white max-w-xs truncate" title="<?= e($s['name']) ?>">
                                    <span class="font-mono text-slate-400 mr-1">#<?= $s['id'] ?></span>
                                    <?= e($s['name']) ?>
                                </td>
                                <td class="py-3 px-4 text-slate-400"><?= e($s['category_name']) ?></td>
                                <td class="py-3 px-4 text-center font-mono"><?= number_format($total) ?></td>
                                <td class="py-3 px-4 text-center font-mono text-emerald-400"><?= number_format($completed) ?></td>
                                <td class="py-3 px-4 text-center font-mono text-rose-400"><?= number_format($failed) ?></td>
                                <td class="py-3 px-4 text-center font-mono font-bold">
                                    <span class="<?= $rate >= 90 ? 'text-emerald-400' : ($rate >= 75 ? 'text-amber-400' : 'text-rose-400') ?>">
                                        <?= $rate ?>%
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $rate >= 90 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($rate >= 75 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20') ?>">
                                        <?= $rate >= 90 ? 'Grade A' : ($rate >= 75 ? 'Grade B' : 'Action Needed') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form action="/admin/services/<?= $s['id'] ?>/toggle" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-2.5 py-1 rounded text-[11px] font-semibold transition <?= $s['status'] === 'active' ? 'bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 border border-rose-600/20' : 'bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 border border-emerald-600/20' ?>">
                                            <?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
