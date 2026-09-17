<?php

declare(strict_types=1);

namespace App\Policies;

class AdminPolicy extends Policy
{
    public function isSuperAdmin(array $admin): bool
    {
        return (int)($admin['role_id'] ?? 0) === 1;
    }

    public function manageSettings(array $admin): bool
    {
        return $this->isSuperAdmin($admin);
    }

    public function manageUsers(array $admin): bool
    {
        return $this->isSuperAdmin($admin) || (int)($admin['role_id'] ?? 0) === 1;
    }

    public function managePayments(array $admin): bool
    {
        return $this->isSuperAdmin($admin);
    }
}
