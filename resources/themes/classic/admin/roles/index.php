<div class="space-y-6 max-w-6xl mx-auto" id="admin-rbac-roles">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Staff Permissions</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Staff Roles & Permissions (RBAC)</h1>
            <p class="text-sm text-slate-400 mt-1">Assign granular administrative roles (Super Admin, Support Representative, Financial Manager).</p>
        </div>
    </div>

    <!-- Roles Matrix -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">ROLE</span>
                <span class="text-xs text-slate-500 font-mono">Tier 1</span>
            </div>
            <h3 class="text-lg font-bold text-white">Super Administrator</h3>
            <p class="text-xs text-slate-400 mt-1">Full system privileges including API keys, database backups, financial reconciliations, and role management.</p>
            <div class="mt-4 pt-4 border-t border-slate-800 text-xs text-slate-300 space-y-1">
                <div>✔ All Modules & Settings</div>
                <div>✔ Wallet & Balance Adjustments</div>
                <div>✔ System Backup & Crons</div>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">ROLE</span>
                <span class="text-xs text-slate-500 font-mono">Tier 2</span>
            </div>
            <h3 class="text-lg font-bold text-white">Support Agent</h3>
            <p class="text-xs text-slate-400 mt-1">Limited operational privileges to reply to tickets, review customer orders, and initiate refills.</p>
            <div class="mt-4 pt-4 border-t border-slate-800 text-xs text-slate-300 space-y-1">
                <div>✔ Support Ticket Handling</div>
                <div>✔ Order Status Tracking</div>
                <div>✖ No Financial Adjustments</div>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">ROLE</span>
                <span class="text-xs text-slate-500 font-mono">Tier 2</span>
            </div>
            <h3 class="text-lg font-bold text-white">Financial Auditor</h3>
            <p class="text-xs text-slate-400 mt-1">Review deposit logs, gateway reconciliations, and generate accounting CSV reports.</p>
            <div class="mt-4 pt-4 border-t border-slate-800 text-xs text-slate-300 space-y-1">
                <div>✔ Payment Reconciliation</div>
                <div>✔ Financial Reports Download</div>
                <div>✖ No API Key Access</div>
            </div>
        </div>
    </div>

    <!-- Administrative Staff Members Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Staff Users with Privileged Access</h2>
            <span class="text-xs text-slate-400"><?= count($staff_users) ?> staff accounts</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                        <th class="py-3 px-4">Staff Username</th>
                        <th class="py-3 px-4">Email Address</th>
                        <th class="py-3 px-4">Current Role</th>
                        <th class="py-3 px-4 text-right">Update Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    <?php foreach ($staff_users as $su): ?>
                        <tr>
                            <td class="py-3.5 px-4 font-bold text-white"><?= e($su['username']) ?></td>
                            <td class="py-3.5 px-4 text-slate-400"><?= e($su['email']) ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    <?= strtoupper(e($su['role'])) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <form action="/admin/roles/assign" method="POST" class="inline-flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= $su['id'] ?>">
                                    <select name="role" class="bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1 text-xs text-white focus:outline-none">
                                        <option value="admin" <?= $su['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="support" <?= $su['role'] === 'support' ? 'selected' : '' ?>>Support</option>
                                        <option value="finance" <?= $su['role'] === 'finance' ? 'selected' : '' ?>>Finance</option>
                                        <option value="user">Demote to User</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold transition">
                                        Save
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
