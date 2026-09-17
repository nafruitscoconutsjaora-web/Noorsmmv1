<div class="space-y-6 max-w-4xl mx-auto" id="account-security-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/account" class="hover:text-white transition">&larr; Account Overview</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Security Settings</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Security & Login Activity</h1>
            <p class="text-sm text-slate-400 mt-1">Review active sessions, login history, and manage your account password.</p>
        </div>
    </div>

    <!-- Password Management Form -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-base font-bold text-white mb-1">Update Account Password</h2>
        <p class="text-xs text-slate-400 mb-6">Ensure your account uses a strong passphrase of 8 or more characters.</p>

        <form action="/account/security/password" method="POST" class="space-y-4 max-w-md">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">New Password</label>
                <input type="password" name="new_password" required minlength="8" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" required minlength="8" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                Save New Password
            </button>
        </form>
    </div>

    <!-- Active Sessions & Security Controls -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-base font-bold text-white">Active Session & Device</h2>
                <p class="text-xs text-slate-400 mt-0.5">Your current browser session and session invalidation controls.</p>
            </div>
            <form action="/account/security/logout-others" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="px-4 py-2 bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 border border-rose-600/20 rounded-xl text-xs font-semibold transition">
                    Logout Other Sessions
                </button>
            </form>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm border border-emerald-500/20">
                    💻
                </div>
                <div>
                    <div class="text-xs font-bold text-white flex items-center gap-2">
                        <span>Current Browser Session</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    </div>
                    <div class="text-[11px] text-slate-400 font-mono mt-0.5"><?= e($_SERVER['HTTP_USER_AGENT'] ?? 'Web Browser') ?></div>
                </div>
            </div>
            <span class="text-xs font-mono text-slate-500">IP: <?= e($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></span>
        </div>
    </div>

    <!-- Login History -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800">
            <h2 class="text-base font-bold text-white">Recent Login History</h2>
            <p class="text-xs text-slate-400 mt-0.5">Records of successful sign-ins to your account.</p>
        </div>

        <?php if (empty($logins)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No past login records found.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Date & Time</th>
                            <th class="py-3 px-4">IP Address</th>
                            <th class="py-3 px-4">Browser & Operating System</th>
                            <th class="py-3 px-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($logins as $l): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono text-slate-300"><?= e($l['created_at']) ?></td>
                                <td class="py-3 px-4 font-mono text-indigo-400"><?= e($l['ip_address']) ?></td>
                                <td class="py-3 px-4 text-slate-400 truncate max-w-xs" title="<?= e($l['user_agent']) ?>"><?= e($l['user_agent']) ?></td>
                                <td class="py-3 px-4 text-right">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $l['status'] === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <?= strtoupper(e($l['status'])) ?>
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
