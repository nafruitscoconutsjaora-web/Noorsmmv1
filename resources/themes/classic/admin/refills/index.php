<div class="space-y-6 max-w-7xl mx-auto" id="admin-refills-cancellations">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin/orders" class="hover:text-white transition">&larr; Orders</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Refill & Cancel Processing</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Refill & Cancellation Request Queues</h1>
            <p class="text-sm text-slate-400 mt-1">Review customer refill and cancellation requests, forward to API providers, or manually resolve.</p>
        </div>
    </div>

    <!-- Refill Requests Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>🔄</span> Pending & Past Refills
            </h2>
            <span class="text-xs text-slate-400"><?= count($refills) ?> total requests</span>
        </div>

        <?php if (empty($refills)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No refill requests in queue.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Req #</th>
                            <th class="py-3 px-4">Order #</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Provider</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($refills as $rf): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-400">#<?= $rf['id'] ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-indigo-400">
                                    <a href="/admin/orders?search=<?= $rf['order_id'] ?>" class="hover:underline">#<?= $rf['order_id'] ?></a>
                                </td>
                                <td class="py-3 px-4 font-semibold text-white"><?= e($rf['username']) ?></td>
                                <td class="py-3 px-4 max-w-xs truncate"><?= e($rf['service_name']) ?></td>
                                <td class="py-3 px-4 text-slate-400"><?= e($rf['provider_name'] ?: 'Direct') ?></td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-400"><?= substr($rf['created_at'], 0, 16) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $rf['status'] === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($rf['status'] === 'rejected' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') ?>">
                                        <?= strtoupper(e($rf['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-1">
                                    <?php if ($rf['status'] === 'pending'): ?>
                                        <form action="/admin/refills/<?= $rf['id'] ?>/approve" method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="/admin/refills/<?= $rf['id'] ?>/reject" method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="px-2.5 py-1 bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white rounded-lg text-xs font-semibold transition border border-rose-600/30">
                                                Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-[11px]">Processed</span>
                                    <?php endif; ?>
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
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>⛔</span> Cancellation Requests
            </h2>
            <span class="text-xs text-slate-400"><?= count($cancellations) ?> total requests</span>
        </div>

        <?php if (empty($cancellations)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No cancellation requests in queue.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Req #</th>
                            <th class="py-3 px-4">Order #</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Reason</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($cancellations as $c): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-400">#<?= $c['id'] ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-indigo-400">
                                    <a href="/admin/orders?search=<?= $c['order_id'] ?>" class="hover:underline">#<?= $c['order_id'] ?></a>
                                </td>
                                <td class="py-3 px-4 font-semibold text-white"><?= e($c['username']) ?></td>
                                <td class="py-3 px-4 text-slate-400 max-w-xs truncate"><?= e($c['reason'] ?: 'No reason stated') ?></td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-400"><?= substr($c['created_at'], 0, 16) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $c['status'] === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($c['status'] === 'rejected' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') ?>">
                                        <?= strtoupper(e($c['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-1">
                                    <?php if ($c['status'] === 'pending'): ?>
                                        <form action="/admin/cancellations/<?= $c['id'] ?>/approve" method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" onclick="return confirm('Cancel order and refund remaining amount to user?')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold transition">
                                                Cancel & Refund
                                            </button>
                                        </form>
                                        <form action="/admin/cancellations/<?= $c['id'] ?>/reject" method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="px-2.5 py-1 bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white rounded-lg text-xs font-semibold transition border border-rose-600/30">
                                                Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-[11px]">Handled</span>
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
