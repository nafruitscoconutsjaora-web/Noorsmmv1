<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AdminMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $admin = Session::get('admin');
        if (!$admin) {
            if ($request->isAjax()) {
                $res = new Response();
                return $res->json(['error' => 'Admin unauthorized'], 401);
            }
            Session::setFlash('error', 'Admin authentication required.');
            $res = new Response();
            return $res->redirect('/admin/login');
        }

        if (($admin['status'] ?? '') === 'suspended') {
            Session::remove('admin');
            Session::setFlash('error', 'Admin account is deactivated.');
            $res = new Response();
            return $res->redirect('/admin/login');
        }

        return $next($request);
    }
}
