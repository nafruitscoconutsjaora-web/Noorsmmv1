<div class="space-y-6 max-w-7xl mx-auto" id="admin-schedules-management">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin/orders" class="hover:text-white transition">&larr; Orders</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Recurring & Drip Automations</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Scheduled & Recurring Order Management</h1>
            <p class="text-sm text-slate-400 mt-1">Monitor all recurring user drip-feed jobs, next trigger timings, and execution cycles.</p>
        </div>
    </div>

    <!-- Active Schedules Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">All Platform Schedules</h2>
            <span class="text-xs text-slate-400"><?= count($schedules) ?> schedules</span>
        </div>

        <?php if (empty($schedules)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">No scheduled order campaigns found.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Schedule #</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Target Link</th>
                            <th class="py-3 px-4 text-center">Batch Runs</th>
                            <th class="py-3 px-4 text-center">Interval</th>
                            <th class="py-3 px-4">Next Trigger</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($schedules as $sc): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-400">#<?= $sc['id'] ?></td>
                                <td class="py-3 px-4 font-semibold text-white">
                                    <a href="/admin/users/<?= $sc['user_id'] ?>" class="hover:text-indigo-400 transition">
                                        <?= e($sc['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4 max-w-xs truncate font-medium"><?= e($sc['service_name']) ?></td>
                                <td class="py-3 px-4 font-mono text-slate-400 text-[11px] max-w-xs truncate"><?= e($sc['link']) ?></td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-indigo-400">
                                    <?= $sc['runs_completed'] ?> / <?= $sc['runs_total'] ?>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-slate-300"><?= $sc['interval_hours'] ?> hrs</td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-400"><?= $sc['next_run_at'] ? substr($sc['next_run_at'], 0, 16) : '-' ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $sc['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($sc['status'] === 'completed' ? 'bg-indigo-500/10 text-indigo-400' : 'bg-slate-800 text-slate-400') ?>">
                                        <?= strtoupper(e($sc['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-1">
                                    <?php if ($sc['status'] === 'active'): ?>
                                        <form action="/admin/schedules/<?= $sc['id'] ?>/cancel" method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" onclick="return confirm('Cancel this schedule?')" class="px-2.5 py-1 bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white rounded-lg text-xs font-semibold transition border border-rose-600/30">
                                                Stop
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-[11px]">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
