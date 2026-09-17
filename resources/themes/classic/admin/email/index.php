<div class="space-y-6 max-w-6xl mx-auto" id="admin-email-management">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Email & Communications</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Email Templates & Bulk Communications</h1>
            <p class="text-sm text-slate-400 mt-1">Manage transactional notification emails and broadcast marketing newsletters.</p>
        </div>
    </div>

    <!-- Bulk Email Form -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-base font-bold text-white mb-2">Send Newsletter / Direct Email</h2>
        <p class="text-xs text-slate-400 mb-4">Send an official email to all registered members or a specific user.</p>

        <form action="/admin/email/send" method="POST" class="space-y-4 max-w-2xl">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Audience</label>
                    <select name="target" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                        <option value="all">All Members</option>
                        <option value="subscribed">Subscribed to Newsletter</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Email Subject</label>
                    <input type="text" name="subject" required placeholder="Important platform update..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Message Content (HTML / Text)</label>
                <textarea name="body" rows="6" required placeholder="Write email message body..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
            </div>
            <button type="submit" onclick="return confirm('Send email to the selected recipient audience?')" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                Dispatch Email Campaign
            </button>
        </form>
    </div>

    <!-- Email Logs -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800">
            <h2 class="text-base font-bold text-white">Email Dispatch History</h2>
        </div>

        <?php if (empty($logs)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No email campaigns dispatched yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Subject</th>
                            <th class="py-3 px-4">Recipients</th>
                            <th class="py-3 px-4">Dispatched At</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white"><?= e($l['subject']) ?></td>
                                <td class="py-3 px-4 text-slate-400 font-mono"><?= number_format($l['recipients_count']) ?> recipients</td>
                                <td class="py-3 px-4 font-mono text-slate-400 text-[11px]"><?= substr($l['created_at'], 0, 16) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        DISPATCHED
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
