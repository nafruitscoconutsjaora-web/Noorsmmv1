<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="/tickets" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-2">
                &larr; Back to Tickets
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-white tracking-tight">Ticket #<?= $ticket['id'] ?>: <?= e($ticket['subject']) ?></h1>
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                    <?= $ticket['status'] === 'open' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($ticket['status'] === 'answered' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-slate-800 text-slate-400') ?>">
                    <?= e(str_replace('_', ' ', $ticket['status'])) ?>
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Priority: <span class="text-slate-200 capitalize font-medium"><?= e($ticket['priority']) ?></span> &bull;
                Opened on: <?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?>
            </p>
        </div>

        <?php if ($ticket['status'] !== 'closed'): ?>
            <form action="/tickets/<?= $ticket['id'] ?>/close" method="POST" onsubmit="return confirm('Close this ticket?');">
                <?= csrf_field() ?>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-rose-500/10 text-slate-300 hover:text-rose-400 border border-slate-700 hover:border-rose-500/20 text-xs font-semibold transition">
                    Close Ticket
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Messages Thread -->
    <div class="space-y-4">
        <?php foreach ($messages as $msg): ?>
            <div class="p-5 rounded-2xl border <?= $msg['sender_type'] === 'admin' ? 'bg-indigo-950/20 border-indigo-500/30' : 'bg-slate-900 border-slate-800' ?>">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800/80 mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold <?= $msg['sender_type'] === 'admin' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300' ?>">
                            <?= $msg['sender_type'] === 'admin' ? '⚡' : '👤' ?>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-white"><?= $msg['sender_type'] === 'admin' ? 'Support Specialist' : 'You' ?></span>
                            <?php if ($msg['sender_type'] === 'admin'): ?>
                                <span class="ml-1.5 px-1.5 py-0.2 rounded bg-indigo-500/10 text-indigo-400 text-[10px] font-semibold">Staff</span>
                            <?php endif; ?>
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

    <!-- Reply Box -->
    <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="text-sm font-bold text-white mb-3">Post a Reply</h3>
            <form action="/tickets/<?= $ticket['id'] ?>/reply" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <textarea name="message" rows="4" required placeholder="Type your response or additional information here..."
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"></textarea>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/20 transition">
                    Send Reply
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 text-center text-xs text-slate-400">
            This ticket has been resolved and closed. If you require further assistance, please <a href="/tickets/new" class="text-indigo-400 hover:underline">open a new ticket</a>.
        </div>
    <?php endif; ?>
</div>
