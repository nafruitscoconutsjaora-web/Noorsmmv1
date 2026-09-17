<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Support Tickets Desk</h1>
            <p class="text-xs text-slate-400 mt-1">Manage client queries, refund disputes, and technical support requests.</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="bg-slate-900 border border-slate-800 p-3 rounded-2xl flex items-center gap-1 overflow-x-auto text-xs">
        <?php foreach (['all' => 'All Tickets', 'open' => 'Open', 'answered' => 'Answered', 'closed' => 'Closed'] as $st => $label): ?>
            <a href="/admin/tickets?status=<?= $st ?>"
                class="px-3.5 py-1.5 rounded-lg font-semibold transition <?= $current_status === $st ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-750' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Tickets Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Ticket ID</th>
                        <th class="py-3.5 px-4">Subject</th>
                        <th class="py-3.5 px-4">User</th>
                        <th class="py-3.5 px-4">Priority</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Updated</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($tickets['items'])): ?>
                        <tr><td colspan="7" class="py-12 text-center text-slate-500">No support tickets found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets['items'] as $t): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-rose-400">#<?= $t['id'] ?></td>
                                <td class="py-3.5 px-4 font-semibold text-white max-w-[200px] truncate">
                                    <a href="/admin/tickets/<?= $t['id'] ?>" class="hover:text-rose-400 transition">
                                        <?= e($t['subject']) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-4">
                                    <a href="/admin/users/<?= $t['user_id'] ?>" class="text-indigo-400 hover:underline">
                                        <?= e($t['username'] ?? 'User #' . $t['user_id']) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($t['priority'] === 'high'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">High</span>
                                    <?php elseif ($t['priority'] === 'medium'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">Medium</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-400">Low</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($t['status'] === 'open'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Open</span>
                                    <?php elseif ($t['status'] === 'answered'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">Answered</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-400">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400 whitespace-nowrap"><?= date('M d, H:i', strtotime($t['updated_at'])) ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/admin/tickets/<?= $t['id'] ?>" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-750 text-slate-200 text-xs font-semibold transition">
                                        Respond &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($tickets['last_page'] > 1): ?>
            <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <div>Page <?= $tickets['page'] ?> of <?= $tickets['last_page'] ?> (<?= $tickets['total'] ?> total)</div>
                <div class="flex items-center gap-2">
                    <?php if ($tickets['page'] > 1): ?>
                        <a href="/admin/tickets?page=<?= $tickets['page'] - 1 ?>&status=<?= e($current_status) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Prev</a>
                    <?php endif; ?>
                    <?php if ($tickets['page'] < $tickets['last_page']): ?>
                        <a href="/admin/tickets?page=<?= $tickets['page'] + 1 ?>&status=<?= e($current_status) ?>" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
