<div class="space-y-6 max-w-5xl mx-auto" id="order-tracking-view">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <div>
            <div class="flex items-center gap-3">
                <a href="/orders" class="text-slate-400 hover:text-white text-sm transition">&larr; All Orders</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs font-mono text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">Order #<?= $order['id'] ?></span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-2"><?= e($order['service_name']) ?></h1>
            <p class="text-sm text-slate-400 mt-1">Target Link: <span class="font-mono text-slate-300 break-all"><?= e($order['link']) ?></span></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span id="order-status-badge" class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider <?= order_status_badge($order['status']) ?>">
                <?= strtoupper(e($order['status'])) ?>
            </span>
            <span class="text-xs text-slate-500 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Auto-refreshing live
            </span>
        </div>
    </div>

    <!-- Live Progress & Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-5">
            <div class="text-xs font-medium text-slate-400">Total Quantity</div>
            <div class="text-2xl font-bold text-white mt-1"><?= number_format($order['quantity']) ?></div>
            <div class="text-xs text-slate-500 mt-2">Ordered units</div>
        </div>
        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-5">
            <div class="text-xs font-medium text-slate-400">Start Counter</div>
            <div class="text-2xl font-bold text-slate-200 mt-1" id="val-start-counter">
                <?= $order['start_counter'] !== null ? number_format($order['start_counter']) : '<span class="text-slate-500 text-lg">Pending</span>' ?>
            </div>
            <div class="text-xs text-slate-500 mt-2">Initial link metric</div>
        </div>
        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-5">
            <div class="text-xs font-medium text-slate-400">Remains</div>
            <div class="text-2xl font-bold text-indigo-400 mt-1" id="val-remains">
                <?= $order['remains'] !== null ? number_format($order['remains']) : '<span class="text-slate-500 text-lg">Estimating</span>' ?>
            </div>
            <div class="text-xs text-slate-500 mt-2">Quantity pending</div>
        </div>
        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-5">
            <div class="text-xs font-medium text-slate-400">Total Charged</div>
            <div class="text-2xl font-bold text-emerald-400 mt-1">₹<?= number_format((float)$order['charge'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-2">Deducted from balance</div>
        </div>
    </div>

    <!-- Visual Progress Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex items-center justify-between text-sm mb-2">
            <span class="font-semibold text-slate-300">Delivery Completion Progress</span>
            <span class="font-bold text-indigo-400" id="progress-text"><?= $progress ?>%</span>
        </div>
        <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden">
            <div id="progress-fill" class="bg-gradient-to-r from-indigo-500 to-emerald-500 h-3 rounded-full transition-all duration-500" style="width: <?= $progress ?>%"></div>
        </div>
        <div class="flex justify-between text-[11px] text-slate-500 mt-2">
            <span>Submitted</span>
            <span>Accepted</span>
            <span>In Delivery</span>
            <span>Completed</span>
        </div>
    </div>

    <!-- Timeline & Events -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-base font-bold text-white mb-6 flex items-center gap-2">
                <span>🕒</span> Order Status Timeline & Event Logs
            </h2>

            <?php if (empty($history)): ?>
                <div class="relative pl-6 border-l-2 border-slate-800 space-y-6">
                    <div class="relative">
                        <div class="absolute -left-[31px] top-0 w-3.5 h-3.5 rounded-full bg-indigo-500 ring-4 ring-slate-900"></div>
                        <div class="text-sm font-semibold text-white">Order Created & Queued</div>
                        <div class="text-xs text-slate-400 mt-0.5"><?= e($order['created_at']) ?></div>
                        <div class="text-xs text-slate-500 mt-1">Order successfully registered in the platform routing dispatch.</div>
                    </div>
                    <?php if ($order['status'] !== 'pending'): ?>
                        <div class="relative">
                            <div class="absolute -left-[31px] top-0 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-4 ring-slate-900"></div>
                            <div class="text-sm font-semibold text-white">Status Updated: <?= strtoupper(e($order['status'])) ?></div>
                            <div class="text-xs text-slate-400 mt-0.5"><?= e($order['updated_at']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="relative pl-6 border-l-2 border-slate-800 space-y-6">
                    <?php foreach ($history as $idx => $ev): ?>
                        <div class="relative">
                            <div class="absolute -left-[31px] top-0 w-3.5 h-3.5 rounded-full <?= $idx === count($history)-1 ? 'bg-emerald-500 animate-pulse' : 'bg-indigo-500' ?> ring-4 ring-slate-900"></div>
                            <div class="text-sm font-semibold text-white flex items-center gap-2">
                                <span><?= strtoupper(e($ev['status'])) ?></span>
                                <span class="text-[11px] font-mono text-slate-500"><?= e($ev['created_at']) ?></span>
                            </div>
                            <?php if (!empty($ev['description'])): ?>
                                <div class="text-xs text-slate-400 mt-1"><?= e($ev['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Conditional Actions & Refill/Cancel options -->
        <div class="space-y-6">
            <!-- Order Refill Panel -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h3 class="text-sm font-bold text-white mb-2 flex items-center gap-2">
                    <span>🔄</span> Refill Protection
                </h3>
                <?php if ($order['service_refill']): ?>
                    <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                        This service includes automated drop refill warranty. If your count drops, request a refill below.
                    </p>
                    <form action="/orders/<?= $order['id'] ?>/refill" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                            Submit Refill Request
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-3 bg-slate-800/60 rounded-xl border border-slate-700 text-xs text-slate-400">
                        Refill is not guaranteed for this service tier by provider.
                    </div>
                <?php endif; ?>

                <?php if (!empty($refills)): ?>
                    <div class="mt-4 pt-4 border-t border-slate-800 space-y-2">
                        <div class="text-xs font-semibold text-slate-300">Refill History</div>
                        <?php foreach ($refills as $rf): ?>
                            <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-800/40">
                                <span class="text-slate-400"><?= substr($rf['created_at'], 0, 16) ?></span>
                                <span class="font-bold text-indigo-400 uppercase"><?= e($rf['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cancellation Panel -->
            <?php if ($order['service_cancel'] && !in_array($order['status'], ['completed', 'cancelled', 'refunded'])): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <h3 class="text-sm font-bold text-white mb-2 flex items-center gap-2">
                        <span>⛔</span> Order Cancellation
                    </h3>
                    <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                        Cancel this order before provider completion. Any undelivered balance is credited back to your wallet.
                    </p>
                    <form action="/orders/<?= $order['id'] ?>/cancel-request" method="POST">
                        <?= csrf_field() ?>
                        <input type="text" name="reason" placeholder="Reason (optional)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white mb-3 placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                        <button type="submit" class="w-full py-2.5 px-4 bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-600/30 text-xs font-semibold rounded-xl transition">
                            Request Cancellation
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Live Auto-Refresh polling every 10 seconds
    (function() {
        const orderId = <?= (int)$order['id'] ?>;
        const currentStatus = '<?= e($order['status']) ?>';
        
        if (currentStatus !== 'completed' && currentStatus !== 'cancelled' && currentStatus !== 'failed') {
            setInterval(function() {
                fetch('/api/orders/' + orderId + '/tracking')
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.status) {
                            const badge = document.getElementById('order-status-badge');
                            if (badge) {
                                badge.textContent = data.status.toUpperCase();
                                badge.className = 'px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider ' + data.badge_class;
                            }
                            if (data.start_counter !== null) {
                                document.getElementById('val-start-counter').textContent = Number(data.start_counter).toLocaleString();
                            }
                            if (data.remains !== null) {
                                document.getElementById('val-remains').textContent = Number(data.remains).toLocaleString();
                            }
                            if (data.progress !== undefined) {
                                document.getElementById('progress-text').textContent = data.progress + '%';
                                document.getElementById('progress-fill').style.width = data.progress + '%';
                            }
                        }
                    })
                    .catch(err => console.debug('Polling live tracking...', err));
            }, 10000);
        }
    })();
</script>
