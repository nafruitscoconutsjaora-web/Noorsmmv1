<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Support\Cache;

class RateLimitMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $ip = $request->ip();
        $key = 'rate_limit_' . md5($ip . '_' . $request->path());
        $maxRequests = (int)config('security.rate_limit_requests', 60);
        $window = (int)config('security.rate_limit_window', 60);

        $current = Cache::get($key, 0);
        if ($current >= $maxRequests) {
            $res = new Response();
            if ($request->isAjax()) {
                return $res->json(['error' => 'Too many requests. Please slow down.'], 429);
            }
            return $res->setStatusCode(429)->setContent('<h1>429 - Too Many Requests</h1><p>Please wait a moment before trying again.</p>');
        }

        Cache::set($key, $current + 1, $window);
        return $next($request);
    }
}
