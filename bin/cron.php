<?php

declare(strict_types=1);

// SMM Panel Cron Runner
// Usage: php bin/cron.php [task_name]

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/helpers.php';

$app = new App\Core\Application(dirname(__DIR__));
$task = $argv[1] ?? 'all';

echo "[" . date('Y-m-d H:i:s') . "] Starting SMM Cron: {$task}\n";

$cronRepo = new App\Repositories\CronLogRepository();
$cronConfig = config('cron.tasks', []);

if ($task !== 'all' && isset($cronConfig[$task])) {
    $tasksToRun = [$task => $cronConfig[$task]];
} else {
    $tasksToRun = $cronConfig;
}

foreach ($tasksToRun as $name => $class) {
    if (!class_exists($class)) {
        echo "Task {$name} ({$class}) not found, skipping.\n";
        continue;
    }

    $logId = $cronRepo->logStart($name);
    try {
        $runner = new $class();
        $output = $runner->run();
        $cronRepo->logFinish($logId, 'success', is_string($output) ? $output : json_encode($output));
        echo "[SUCCESS] {$name}: " . (is_string($output) ? $output : 'Done') . "\n";
    } catch (\Throwable $e) {
        $cronRepo->logFinish($logId, 'failed', $e->getMessage());
        echo "[FAILED] {$name}: {$e->getMessage()}\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] SMM Cron execution completed.\n";
