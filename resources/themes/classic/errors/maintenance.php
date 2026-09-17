<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance - <?= e(config('app.name', 'Apex SMM')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex items-center justify-center bg-slate-950 text-slate-100 p-6">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center space-y-6 shadow-2xl">
        <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-3xl mx-auto">
            🛠️
        </div>

        <div class="space-y-2">
            <h1 class="text-2xl font-bold text-white tracking-tight">System Under Maintenance</h1>
            <p class="text-xs text-slate-400 leading-relaxed">
                We are currently performing routine database upgrades and provider API speed enhancements. The client portal will be restored shortly.
            </p>
        </div>

        <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 text-xs text-slate-400 space-y-1">
            <div class="text-[11px] text-slate-500">Need urgent support?</div>
            <div class="text-indigo-400 font-semibold"><?= e(setting('support_email', 'support@example.com')) ?></div>
        </div>
    </div>
</body>
</html>
