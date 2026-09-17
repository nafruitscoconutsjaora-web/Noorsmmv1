<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ScheduleRepository;
use PDO;

class AdminScheduleController extends BaseController
{
    private ScheduleRepository $scheduleRepo;
    private PDO $db;

    public function __construct()
    {
        $this->scheduleRepo = new ScheduleRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $schedules = $this->scheduleRepo->getAllAdmin();

        $runs = $this->db->query("SELECT osr.*, os.service_id, s.name as service_name, u.username 
            FROM `order_schedule_runs` osr 
            JOIN `order_schedules` os ON osr.schedule_id = os.id 
            JOIN `services` s ON os.service_id = s.id 
            JOIN `users` u ON os.user_id = u.id 
            ORDER BY osr.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

        return view('admin/schedules/index', [
            'schedules' => $schedules,
            'runs' => $runs,
        ], 'admin');
    }

    public function toggle(Request $request, string $id): Response
    {
        $schedId = (int)$id;
        $schedule = $this->db->query("SELECT * FROM `order_schedules` WHERE `id` = {$schedId}")->fetch(PDO::FETCH_ASSOC);

        if ($schedule) {
            $newStatus = $schedule['status'] === 'active' ? 'paused' : 'active';
            $this->scheduleRepo->updateStatus($schedId, $newStatus);
            flash('success', "Schedule #{$schedId} status set to {$newStatus}.");
        }

        return $this->redirect('/admin/schedules');
    }
}
