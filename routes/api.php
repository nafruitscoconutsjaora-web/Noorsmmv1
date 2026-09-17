<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use App\Controllers\Api\ApiController;
use App\Controllers\Api\WebhookController;
use App\Middleware\RateLimitMiddleware;

$router->group(['middleware' => [RateLimitMiddleware::class]], function ($router) {
    // SMM Standard Reseller API v1/v2
    $router->post('/api/v1', [ApiController::class, 'handle']);
    $router->get('/api/v1', [ApiController::class, 'handle']);

    // Payment Gateway Webhooks
    $router->post('/webhook/razorpay', [WebhookController::class, 'razorpay']);
});
