<div class="min-h-[60vh] flex items-center justify-center py-16 px-4">
    <div class="text-center max-w-md space-y-6">
        <div class="text-8xl font-extrabold text-indigo-500/30 font-mono select-none">404</div>
        <div class="space-y-2">
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Endpoint Not Found</h1>
            <p class="text-sm text-slate-400">
                <?= !empty($message) ? e($message) : "The destination URL or resource requested doesn't exist or has moved." ?>
            </p>
        </div>

        <div class="flex items-center justify-center gap-3 pt-2">
            <a href="/" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 transition">
                Return to Home
            </a>
            <a href="/services" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
                Browse Services
            </a>
        </div>
    </div>
</div>
