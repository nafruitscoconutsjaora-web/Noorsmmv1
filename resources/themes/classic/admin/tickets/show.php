<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="/admin/tickets" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-2">
                &larr; Back to Tickets
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-white tracking-tight">Ticket #<?= $ticket['id'] ?>: <?= e($ticket['subject']) ?></h1>
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                    <?= $ticket['status'] === 'open' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($ticket['status'] === 'answered' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-slate-800 text-slate-400') ?>">
                    <?= e($ticket['status']) ?>
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Client: <a href="/admin/users/<?= $ticket['user_id'] ?>" class="text-indigo-400 hover:underline font-semibold"><?= e($ticket['username'] ?? 'User #' . $ticket['user_id']) ?></a> &bull;
                Priority: <span class="capitalize text-slate-200"><?= e($ticket['priority']) ?></span> &bull;
                Opened: <?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?>
            </p>
        </div>

        <form action="/admin/tickets/<?= $ticket['id'] ?>/status" method="POST" class="flex items-center gap-2">
            <?= csrf_field() ?>
            <select name="status" class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="answered" <?= $ticket['status'] === 'answered' ? 'selected' : '' ?>>Answered</option>
                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
                Change Status
            </button>
        </form>
    </div>

    <!-- Message Thread -->
    <div class="space-y-4">
        <?php foreach ($messages as $msg): ?>
            <div class="p-5 rounded-2xl border <?= $msg['sender_type'] === 'admin' ? 'bg-rose-950/20 border-rose-500/30' : 'bg-slate-900 border-slate-800' ?>">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800/80 mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold <?= $msg['sender_type'] === 'admin' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300' ?>">
                            <?= $msg['sender_type'] === 'admin' ? '🛡️' : '👤' ?>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-white"><?= $msg['sender_type'] === 'admin' ? 'Support Administrator' : 'Client User' ?></span>
                            <span class="ml-1.5 text-[10px] text-slate-500"><?= e($msg['sender_type']) ?></span>
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-400"><?= date('M d, H:i', strtotime($msg['created_at'])) ?></span>
                </div>
                <div class="text-xs text-slate-200 leading-relaxed whitespace-pre-line">
                    <?= nl2br(e($msg['message'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Admin Reply Box -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-3">
        <h3 class="text-sm font-bold text-white">Post Official Staff Reply</h3>
        <form action="/admin/tickets/<?= $ticket['id'] ?>/reply" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <textarea name="message" rows="4" required placeholder="Write a resolution, explanation, or request for information..."
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition"></textarea>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-[11px] text-slate-500">Posting a reply will mark the ticket status as 'Answered'.</span>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs shadow-md shadow-rose-600/20 transition">
                    Send Reply
                </button>
            </div>
        </form>
    </div>
</div>
