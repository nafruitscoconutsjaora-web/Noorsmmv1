<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CronTaskRepository;
use App\Services\CronService;

class AdminCronController extends BaseController
{
    private CronTaskRepository $cronRepo;

    public function __construct()
    {
        $this->cronRepo = new CronTaskRepository();
    }

    public function index(Request $request): Response
    {
        $tasks = $this->cronRepo->getAllTasks();
        $logs = $this->cronRepo->getRecentCronLogs(25);

        return view('admin/cron/index', [
            'tasks' => $tasks,
            'logs' => $logs,
        ], 'admin');
    }

    public function toggle(Request $request, string $id): Response
    {
        $this->cronRepo->toggleTask((int)$id);
        flash('success', 'Cron task schedule status toggled.');
        return $this->redirect('/admin/cron');
    }

    public function trigger(Request $request, string $id): Response
    {
        $cronService = new CronService();
        $start = microtime(true);
        $result = $cronService->runAll();
        $duration = round(microtime(true) - $start, 3);

        flash('success', "Cron tasks executed in {$duration}s: {$result['synced_orders']} orders synced, {$result['scheduled_runs']} scheduled runs processed.");
        return $this->redirect('/admin/cron');
    }
}
