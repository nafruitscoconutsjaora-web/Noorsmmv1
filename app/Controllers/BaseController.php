<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Validation\Validator;

abstract class BaseController
{
    protected function validate(array $data, array $rules): array
    {
        return Validator::make($data, $rules)->validate();
    }

    protected function user(): ?array
    {
        return Session::get('user');
    }

    protected function admin(): ?array
    {
        return Session::get('admin');
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return json_response($data, $status);
    }

    protected function redirect(string $url): Response
    {
        return redirect($url);
    }
}
