<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use App\Controllers\Web\AccountController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\DashboardController;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\OrderController;
use App\Controllers\Web\ServiceController;
use App\Controllers\Web\TicketController;
use App\Controllers\Web\WalletController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\RateLimitMiddleware;

// Public marketing & catalog routes
$router->group(['middleware' => [MaintenanceMiddleware::class, RateLimitMiddleware::class]], function ($router) {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/services', [HomeController::class, 'services']);
    $router->get('/api-docs', [HomeController::class, 'apiDocs']);
    $router->get('/terms', [HomeController::class, 'terms']);
});

// Guest Authentication
$router->group(['middleware' => [GuestMiddleware::class, MaintenanceMiddleware::class]], function ($router) {
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
    $router->get('/register', [AuthController::class, 'showRegister']);
    $router->post('/register', [AuthController::class, 'register'], [CsrfMiddleware::class]);
});

// User Authenticated Portal
$router->group(['middleware' => [AuthMiddleware::class, MaintenanceMiddleware::class]], function ($router) {
    $router->post('/logout', [AuthController::class, 'logout'], [CsrfMiddleware::class]);
    $router->get('/dashboard', [DashboardController::class, 'index']);

    // Orders
    $router->get('/orders/new', [OrderController::class, 'create']);
    $router->post('/orders', [OrderController::class, 'store'], [CsrfMiddleware::class]);
    $router->get('/orders', [OrderController::class, 'index']);
    $router->post('/orders/{id}/cancel', [OrderController::class, 'cancel'], [CsrfMiddleware::class]);
    $router->get('/api/service/{id}', [OrderController::class, 'serviceInfo']);

    // Services Catalog inside portal
    $router->get('/services-list', [ServiceController::class, 'index']);

    // Wallet & Payments
    $router->get('/wallet', [WalletController::class, 'index']);
    $router->post('/wallet/initiate', [WalletController::class, 'initiatePayment'], [CsrfMiddleware::class]);
    $router->post('/wallet/verify', [WalletController::class, 'verifyPayment'], [CsrfMiddleware::class]);
    $router->post('/wallet/test-deposit', [WalletController::class, 'testDeposit'], [CsrfMiddleware::class]);

    // Support Tickets
    $router->get('/tickets', [TicketController::class, 'index']);
    $router->get('/tickets/new', [TicketController::class, 'create']);
    $router->post('/tickets', [TicketController::class, 'store'], [CsrfMiddleware::class]);
    $router->get('/tickets/{id}', [TicketController::class, 'show']);
    $router->post('/tickets/{id}/reply', [TicketController::class, 'reply'], [CsrfMiddleware::class]);
    $router->post('/tickets/{id}/close', [TicketController::class, 'close'], [CsrfMiddleware::class]);

    // Account
    $router->get('/account', [AccountController::class, 'index']);
    $router->post('/account/password', [AccountController::class, 'changePassword'], [CsrfMiddleware::class]);
    $router->post('/account/api-key', [AccountController::class, 'generateApiKey'], [CsrfMiddleware::class]);
});
