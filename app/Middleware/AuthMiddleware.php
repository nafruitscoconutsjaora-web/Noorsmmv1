<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $user = Session::get('user');
        if (!$user) {
            if ($request->isAjax()) {
                $res = new Response();
                return $res->json(['error' => 'Unauthenticated'], 401);
            }
            Session::setFlash('error', 'Please log in to continue.');
            $res = new Response();
            return $res->redirect('/login');
        }

        // Check if user status is suspended
        if (($user['status'] ?? '') === 'suspended') {
            Session::remove('user');
            Session::setFlash('error', 'Your account has been suspended. Please contact support.');
            $res = new Response();
            return $res->redirect('/login');
        }

        return $next($request);
    }
}
