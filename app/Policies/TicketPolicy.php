<?php

declare(strict_types=1);

namespace App\Policies;

class TicketPolicy extends Policy
{
    public function view(array $user, array $ticket): bool
    {
        return (int)$ticket['user_id'] === (int)$user['id'];
    }

    public function reply(array $user, array $ticket): bool
    {
        return (int)$ticket['user_id'] === (int)$user['id'] && $ticket['status'] !== 'closed';
    }
}
