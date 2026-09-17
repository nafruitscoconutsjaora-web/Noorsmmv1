<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NotificationRepository;
use PDO;

class AdminNotificationController extends BaseController
{
    private NotificationRepository $notifRepo;
    private PDO $db;

    public function __construct()
    {
        $this->notifRepo = new NotificationRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $notifications = $this->notifRepo->getAllAdmin(50);
        $users = $this->db->query("SELECT id, username FROM `users` WHERE `status` = 'active' ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

        return view('admin/notifications/index', [
            'notifications' => $notifications,
            'users' => $users,
        ], 'admin');
    }

    public function send(Request $request): Response
    {
        $title = trim((string)$request->input('title'));
        $message = trim((string)$request->input('message'));
        $type = (string)$request->input('type', 'info');
        $target = (string)$request->input('target', 'all'); // 'all' or specific user ID

        if (empty($title) || empty($message)) {
            flash('error', 'Title and message are required.');
            return $this->redirect('/admin/notifications');
        }

        if ($target === 'all') {
            $this->notifRepo->create([
                'user_id' => null,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_global' => 1,
            ]);
            flash('success', 'Global announcement broadcasted to all users.');
        } else {
            $userId = (int)$target;
            $this->notifRepo->create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_global' => 0,
            ]);
            flash('success', "Targeted notification sent to user #{$userId}.");
        }

        return $this->redirect('/admin/notifications');
    }
}
