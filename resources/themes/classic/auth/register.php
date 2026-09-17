<div class="space-y-6">
    <div class="text-center">
        <h2 class="text-xl font-bold text-white tracking-tight">Create your reseller account</h2>
        <p class="text-xs text-slate-400 mt-1">Get instant access to wholesale rates and automated API fulfillment</p>
    </div>

    <form action="/register" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="username" class="block text-xs font-semibold text-slate-300 mb-1">Username</label>
            <input type="text" id="username" name="username" required autofocus
                value="<?= e(old('username')) ?>"
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 mb-1">Email Address</label>
            <input type="email" id="email" name="email" required
                value="<?= e(old('email')) ?>"
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                <input type="password" id="password" name="password" required minlength="6"
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
            </div>
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6"
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-750 text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
            </div>
        </div>

        <div class="flex items-start gap-2 pt-1">
            <input type="checkbox" id="terms" required class="mt-1 rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500">
            <label for="terms" class="text-xs text-slate-400 leading-snug">
                I agree to the <a href="/terms" target="_blank" class="text-indigo-400 hover:underline">Terms of Service</a> and acknowledge the non-refundable deposit policy.
            </label>
        </div>

        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-md shadow-indigo-600/20 transition">
            Create Account
        </button>
    </form>

    <div class="text-center text-xs text-slate-400 pt-2 border-t border-slate-800">
        Already have an account?
        <a href="/login" class="font-semibold text-indigo-400 hover:text-indigo-300 transition">Sign in</a>
    </div>
</div>
