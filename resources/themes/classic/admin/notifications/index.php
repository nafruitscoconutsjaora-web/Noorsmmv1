<div class="space-y-6 max-w-6xl mx-auto" id="admin-notification-center">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Broadcasting</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Platform Notifications & Announcements</h1>
            <p class="text-sm text-slate-400 mt-1">Broadcast system alerts, service maintenance notices, or targeted updates to customers.</p>
        </div>
    </div>

    <!-- Create Broadcast Form -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-base font-bold text-white mb-2">Send Notification / Announcement</h2>
        <p class="text-xs text-slate-400 mb-5">Broadcast across in-app notification center and dashboard banners.</p>

        <form action="/admin/notifications/send" method="POST" class="space-y-4 max-w-2xl">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Recipient Audience</label>
                    <select name="target" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="all">All Registered Users</option>
                        <option value="active">Active Buyers Only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Alert Level</label>
                    <select name="type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="info">Information (Blue)</option>
                        <option value="success">Success / Promo (Green)</option>
                        <option value="warning">Warning / Maintenance (Amber)</option>
                        <option value="urgent">Urgent / Action Required (Red)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Announcement Title</label>
                <input type="text" name="title" required placeholder="e.g. Scheduled Provider Maintenance" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Message Content</label>
                <textarea name="message" rows="3" required placeholder="Enter announcement body text..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                Dispatch Broadcast
            </button>
        </form>
    </div>

    <!-- Sent Announcements Log -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800">
            <h2 class="text-sm font-bold text-white">Broadcast History</h2>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No notifications broadcasted yet.</div>
        <?php else: ?>
            <div class="divide-y divide-slate-800/60">
                <?php foreach ($notifications as $n): ?>
                    <div class="p-5 hover:bg-slate-800/20 transition flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $n['type'] === 'urgent' ? 'bg-rose-500/10 text-rose-400' : ($n['type'] === 'warning' ? 'bg-amber-500/10 text-amber-400' : 'bg-indigo-500/10 text-indigo-400') ?>">
                                    <?= strtoupper(e($n['type'])) ?>
                                </span>
                                <h3 class="text-sm font-bold text-white"><?= e($n['title']) ?></h3>
                            </div>
                            <p class="text-xs text-slate-300"><?= e($n['message']) ?></p>
                            <div class="text-[11px] text-slate-500 font-mono pt-1">
                                Dispatched: <?= substr($n['created_at'], 0, 16) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
