<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class MaintenanceMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $isMaintenance = (string)setting('maintenance_mode', '0') === '1';

        if ($isMaintenance) {
            // Allow admin routes or logged in admin
            if (str_starts_with($request->path(), '/admin') || Session::get('admin')) {
                return $next($request);
            }

            $res = new Response();
            if ($request->isAjax()) {
                return $res->json(['error' => 'Platform is under scheduled maintenance.'], 503);
            }

            return $res->setStatusCode(503)->setContent(
                '<!DOCTYPE html><html><head><title>System Maintenance</title><meta name="viewport" content="width=device-width, initial-scale=1.0">' .
                '<script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen p-4">' .
                '<div class="max-w-md w-full text-center bg-slate-800 p-8 rounded-2xl border border-slate-700 shadow-2xl">' .
                '<h1 class="text-2xl font-bold mb-3">Scheduled Maintenance</h1>' .
                '<p class="text-slate-400 text-sm mb-6">Our platform is currently undergoing scheduled infrastructure optimization. Normal operations will resume shortly.</p>' .
                '<a href="/admin/login" class="text-xs text-indigo-400 hover:underline">Staff Access</a>' .
                '</div></body></html>'
            );
        }

        return $next($request);
    }
}
