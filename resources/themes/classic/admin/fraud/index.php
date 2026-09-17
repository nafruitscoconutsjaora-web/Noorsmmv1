<div class="space-y-6 max-w-6xl mx-auto" id="admin-fraud-monitor">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Security & Abuse</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Fraud Detection & Abuse Prevention</h1>
            <p class="text-sm text-slate-400 mt-1">Identify suspicious velocity spikes, rapid failed transactions, and multi-accounting risks.</p>
        </div>
    </div>

    <!-- Fraud Alerts Banner -->
    <div class="bg-gradient-to-r from-rose-950/40 via-slate-900 to-slate-900 border border-rose-500/30 rounded-2xl p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-xl border border-rose-500/20 shrink-0">
                🚨
            </div>
            <div>
                <h2 class="text-base font-bold text-white">Automated Risk Heuristics Active</h2>
                <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                    The fraud engine automatically monitors high-frequency order placement (>10 orders/min), failed payment retry bursts, and blacklisted keywords.
                </p>
            </div>
        </div>
    </div>

    <!-- Suspicious Activities Log -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Flagged Suspicious Activity Logs</h2>
            <span class="text-xs text-slate-400"><?= count($flagged_items) ?> events flagged</span>
        </div>

        <?php if (empty($flagged_items)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">
                No high-risk security incidents or abusive behaviors flagged currently. System operating normally.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Detection Heuristic</th>
                            <th class="py-3 px-4">Risk Severity</th>
                            <th class="py-3 px-4">Target / Metadata</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($flagged_items as $f): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-400 text-[11px]"><?= substr($f['created_at'], 0, 16) ?></td>
                                <td class="py-3 px-4 font-semibold text-white">
                                    <a href="/admin/users/<?= $f['user_id'] ?>" class="hover:text-indigo-400">
                                        <?= e($f['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-200"><?= e($f['rule_triggered']) ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $f['severity'] === 'high' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' ?>">
                                        <?= strtoupper(e($f['severity'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-400 text-[11px] max-w-xs truncate"><?= e($f['metadata']) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <form action="/admin/users/<?= $f['user_id'] ?>/toggle-status" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" onclick="return confirm('Suspend user account immediately?')" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold transition">
                                            Suspend User
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
