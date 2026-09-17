<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="/admin/orders" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-2">
            &larr; Back to All Orders
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-white tracking-tight">Order #<?= $order['id'] ?></h1>
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider <?= order_status_badge($order['status']) ?>">
                    <?= e(str_replace('_', ' ', $order['status'])) ?>
                </span>
            </div>

            <?php if (!empty($order['provider_id'])): ?>
                <form action="/admin/orders/<?= $order['id'] ?>/retry-provider" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 transition flex items-center gap-2">
                        <span>⚡ Re-dispatch to Provider</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Metadata & Breakdown Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Order Specification -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white pb-3 border-b border-slate-800">Order Specifications</h3>

            <div class="space-y-2.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">Target Link:</span>
                    <a href="<?= e($order['link']) ?>" target="_blank" class="font-mono text-rose-400 hover:underline max-w-[220px] truncate"><?= e($order['link']) ?></a>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Service:</span>
                    <span class="font-semibold text-white max-w-[220px] truncate"><?= e($order['service_name'] ?? 'Service #' . $order['service_id']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Order Quantity:</span>
                    <span class="font-mono font-bold text-white"><?= number_format((int)$order['quantity']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Client Charged:</span>
                    <span class="font-mono font-bold text-emerald-400">₹<?= number_format((float)$order['charge'], 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Start Counter:</span>
                    <span class="font-mono text-slate-300"><?= $order['start_counter'] ?? '-' ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Remains:</span>
                    <span class="font-mono text-slate-300"><?= $order['remains'] ?? '-' ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Placed By:</span>
                    <a href="/admin/users/<?= $order['user_id'] ?>" class="text-indigo-400 hover:underline font-semibold"><?= e($order['username'] ?? 'User #' . $order['user_id']) ?></a>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Source:</span>
                    <span class="font-mono text-slate-300 uppercase"><?= e($order['source'] ?? 'web') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Created At:</span>
                    <span class="text-slate-300"><?= date('M d, Y H:i:s', strtotime($order['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Provider Dispatch Status & Manual Override -->
        <div class="space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <h3 class="text-sm font-bold text-white pb-3 border-b border-slate-800">Upstream Provider Info</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Provider Name:</span>
                        <span class="font-semibold text-white"><?= e($order['provider_name'] ?? 'Direct / Manual') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Remote Order ID:</span>
                        <span class="font-mono text-indigo-400 font-semibold"><?= e($order['provider_order_id'] ?? 'Not sent') ?></span>
                    </div>
                    <?php if (!empty($order['provider_error'])): ?>
                        <div class="p-2.5 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
                            <strong>Last Error:</strong> <?= e($order['provider_error']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Manual Status Modification Form -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <h3 class="text-sm font-bold text-white pb-3 border-b border-slate-800">Manual Status Override</h3>

                <form action="/admin/orders/<?= $order['id'] ?>/status" method="POST" class="space-y-3">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Set New Status</label>
                        <select name="status" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                            <?php foreach (['pending', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'failed'] as $st): ?>
                                <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Staff Note</label>
                        <input type="text" name="note" placeholder="Reason for change..."
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition">
                        Update Status (Refunds user if Cancelled)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Audit Log Timeline -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <h3 class="text-sm font-bold text-white pb-3 border-b border-slate-800 mb-4">Lifecycle Timeline</h3>
        <div class="space-y-3">
            <?php if (empty($history)): ?>
                <div class="text-xs text-slate-500 py-2">No historical transition records for this order.</div>
            <?php else: ?>
                <?php foreach ($history as $h): ?>
                    <div class="flex items-start gap-3 text-xs">
                        <div class="w-2 h-2 rounded-full bg-rose-500 mt-1.5 shrink-0"></div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-white uppercase tracking-wider text-[11px]"><?= e(str_replace('_', ' ', $h['new_status'])) ?></span>
                                <span class="text-slate-400 text-[10px]"><?= date('M d, H:i:s', strtotime($h['created_at'])) ?></span>
                            </div>
                            <?php if (!empty($h['note'])): ?>
                                <p class="text-slate-300 mt-0.5"><?= e($h['note']) ?></p>
                            <?php endif; ?>
                            <div class="text-[10px] text-slate-500 mt-0.5">Author: <?= e($h['changed_by'] ?? 'system') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
