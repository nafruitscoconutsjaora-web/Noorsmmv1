<div class="space-y-6 max-w-6xl mx-auto" id="admin-cron-management">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Cron Management</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Automated Tasks & Cron Job Monitor</h1>
            <p class="text-sm text-slate-400 mt-1">Background synchronization of orders with providers, automated drip execution, and status polling.</p>
        </div>
    </div>

    <!-- Scheduled Tasks Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Registered Automated Workers</h2>
            <span class="text-xs text-slate-400">Daemon active</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                        <th class="py-3 px-4">Task Name</th>
                        <th class="py-3 px-4">Schedule Expression</th>
                        <th class="py-3 px-4">Last Run Status</th>
                        <th class="py-3 px-4">Last Executed</th>
                        <th class="py-3 px-4">Duration</th>
                        <th class="py-3 px-4 text-center">State</th>
                        <th class="py-3 px-4 text-right">Manual Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white"><?= e($t['title'] ?? $t['name']) ?></div>
                                <div class="text-[11px] text-slate-400 mt-0.5"><?= e($t['description'] ?? '') ?></div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-indigo-400 text-xs"><code><?= e($t['cron_expression'] ?? $t['expression'] ?? '* * * * *') ?></code></td>
                            <td class="py-3.5 px-4">
                                <?php 
                                $status = $t['last_status'] ?? $t['last_run_status'] ?? 'pending';
                                if ($status === 'success'): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">SUCCESS</span>
                                <?php elseif ($status === 'failed'): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/20">FAILED</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-400">PENDING</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]"><?= !empty($t['last_run_at']) ? substr($t['last_run_at'], 0, 16) : 'Never' ?></td>
                            <?php 
                            $duration = $t['last_duration'] ?? ($t['last_duration_seconds'] ?? null); 
                            ?>
                            <td class="py-3.5 px-4 font-mono text-slate-300"><?= $duration !== null ? number_format((float)$duration, 2) . 's' : '-' ?></td>
                            <td class="py-3.5 px-4 text-center">
                                <?php $isActive = !empty($t['is_enabled'] ?? $t['is_active']); ?>
                                <span class="w-2 h-2 rounded-full inline-block <?= $isActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600' ?>"></span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <form action="/admin/cron/<?= $t['id'] ?>/trigger" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="px-3 py-1 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white rounded-lg text-xs font-semibold transition">
                                        Run Now
                                    </button>
                                </form>
                                <form action="/admin/cron/<?= $t['id'] ?>/toggle" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold transition border border-slate-700">
                                        <?= $isActive ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Execution Logs -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800">
            <h2 class="text-sm font-bold text-white">Recent Execution Logs</h2>
        </div>

        <?php if (empty($logs)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No execution logs recorded yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-slate-950 border-b border-slate-800">
                        <tr class="text-slate-400">
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Worker Task</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Duration</th>
                            <th class="py-3 px-4">Execution Summary / Output</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="py-2 px-4 font-mono text-slate-400 text-[11px]"><?= e($l['created_at']) ?></td>
                                <td class="py-2 px-4 font-mono text-indigo-400 font-semibold"><?= e($l['task_name']) ?></td>
                                <td class="py-2 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $l['status'] === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <?= strtoupper(e($l['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4 font-mono text-slate-400"><?= number_format((float)$l['duration_seconds'], 3) ?>s</td>
                                <td class="py-2 px-4 text-slate-300 truncate max-w-md"><?= e($l['output'] ?: 'OK') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
