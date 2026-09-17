<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Session;

abstract class Policy
{
    protected function currentUser(): ?array
    {
        return Session::get('user');
    }

    protected function currentAdmin(): ?array
    {
        return Session::get('admin');
    }
}
