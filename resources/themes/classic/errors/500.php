<div class="min-h-[60vh] flex items-center justify-center py-16 px-4">
    <div class="text-center max-w-md space-y-6">
        <div class="text-8xl font-extrabold text-rose-500/30 font-mono select-none">500</div>
        <div class="space-y-2">
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Internal Server Fault</h1>
            <p class="text-sm text-slate-400">
                <?= !empty($message) ? e($message) : "An unexpected condition was encountered on the server. Our engineering team has been alerted." ?>
            </p>
        </div>

        <div class="flex items-center justify-center gap-3 pt-2">
            <a href="/" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 transition">
                Return to Safety
            </a>
        </div>
    </div>
</div>
