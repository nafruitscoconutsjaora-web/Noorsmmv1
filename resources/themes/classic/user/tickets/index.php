<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Support Tickets</h1>
            <p class="text-xs text-slate-400 mt-1">Direct priority communication channel with our operations team.</p>
        </div>
        <a href="/tickets/new" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 transition">
            + Open New Ticket
        </a>
    </div>

    <!-- Tickets List Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Ticket ID</th>
                        <th class="py-3.5 px-4">Subject</th>
                        <th class="py-3.5 px-4">Priority</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Last Updated</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($tickets['items'])): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                No support tickets submitted yet. Have a question about an order? Open a ticket above!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets['items'] as $t): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-400">#<?= $t['id'] ?></td>
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    <a href="/tickets/<?= $t['id'] ?>" class="hover:text-indigo-400 transition"><?= e($t['subject']) ?></a>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($t['priority'] === 'high'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">High</span>
                                    <?php elseif ($t['priority'] === 'medium'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">Medium</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-300">Low</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($t['status'] === 'open'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Open</span>
                                    <?php elseif ($t['status'] === 'answered'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">Answered</span>
                                    <?php elseif ($t['status'] === 'customer_reply'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">Customer Reply</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-400">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400"><?= date('M d, H:i', strtotime($t['updated_at'])) ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/tickets/<?= $t['id'] ?>" class="inline-flex items-center px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
                                        View &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
