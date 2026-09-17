<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class GuestMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Session::get('user')) {
            $res = new Response();
            return $res->redirect('/dashboard');
        }

        return $next($request);
    }
}
