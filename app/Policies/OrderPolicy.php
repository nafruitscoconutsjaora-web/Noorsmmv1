<?php

declare(strict_types=1);

namespace App\Policies;

class OrderPolicy extends Policy
{
    public function view(array $user, array $order): bool
    {
        return (int)$order['user_id'] === (int)$user['id'];
    }

    public function cancel(array $user, array $order): bool
    {
        // Users can only request cancel if pending
        return (int)$order['user_id'] === (int)$user['id'] && in_array($order['status'], ['pending'], true);
    }
}
