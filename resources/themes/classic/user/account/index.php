<div class="max-w-4xl mx-auto space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Account Settings</h1>
        <p class="text-xs text-slate-400 mt-1">Manage your security credentials, API reseller token, and profile preferences.</p>
    </div>

    <!-- API Reseller Key Section -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl space-y-4">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span class="text-indigo-400">🔌</span> Reseller API Integration Key
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Use this key in external panels, automated scripts, or web apps to authenticate API calls.</p>
            </div>
            <a href="/api" target="_blank" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                API Docs &rarr;
            </a>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Your Secret API Key</label>
            <div class="flex items-center gap-2">
                <input type="text" readonly id="apiKeyField" value="<?= e($user['api_key'] ?? 'No key generated') ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-indigo-400 font-mono text-xs focus:outline-none select-all">
                <button type="button" onclick="copyApiKey()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold whitespace-nowrap transition" id="copyBtn">
                    Copy Key
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <div class="text-[11px] text-slate-500">
                Keep this key confidential. Anyone with this key can place orders using your balance.
            </div>
            <form action="/account/api-key" method="POST" onsubmit="return confirm('Regenerating your API key will immediately invalidate your old key. Continue?');">
                <?= csrf_field() ?>
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-semibold transition">
                    Regenerate Key
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Account Profile Details -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white pb-3 border-b border-slate-800">Profile Details</h2>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block mb-0.5">Username:</span>
                    <span class="font-semibold text-white"><?= e($user['username']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5">Email Address:</span>
                    <span class="font-semibold text-white"><?= e($user['email']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5">Account Balance:</span>
                    <span class="font-bold text-emerald-400 font-mono text-sm">₹<?= number_format((float)$user['balance'], 2) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5">Custom Discount Rate:</span>
                    <span class="font-semibold text-slate-300"><?= (float)($user['custom_rate'] ?? 0) ?>%</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5">Registered On:</span>
                    <span class="text-slate-400"><?= date('F d, Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Change Password Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white pb-3 border-b border-slate-800">Update Password</h2>

            <form action="/account/password" method="POST" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="old_password" class="block text-xs font-semibold text-slate-300 mb-1">Current Password</label>
                    <input type="password" id="old_password" name="old_password" required
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-semibold text-slate-300 mb-1">New Password (min 6 chars)</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                </div>

                <div>
                    <label for="new_password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1">Confirm New Password</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" required minlength="6"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/20 transition">
                    Save New Password
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function copyApiKey() {
        const keyField = document.getElementById('apiKeyField');
        keyField.select();
        keyField.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(keyField.value);
        const btn = document.getElementById('copyBtn');
        btn.textContent = 'Copied!';
        setTimeout(() => {
            btn.textContent = 'Copy Key';
        }, 2000);
    }
</script>
