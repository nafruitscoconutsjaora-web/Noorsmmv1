<div class="space-y-6 max-w-4xl mx-auto" id="notification-center">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Notifications & Alerts</h1>
            <p class="text-sm text-slate-400 mt-1">Platform announcements, order progress updates, and payment notifications.</p>
        </div>
        <form action="/notifications/read-all" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 border border-slate-700 transition">
                Mark All as Read
            </button>
        </form>
    </div>

    <!-- Category Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-800 text-xs">
        <a href="/notifications" class="px-3.5 py-1.5 rounded-lg font-medium whitespace-nowrap transition <?= empty($type) && !$unread_only ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white bg-slate-900' ?>">
            All (<?= $total ?>)
        </a>
        <a href="/notifications?unread=1" class="px-3.5 py-1.5 rounded-lg font-medium whitespace-nowrap transition <?= $unread_only ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white bg-slate-900' ?>">
            Unread
        </a>
        <a href="/notifications?type=order" class="px-3.5 py-1.5 rounded-lg font-medium whitespace-nowrap transition <?= $type === 'order' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white bg-slate-900' ?>">
            Order Updates
        </a>
        <a href="/notifications?type=wallet" class="px-3.5 py-1.5 rounded-lg font-medium whitespace-nowrap transition <?= $type === 'wallet' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white bg-slate-900' ?>">
            Payments & Balance
        </a>
        <a href="/notifications?type=system" class="px-3.5 py-1.5 rounded-lg font-medium whitespace-nowrap transition <?= $type === 'system' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white bg-slate-900' ?>">
            System Announcements
        </a>
    </div>

    <!-- Notifications List -->
    <div class="space-y-3">
        <?php if (empty($notifications)): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center">
                <div class="text-3xl mb-3">🔔</div>
                <div class="text-sm font-semibold text-white">No notifications found</div>
                <div class="text-xs text-slate-500 mt-1">You're all caught up with your platform updates.</div>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="p-5 rounded-2xl border transition flex items-start justify-between gap-4 <?= $n['is_read'] ? 'bg-slate-900/60 border-slate-800/80 text-slate-300' : 'bg-slate-900 border-indigo-500/30 text-white shadow-sm ring-1 ring-indigo-500/10' ?>">
                    <div class="flex items-start gap-3.5">
                        <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center text-sm <?= $n['type'] === 'order' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : ($n['type'] === 'wallet' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') ?>">
                            <?= $n['type'] === 'order' ? '📦' : ($n['type'] === 'wallet' ? '💰' : '📢') ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-white"><?= e($n['title']) ?></h3>
                                <?php if (!$n['is_read']): ?>
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed"><?= nl2br(e($n['message'])) ?></p>
                            <span class="text-[11px] font-mono text-slate-500 mt-2 block"><?= e($n['created_at']) ?></span>
                        </div>
                    </div>

                    <?php if (!$n['is_read']): ?>
                        <form action="/notifications/<?= $n['id'] ?>/read" method="POST" class="shrink-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-xs text-slate-400 hover:text-indigo-400 transition" title="Mark as read">
                                &check; Mark Read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pt-4 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Page <?= $page ?> of <?= $total_pages ?></span>
                    <div class="flex items-center gap-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&type=<?= urlencode($type ?? '') ?>" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white transition">&larr; Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&type=<?= urlencode($type ?? '') ?>" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white transition">Next &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
