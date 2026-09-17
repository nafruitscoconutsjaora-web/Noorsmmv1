<div class="space-y-6 max-w-6xl mx-auto" id="refill-cancellation-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/orders" class="hover:text-white transition">&larr; Orders</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Refill & Cancellation Requests</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Refill & Cancellation Logs</h1>
            <p class="text-sm text-slate-400 mt-1">Track the status of your drop refill requests and cancellation requests.</p>
        </div>
    </div>

    <!-- Refill Requests Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🔄</span> Refill Requests
            </h2>
            <span class="text-xs text-slate-400"><?= count($refills) ?> requests</span>
        </div>

        <?php if (empty($refills)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No refill requests submitted yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Refill ID</th>
                            <th class="py-3 px-4">Order ID</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Link</th>
                            <th class="py-3 px-4">Submitted Date</th>
                            <th class="py-3 px-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($refills as $rf): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-400">#<?= $rf['id'] ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-indigo-400">
                                    <a href="/orders/<?= $rf['order_id'] ?>/tracking" class="hover:underline">#<?= $rf['order_id'] ?></a>
                                </td>
                                <td class="py-3 px-4 text-white font-medium max-w-xs truncate"><?= e($rf['service_name']) ?></td>
                                <td class="py-3 px-4 text-slate-400 font-mono text-[11px] max-w-xs truncate"><?= e($rf['link']) ?></td>
                                <td class="py-3 px-4 text-slate-400 font-mono text-[11px]"><?= substr($rf['created_at'], 0, 16) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?= $rf['status'] === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($rf['status'] === 'rejected' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') ?>">
                                        <?= strtoupper(e($rf['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancellation Requests Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span>⛔</span> Cancellation Requests
            </h2>
            <span class="text-xs text-slate-400"><?= count($cancellations) ?> requests</span>
        </div>

        <?php if (empty($cancellations)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No cancellation requests submitted yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Req ID</th>
                            <th class="py-3 px-4">Order ID</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Reason</th>
                            <th class="py-3 px-4">Submitted Date</th>
                            <th class="py-3 px-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($cancellations as $c): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-400">#<?= $c['id'] ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-indigo-400">
                                    <a href="/orders/<?= $c['order_id'] ?>/tracking" class="hover:underline">#<?= $c['order_id'] ?></a>
                                </td>
                                <td class="py-3 px-4 text-white font-medium max-w-xs truncate"><?= e($c['service_name']) ?></td>
                                <td class="py-3 px-4 text-slate-400 max-w-xs truncate"><?= e($c['reason'] ?: 'None provided') ?></td>
                                <td class="py-3 px-4 text-slate-400 font-mono text-[11px]"><?= substr($c['created_at'], 0, 16) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?= $c['status'] === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($c['status'] === 'rejected' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') ?>">
                                        <?= strtoupper(e($c['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
