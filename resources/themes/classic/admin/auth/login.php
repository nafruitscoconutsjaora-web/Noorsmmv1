<div class="space-y-6">
    <div class="text-center">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 text-rose-400 text-xs font-semibold mb-2">
            🛡️ Administrative Access
        </div>
        <h2 class="text-xl font-bold text-white tracking-tight">Staff Portal Sign In</h2>
        <p class="text-xs text-slate-400 mt-1">Authorized personnel only. All access is audited.</p>
    </div>

    <form action="/admin/login" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="login" class="block text-xs font-semibold text-slate-300 mb-1">Admin Username or Email</label>
            <input type="text" id="login" name="login" required autofocus
                value="<?= e(old('login')) ?>"
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
            <input type="password" id="password" name="password" required
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
        </div>

        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-sm shadow-md shadow-rose-600/20 transition">
            Authorize & Sign In
        </button>
    </form>

    <div class="text-center text-xs text-slate-500 pt-2 border-t border-slate-800">
        Demo Superadmin: <code class="text-slate-300">admin</code> / <code class="text-slate-300">admin123</code>
    </div>
</div>
