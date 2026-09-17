<div class="space-y-6">
    <div class="text-center">
        <h2 class="text-xl font-bold text-white tracking-tight">Sign in to your account</h2>
        <p class="text-xs text-slate-400 mt-1">Access your dashboard, place orders, and manage funds</p>
    </div>

    <form action="/login" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="login" class="block text-xs font-semibold text-slate-300 mb-1">Username or Email</label>
            <input type="text" id="login" name="login" required autofocus
                value="<?= e(old('login')) ?>"
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-xs font-semibold text-slate-300">Password</label>
            </div>
            <input type="password" id="password" name="password" required
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
        </div>

        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-md shadow-indigo-600/20 transition">
            Sign In
        </button>
    </form>

    <div class="text-center text-xs text-slate-400 pt-2 border-t border-slate-800">
        Don't have an account yet?
        <a href="/register" class="font-semibold text-indigo-400 hover:text-indigo-300 transition">Register here</a>
    </div>
</div>
