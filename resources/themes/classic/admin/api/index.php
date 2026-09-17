<?php
$api_users = $api_users ?? [];
?>
<div class="space-y-6 max-w-7xl mx-auto" id="admin-api-management">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Developers</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">API Consumers & Key Management</h1>
            <p class="text-sm text-slate-400 mt-1">Monitor active customer API keys, request volumes, and rate limiting status.</p>
        </div>
    </div>

    <!-- Active API Keys Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Registered API Consumers</h2>
            <span class="text-xs text-slate-400"><?= count($api_users) ?> developers</span>
        </div>

        <?php if (empty($api_users)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">No users have generated API keys yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">API Key Hash</th>
                            <th class="py-3 px-4 text-center">Lifetime Orders</th>
                            <th class="py-3 px-4 text-right">Balance</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($api_users as $u): ?>
                            <tr>
                                <td class="py-3 px-4 font-semibold text-white">
                                    <a href="/admin/users/<?= $u['id'] ?>" class="hover:text-indigo-400 transition">
                                        <?= e($u['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-400"><?= e($u['email']) ?></td>
                                <td class="py-3 px-4 font-mono text-slate-300">
                                    <?= substr($u['api_key'], 0, 8) ?>••••••••<?= substr($u['api_key'], -6) ?>
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-indigo-400"><?= number_format($u['orders_count'] ?? 0) ?></td>
                                <td class="py-3 px-4 text-right font-mono text-emerald-400 font-bold">₹<?= number_format((float)$u['balance'], 2) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <form action="/admin/api/keys/<?= $u['id'] ?>/revoke" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" onclick="return confirm('Revoke this user API key immediately?')" class="px-2.5 py-1 bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 border border-rose-600/20 rounded-lg text-xs transition">
                                            Revoke Key
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
