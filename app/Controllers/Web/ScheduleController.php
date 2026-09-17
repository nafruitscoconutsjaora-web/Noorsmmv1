<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ScheduleRepository;
use PDO;

class ScheduleController extends BaseController
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
        $user = $this->user();
        $schedules = $this->scheduleRepo->getForUser($user['id']);

        // Load active services for schedule creation modal/dropdown
        $services = $this->db->query("SELECT s.id, s.name, s.rate, c.name as category_name 
                                      FROM `services` s 
                                      JOIN `categories` c ON s.category_id = c.id 
                                      WHERE s.status = 'active' 
                                      ORDER BY c.sort_order ASC, s.name ASC")->fetchAll(PDO::FETCH_ASSOC);

        return view('user/orders/schedules', [
            'schedules' => $schedules,
            'services' => $services,
        ], 'user');
    }

    public function store(Request $request): Response
    {
        $user = $this->user();
        $serviceId = (int)$request->input('service_id');
        $link = trim((string)$request->input('link'));
        $quantity = (int)$request->input('quantity');
        $scheduleType = (string)$request->input('schedule_type', 'recurring');
        $runsTotal = max(1, (int)$request->input('runs_total', 5));
        $intervalHours = max(1, (int)$request->input('interval_hours', 24));
        $startDelayHours = max(0, (int)$request->input('start_delay_hours', 0));

        if (empty($link) || $serviceId <= 0 || $quantity <= 0) {
            flash('error', 'Please provide a valid service, link, and quantity.');
            return $this->redirect('/orders/schedules');
        }

        // Verify service exists
        $stmt = $this->db->prepare("SELECT * FROM `services` WHERE `id` = :id AND `status` = 'active'");
        $stmt->execute([':id' => $serviceId]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            flash('error', 'Selected service is currently inactive or not available.');
            return $this->redirect('/orders/schedules');
        }

        if ($quantity < $service['min_quantity'] || $quantity > $service['max_quantity']) {
            flash('error', "Quantity must be between {$service['min_quantity']} and {$service['max_quantity']}.");
            return $this->redirect('/orders/schedules');
        }

        $nextRun = date('Y-m-d H:i:s', strtotime("+{$startDelayHours} hours"));

        $this->scheduleRepo->create([
            'user_id' => $user['id'],
            'service_id' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
            'schedule_type' => $scheduleType,
            'runs_total' => $runsTotal,
            'interval_hours' => $intervalHours,
            'next_run_at' => $nextRun,
        ]);

        flash('success', 'Order schedule created successfully. Auto-runs will process at scheduled intervals.');
        return $this->redirect('/orders/schedules');
    }

    public function toggle(Request $request, string $id): Response
    {
        $user = $this->user();
        $schedule = $this->scheduleRepo->findByIdAndUser((int)$id, $user['id']);

        if (!$schedule) {
            flash('error', 'Schedule not found.');
            return $this->redirect('/orders/schedules');
        }

        $newStatus = $schedule['status'] === 'active' ? 'paused' : 'active';
        $this->scheduleRepo->updateStatus($schedule['id'], $newStatus);

        flash('success', "Schedule status set to {$newStatus}.");
        return $this->redirect('/orders/schedules');
    }

    public function cancel(Request $request, string $id): Response
    {
        $user = $this->user();
        $schedule = $this->scheduleRepo->findByIdAndUser((int)$id, $user['id']);

        if (!$schedule) {
            flash('error', 'Schedule not found.');
            return $this->redirect('/orders/schedules');
        }

        $this->scheduleRepo->updateStatus($schedule['id'], 'cancelled');
        flash('success', 'Schedule has been cancelled.');
        return $this->redirect('/orders/schedules');
    }

    public function runs(Request $request, string $id): Response
    {
        $user = $this->user();
        $schedule = $this->scheduleRepo->findByIdAndUser((int)$id, $user['id']);

        if (!$schedule) {
            return $this->json(['error' => 'Schedule not found'], 404);
        }

        $runs = $this->scheduleRepo->getRuns($schedule['id']);
        return $this->json(['runs' => $runs]);
    }
}
