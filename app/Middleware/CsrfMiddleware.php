<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class CsrfMiddleware
{
    private array $except = [
        '/webhook/razorpay',
        '/api/v1/orders',
        '/api/v1/services',
        '/api/v1/balance',
    ];

    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $path = $request->path();
        foreach ($this->except as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return $next($request);
            }
        }

        $token = $request->post('_csrf_token') ?? $request->post('csrf_token') ?? $request->header('X-CSRF-Token');
        if (!Session::verifyCsrfToken($token)) {
            if ($request->isAjax()) {
                $res = new Response();
                return $res->json(['error' => 'CSRF token mismatch or expired. Please refresh the page.'], 419);
            }

            Session::setFlash('error', 'Session expired or security token mismatch. Please try again.');
            $res = new Response();
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            return $res->redirect($referer);
        }

        return $next($request);
    }
}
