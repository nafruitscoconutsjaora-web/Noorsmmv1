<?php

declare(strict_types=1);

// Apex SMM Platform - Installation Wizard - Step 2: Requirements Check

$baseDir = dirname(__DIR__);

// Auto-create storage subdirectories if they don't exist
$dirsToEnsure = [
    $baseDir . '/storage',
    $baseDir . '/storage/logs',
    $baseDir . '/storage/cache',
    $baseDir . '/storage/sessions',
    $baseDir . '/storage/temp',
    $baseDir . '/storage/uploads',
    $baseDir . '/bootstrap',
];

foreach ($dirsToEnsure as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

// 1. PHP Version
$phpMinVersion = '8.1.0';
$phpPassed = version_compare(PHP_VERSION, $phpMinVersion, '>=');

// 2. Extensions
$requiredExtensions = [
    'pdo' => 'PDO Abstraction Layer',
    'pdo_mysql' => 'PDO MySQL / MariaDB Driver',
    'curl' => 'cURL Client (Wholesale Provider APIs & Webhooks)',
    'bcmath' => 'BCMath (High-Precision Financial Arithmetic)',
    'mbstring' => 'MBString (Multibyte String Processing)',
    'json' => 'JSON Parser & Encoder',
    'openssl' => 'OpenSSL Cryptography & TLS',
    'ctype' => 'Ctype Character Type Functions',
];

$extensionChecks = [];
$allExtensionsPassed = true;

foreach ($requiredExtensions as $ext => $description) {
    $loaded = extension_loaded($ext);
    if (!$loaded) {
        $allExtensionsPassed = false;
    }
    $extensionChecks[] = [
        'extension' => $ext,
        'description' => $description,
        'passed' => $loaded,
        'status' => $loaded ? 'Installed' : 'Missing',
    ];
}

// 3. Directory & File Permissions
$requiredPaths = [
    $baseDir . '/storage' => 'Storage Directory (/storage)',
    $baseDir . '/storage/logs' => 'Logs Storage (/storage/logs)',
    $baseDir . '/storage/cache' => 'Cache Directory (/storage/cache)',
    $baseDir . '/storage/sessions' => 'Session Directory (/storage/sessions)',
    $baseDir . '/bootstrap' => 'Bootstrap Directory (/bootstrap)',
    $baseDir . '/.env' => 'Environment Secret File (/.env)',
];

$pathChecks = [];
$allPathsPassed = true;

foreach ($requiredPaths as $path => $label) {
    $exists = file_exists($path);
    $writable = false;

    if ($exists) {
        $writable = is_writable($path);
    } else {
        // If file doesn't exist (like .env), check if parent directory is writable
        $parent = dirname($path);
        $writable = is_writable($parent);
    }

    if (!$writable) {
        $allPathsPassed = false;
    }

    $pathChecks[] = [
        'path' => $path,
        'label' => $label,
        'passed' => $writable,
        'status' => $writable ? 'Writable' : 'Not Writable',
    ];
}

// 4. Server Directives
$directives = [
    'memory_limit' => [
        'label' => 'PHP Memory Limit',
        'current' => ini_get('memory_limit'),
        'recommended' => '128M+',
    ],
    'max_execution_time' => [
        'label' => 'Max Execution Time',
        'current' => ini_get('max_execution_time') . 's',
        'recommended' => '30s+',
    ],
    'upload_max_filesize' => [
        'label' => 'Upload Max Filesize',
        'current' => ini_get('upload_max_filesize'),
        'recommended' => '5M+',
    ],
    'allow_url_fopen' => [
        'label' => 'allow_url_fopen',
        'current' => ini_get('allow_url_fopen') ? 'On' : 'Off',
        'recommended' => 'On',
    ],
];

$allRequirementsPassed = $phpPassed && $allExtensionsPassed && $allPathsPassed;
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex SMM - Requirements Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-between p-4 sm:p-6 lg:p-8 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-slate-950">
    <div class="max-w-3xl w-full mx-auto my-auto">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 mb-4 shadow-lg shadow-rose-500/10">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Apex SMM Platform</h1>
            <p class="text-sm text-slate-400 mt-1">High-Speed Social Media Marketing Reseller System</p>
        </div>

        <!-- Wizard Stepper Navigation -->
        <div class="bg-slate-900/80 border border-slate-800/80 backdrop-blur rounded-2xl p-4 sm:p-6 mb-6 shadow-xl">
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center mb-1.5 shadow">
                        ✓
                    </div>
                    <span class="text-slate-400 font-medium">Welcome</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-rose-500 text-white font-bold flex items-center justify-center ring-4 ring-rose-500/20 mb-1.5 shadow">
                        2
                    </div>
                    <span class="font-semibold text-rose-400">Requirements</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 font-semibold flex items-center justify-center mb-1.5">
                        3
                    </div>
                    <span class="text-slate-400">Database</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 font-semibold flex items-center justify-center mb-1.5">
                        4
                    </div>
                    <span class="text-slate-400">Complete</span>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white tracking-tight">Server Prerequisites & Permissions</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Validating host environment against production requirements</p>
                </div>
                <?php if ($allRequirementsPassed): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Ready to Proceed
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 border border-rose-500/20 text-rose-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                        Action Required
                    </span>
                <?php endif; ?>
            </div>

            <!-- Status Alert -->
            <?php if ($allRequirementsPassed): ?>
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>All core PHP extensions and writable storage directory permissions are verified. You can proceed to the database configuration.</span>
                </div>
            <?php else: ?>
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-semibold">Some system prerequisites were not met:</span>
                        <p class="text-slate-400 mt-1">Please install any missing PHP extensions or run the following permission command on your server:</p>
                        <pre class="mt-2 p-2 rounded bg-slate-950 text-slate-300 font-mono text-[11px] overflow-x-auto">chmod -R 775 storage bootstrap</pre>
                    </div>
                </div>
            <?php endif; ?>

            <!-- PHP Version Check -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">1. PHP Environment</h3>
                <div class="p-3.5 rounded-xl bg-slate-800/40 border border-slate-800/80 flex items-center justify-between text-xs">
                    <div>
                        <span class="font-semibold text-white">PHP Engine Version</span>
                        <span class="text-slate-400 block text-[11px] mt-0.5">Required: <?= $phpMinVersion ?> or newer</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-slate-300 bg-slate-950 px-2 py-0.5 rounded border border-slate-800"><?= PHP_VERSION ?></span>
                        <?php if ($phpPassed): ?>
                            <span class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">✓</span>
                        <?php else: ?>
                            <span class="w-5 h-5 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-xs">✕</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Extensions Table -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">2. Required PHP Extensions</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($extensionChecks as $chk): ?>
                        <div class="p-3 rounded-xl bg-slate-800/40 border border-slate-800/80 flex items-center justify-between text-xs">
                            <div class="pr-2">
                                <span class="font-mono font-semibold text-white"><?= htmlspecialchars($chk['extension']) ?></span>
                                <span class="text-slate-400 block text-[11px] truncate max-w-[200px]"><?= htmlspecialchars($chk['description']) ?></span>
                            </div>
                            <div>
                                <?php if ($chk['passed']): ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-400">
                                        <span>Active</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-400">
                                        <span>Missing</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Path Permissions Table -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">3. Directory & File Write Permissions</h3>
                <div class="space-y-2">
                    <?php foreach ($pathChecks as $chk): ?>
                        <div class="p-3 rounded-xl bg-slate-800/40 border border-slate-800/80 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-medium text-slate-200"><?= htmlspecialchars($chk['label']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($chk['passed']): ?>
                                    <span class="text-[11px] text-emerald-400 font-semibold">Writable</span>
                                    <span class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">✓</span>
                                <?php else: ?>
                                    <span class="text-[11px] text-rose-400 font-semibold">Not Writable</span>
                                    <span class="w-5 h-5 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-xs">✕</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Server Directives Reference -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">4. Recommended Directives</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <?php foreach ($directives as $key => $dir): ?>
                        <div class="p-2.5 rounded-xl bg-slate-800/20 border border-slate-800/60 text-center">
                            <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($dir['label']) ?></span>
                            <span class="text-xs font-mono font-bold text-slate-200 mt-0.5 block"><?= htmlspecialchars($dir['current']) ?></span>
                            <span class="text-[9px] text-slate-500 block">Rec: <?= htmlspecialchars($dir['recommended']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-800/80">
                <a href="index.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to Welcome</span>
                </a>

                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="requirements.php" class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl transition">
                        Re-check
                    </a>

                    <?php if ($allRequirementsPassed): ?>
                        <a href="database.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-rose-500 hover:bg-rose-600 active:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-lg shadow-rose-500/20 transition">
                            <span>Continue to Database Setup</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    <?php else: ?>
                        <button disabled class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-slate-800 text-slate-500 text-xs font-semibold rounded-xl cursor-not-allowed">
                            <span>Requirements Not Met</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; <?= date('Y') ?> Apex SMM Services. Step 2 of 4: Requirements Verification.
        </p>
    </div>
</body>
</html>
