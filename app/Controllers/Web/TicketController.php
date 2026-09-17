<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\SupportTicketRepository;

class TicketController extends BaseController
{
    private SupportTicketRepository $ticketRepo;

    public function __construct()
    {
        $this->ticketRepo = new SupportTicketRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $page = max(1, (int)$request->query('page', 1));
        $tickets = $this->ticketRepo->getUserTickets((int)$user['id'], $page, 15);

        return view('user/tickets/index', [
            'tickets' => $tickets,
        ], 'user');
    }

    public function create(Request $request): Response
    {
        return view('user/tickets/new', [], 'user');
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'subject' => 'required|min:4|max:150',
            'priority' => 'required|in:low,medium,high',
            'message' => 'required|min:10',
        ]);

        $user = $this->user();
        $ticketId = $this->ticketRepo->createTicket([
            'user_id' => $user['id'],
            'subject' => $data['subject'],
            'priority' => $data['priority'],
            'message' => $data['message'],
        ]);

        Session::setFlash('success', "Support ticket #{$ticketId} submitted. Our agents will respond shortly.");
        return $this->redirect("/tickets/{$ticketId}");
    }

    public function show(Request $request, string $id): Response
    {
        $ticketId = (int)$id;
        $ticket = $this->ticketRepo->findById($ticketId);
        $user = $this->user();

        if (!$ticket || (int)$ticket['user_id'] !== (int)$user['id']) {
            Session::setFlash('error', 'Support ticket not found.');
            return $this->redirect('/tickets');
        }

        $messages = $this->ticketRepo->getMessages($ticketId);

        return view('user/tickets/show', [
            'ticket' => $ticket,
            'messages' => $messages,
        ], 'user');
    }

    public function reply(Request $request, string $id): Response
    {
        $ticketId = (int)$id;
        $ticket = $this->ticketRepo->findById($ticketId);
        $user = $this->user();

        if (!$ticket || (int)$ticket['user_id'] !== (int)$user['id']) {
            Session::setFlash('error', 'Support ticket not found.');
            return $this->redirect('/tickets');
        }

        if ($ticket['status'] === 'closed') {
            Session::setFlash('error', 'This ticket is marked as closed.');
            return $this->redirect("/tickets/{$ticketId}");
        }

        $data = $this->validate($request->all(), [
            'message' => 'required|min:2',
        ]);

        $this->ticketRepo->addMessage($ticketId, 'user', (int)$user['id'], $data['message']);

        Session::setFlash('success', 'Your reply has been posted.');
        return $this->redirect("/tickets/{$ticketId}");
    }

    public function close(Request $request, string $id): Response
    {
        $ticketId = (int)$id;
        $ticket = $this->ticketRepo->findById($ticketId);
        $user = $this->user();

        if (!$ticket || (int)$ticket['user_id'] !== (int)$user['id']) {
            Session::setFlash('error', 'Support ticket not found.');
            return $this->redirect('/tickets');
        }

        $this->ticketRepo->updateStatus($ticketId, 'closed');
        Session::setFlash('success', "Ticket #{$ticketId} closed.");
        return $this->redirect('/tickets');
    }
}
