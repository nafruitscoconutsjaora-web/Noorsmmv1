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

    // --- PART 2 USER FEATURES ---
    // 1. Advanced Order Tracking
    $router->get('/orders/{id}/tracking', [\App\Controllers\Web\TrackingController::class, 'show']);
    $router->get('/api/orders/{id}/tracking', [\App\Controllers\Web\TrackingController::class, 'pollStatus']);

    // 2. Order Analytics
    $router->get('/orders/analytics', [\App\Controllers\Web\AnalyticsController::class, 'index']);

    // 3. Advanced Wallet Activity
    $router->get('/wallet/activity', [\App\Controllers\Web\WalletActivityController::class, 'index']);

    // 4. Notification Center
    $router->get('/notifications', [\App\Controllers\Web\NotificationCenterController::class, 'index']);
    $router->post('/notifications/{id}/read', [\App\Controllers\Web\NotificationCenterController::class, 'markAsRead'], [CsrfMiddleware::class]);
    $router->post('/notifications/read-all', [\App\Controllers\Web\NotificationCenterController::class, 'markAllAsRead'], [CsrfMiddleware::class]);

    // 6. Security & Login Activity
    $router->get('/account/security', [\App\Controllers\Web\SecurityController::class, 'index']);
    $router->post('/account/security/password', [\App\Controllers\Web\SecurityController::class, 'updatePassword'], [CsrfMiddleware::class]);
    $router->post('/account/security/logout-others', [\App\Controllers\Web\SecurityController::class, 'logoutOtherSessions'], [CsrfMiddleware::class]);

    // 7. Referral System
    $router->get('/referrals', [\App\Controllers\Web\ReferralController::class, 'index']);
    $router->post('/referrals/payout', [\App\Controllers\Web\ReferralController::class, 'requestPayout'], [CsrfMiddleware::class]);

    // 8. User API Management
    $router->get('/account/api', [\App\Controllers\Web\UserApiController::class, 'index']);
    $router->post('/account/api/generate', [\App\Controllers\Web\UserApiController::class, 'generateKey'], [CsrfMiddleware::class]);
    $router->post('/account/api/revoke', [\App\Controllers\Web\UserApiController::class, 'revokeKey'], [CsrfMiddleware::class]);

    // 9. Favorite Services
    $router->get('/services/favorites', [\App\Controllers\Web\FavoriteController::class, 'index']);
    $router->post('/services/{id}/favorite', [\App\Controllers\Web\FavoriteController::class, 'toggle'], [CsrfMiddleware::class]);

    // 10. Refill & Cancellation Management
    $router->get('/orders/refills', [\App\Controllers\Web\RefillController::class, 'index']);
    $router->post('/orders/{id}/refill', [\App\Controllers\Web\RefillController::class, 'requestRefill'], [CsrfMiddleware::class]);
    $router->post('/orders/{id}/cancel-request', [\App\Controllers\Web\RefillController::class, 'requestCancellation'], [CsrfMiddleware::class]);

    // 11. Scheduled & Recurring Orders
    $router->get('/orders/schedules', [\App\Controllers\Web\ScheduleController::class, 'index']);
    $router->post('/orders/schedules', [\App\Controllers\Web\ScheduleController::class, 'store'], [CsrfMiddleware::class]);
    $router->post('/orders/schedules/{id}/toggle', [\App\Controllers\Web\ScheduleController::class, 'toggle'], [CsrfMiddleware::class]);
    $router->post('/orders/schedules/{id}/cancel', [\App\Controllers\Web\ScheduleController::class, 'cancel'], [CsrfMiddleware::class]);
    $router->get('/api/orders/schedules/{id}/runs', [\App\Controllers\Web\ScheduleController::class, 'runs']);

    // 12. Service Comparison
    $router->get('/services/compare', [\App\Controllers\Web\ComparisonController::class, 'index']);

    // 13. Rewards & Loyalty
    $router->get('/rewards', [\App\Controllers\Web\LoyaltyController::class, 'index']);
    $router->post('/rewards/redeem', [\App\Controllers\Web\LoyaltyController::class, 'redeem'], [CsrfMiddleware::class]);

    // 14. User Reports & Export
    $router->get('/reports/export', [\App\Controllers\Web\ExportController::class, 'index']);
    $router->get('/reports/export/orders', [\App\Controllers\Web\ExportController::class, 'exportOrders']);
    $router->get('/reports/export/wallet', [\App\Controllers\Web\ExportController::class, 'exportWallet']);

    // 15. User Preferences
    $router->get('/account/preferences', [\App\Controllers\Web\PreferencesController::class, 'index']);
    $router->post('/account/preferences', [\App\Controllers\Web\PreferencesController::class, 'update'], [CsrfMiddleware::class]);

    // 16. User Verification Center
    $router->get('/account/verification', [\App\Controllers\Web\VerificationController::class, 'index']);
    $router->post('/account/verification/email', [\App\Controllers\Web\VerificationController::class, 'sendEmailVerification'], [CsrfMiddleware::class]);
    $router->post('/account/verification/phone', [\App\Controllers\Web\VerificationController::class, 'updatePhone'], [CsrfMiddleware::class]);

    // 17. Help & Knowledge Center
    $router->get('/help', [\App\Controllers\Web\HelpController::class, 'index']);
    $router->get('/help/article/{slug}', [\App\Controllers\Web\HelpController::class, 'article']);
});
