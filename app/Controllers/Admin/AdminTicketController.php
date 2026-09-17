<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\SupportTicketRepository;

class AdminTicketController extends BaseController
{
    private SupportTicketRepository $ticketRepo;

    public function __construct()
    {
        $this->ticketRepo = new SupportTicketRepository();
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query('page', 1));
        $status = (string)$request->query('status', 'all');

        $tickets = $this->ticketRepo->getAdminPaginated($page, 20, $status);

        return view('admin/tickets/index', [
            'tickets' => $tickets,
            'current_status' => $status,
        ], 'admin');
    }

    public function show(Request $request, string $id): Response
    {
        $ticketId = (int)$id;
        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) {
            Session::setFlash('error', 'Ticket not found.');
            return $this->redirect('/admin/tickets');
        }

        $messages = $this->ticketRepo->getMessages($ticketId);

        return view('admin/tickets/show', [
            'ticket' => $ticket,
            'messages' => $messages,
        ], 'admin');
    }

    public function reply(Request $request, string $id): Response
    {
        $ticketId = (int)$id;
        $data = $this->validate($request->all(), [
            'message' => 'required|min:2',
        ]);

        $admin = $this->admin();
        $this->ticketRepo->addMessage($ticketId, 'admin', (int)$admin['id'], $data['message']);

        Session::setFlash('success', 'Reply submitted to user.');
        return $this->redirect("/admin/tickets/{$ticketId}");
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $ticketId = (int)$id;
        $status = (string)$request->post('status');

        if (!in_array($status, ['open', 'answered', 'closed'], true)) {
            Session::setFlash('error', 'Invalid status.');
            return $this->redirect("/admin/tickets/{$ticketId}");
        }

        $this->ticketRepo->updateStatus($ticketId, $status);
        Session::setFlash('success', "Ticket status updated to {$status}.");
        return $this->redirect("/admin/tickets/{$ticketId}");
    }
}
