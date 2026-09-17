<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(config('app.name', 'Apex SMM Services')) ?> - Account Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        primary: {
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-950">

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <a href="/" class="inline-flex items-center gap-2.5 mb-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-indigo-600/30">
                ⚡
            </div>
            <span class="text-2xl font-extrabold tracking-tight text-white"><?= e(config('app.name', 'Apex SMM')) ?></span>
        </a>
    </div>

    <!-- Flash Alerts -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0 mb-4">
        <?php if ($success = flash('success')): ?>
            <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium text-center">
                <?= e($success) ?>
            </div>
        <?php endif; ?>
        <?php if ($error = flash('error')): ?>
            <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm font-medium text-center">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-slate-900 border border-slate-800 py-8 px-6 sm:px-10 shadow-2xl rounded-2xl">
            <?= $content ?>
        </div>
    </div>

    <div class="mt-8 text-center text-xs text-slate-500">
        <a href="/" class="hover:text-slate-400 transition">&larr; Back to Homepage</a>
        <span class="mx-2">&bull;</span>
        <a href="/terms" class="hover:text-slate-400 transition">Terms & Conditions</a>
    </div>

</body>
</html>
