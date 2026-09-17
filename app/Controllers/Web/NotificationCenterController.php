<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NotificationRepository;

class NotificationCenterController extends BaseController
{
    private NotificationRepository $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $type = $request->query('type');
        $unreadOnly = $request->query('unread') === '1';
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $items = $this->notifications->getForUser($user['id'], $type, $unreadOnly, $perPage, $offset);
        $total = $this->notifications->countForUser($user['id'], $type, $unreadOnly);
        $totalPages = (int)ceil($total / $perPage);

        return view('user/notifications/index', [
            'notifications' => $items,
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
            'type' => $type,
            'unread_only' => $unreadOnly,
        ], 'user');
    }

    public function markAsRead(Request $request, string $id): Response
    {
        $user = $this->user();
        $this->notifications->markAsRead((int)$id, $user['id']);

        if ($request->isAjax()) {
            return $this->json(['success' => true]);
        }
        flash('success', 'Notification marked as read.');
        return $this->redirect('/notifications');
    }

    public function markAllAsRead(Request $request): Response
    {
        $user = $this->user();
        $this->notifications->markAllAsRead($user['id']);

        flash('success', 'All notifications marked as read.');
        return $this->redirect('/notifications');
    }
}
