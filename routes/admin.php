<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminOrderController;
use App\Controllers\Admin\AdminPaymentController;
use App\Controllers\Admin\AdminProviderController;
use App\Controllers\Admin\AdminServiceController;
use App\Controllers\Admin\AdminSettingController;
use App\Controllers\Admin\AdminTicketController;
use App\Controllers\Admin\AdminUserController;
use App\Middleware\AdminMiddleware;
use App\Middleware\CsrfMiddleware;

// Admin Authentication
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login'], [CsrfMiddleware::class]);
$router->post('/admin/logout', [AdminAuthController::class, 'logout'], [CsrfMiddleware::class]);

// Admin Protected Routes
$router->group(['middleware' => [AdminMiddleware::class]], function ($router) {
    $router->get('/admin', [AdminDashboardController::class, 'index']);
    $router->get('/admin/dashboard', [AdminDashboardController::class, 'index']);

    // Orders Management
    $router->get('/admin/orders', [AdminOrderController::class, 'index']);
    $router->get('/admin/orders/{id}', [AdminOrderController::class, 'show']);
    $router->post('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus'], [CsrfMiddleware::class]);
    $router->post('/admin/orders/{id}/retry', [AdminOrderController::class, 'retryProvider'], [CsrfMiddleware::class]);

    // Services Management
    $router->get('/admin/services', [AdminServiceController::class, 'index']);
    $router->get('/admin/services/create', [AdminServiceController::class, 'create']);
    $router->post('/admin/services', [AdminServiceController::class, 'store'], [CsrfMiddleware::class]);
    $router->get('/admin/services/{id}/edit', [AdminServiceController::class, 'edit']);
    $router->post('/admin/services/{id}/edit', [AdminServiceController::class, 'update'], [CsrfMiddleware::class]);
    $router->post('/admin/services/{id}/delete', [AdminServiceController::class, 'delete'], [CsrfMiddleware::class]);

    // Categories
    $router->get('/admin/categories', [AdminServiceController::class, 'categories']);
    $router->post('/admin/categories', [AdminServiceController::class, 'storeCategory'], [CsrfMiddleware::class]);
    $router->post('/admin/categories/{id}/delete', [AdminServiceController::class, 'deleteCategory'], [CsrfMiddleware::class]);

    // Providers
    $router->get('/admin/providers', [AdminProviderController::class, 'index']);
    $router->post('/admin/providers', [AdminProviderController::class, 'store'], [CsrfMiddleware::class]);
    $router->post('/admin/providers/{id}/test', [AdminProviderController::class, 'test'], [CsrfMiddleware::class]);
    $router->get('/admin/providers/{id}/import', [AdminProviderController::class, 'import']);
    $router->post('/admin/providers/{id}/import', [AdminProviderController::class, 'executeImport'], [CsrfMiddleware::class]);

    // Users
    $router->get('/admin/users', [AdminUserController::class, 'index']);
    $router->get('/admin/users/{id}', [AdminUserController::class, 'show']);
    $router->post('/admin/users/{id}/balance', [AdminUserController::class, 'adjustBalance'], [CsrfMiddleware::class]);
    $router->post('/admin/users/{id}/status', [AdminUserController::class, 'updateStatus'], [CsrfMiddleware::class]);

    // Payments
    $router->get('/admin/payments', [AdminPaymentController::class, 'index']);

    // Support Tickets
    $router->get('/admin/tickets', [AdminTicketController::class, 'index']);
    $router->get('/admin/tickets/{id}', [AdminTicketController::class, 'show']);
    $router->post('/admin/tickets/{id}/reply', [AdminTicketController::class, 'reply'], [CsrfMiddleware::class]);
    $router->post('/admin/tickets/{id}/status', [AdminTicketController::class, 'updateStatus'], [CsrfMiddleware::class]);

    // Settings
    $router->get('/admin/settings', [AdminSettingController::class, 'index']);
    $router->post('/admin/settings', [AdminSettingController::class, 'update'], [CsrfMiddleware::class]);
});
