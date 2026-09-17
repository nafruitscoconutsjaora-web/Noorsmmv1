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

    // Payments & Payment Gateways Management
    $router->get('/admin/payments', [AdminPaymentController::class, 'index']);
    $router->get('/admin/gateways', [\App\Controllers\Admin\AdminGatewayController::class, 'index']);
    $router->get('/admin/gateways/{id}/edit', [\App\Controllers\Admin\AdminGatewayController::class, 'edit']);
    $router->post('/admin/gateways/{id}/update', [\App\Controllers\Admin\AdminGatewayController::class, 'update'], [CsrfMiddleware::class]);
    $router->post('/admin/gateways/{id}/toggle', [\App\Controllers\Admin\AdminGatewayController::class, 'toggle'], [CsrfMiddleware::class]);

    // Support Tickets
    $router->get('/admin/tickets', [AdminTicketController::class, 'index']);
    $router->get('/admin/tickets/{id}', [AdminTicketController::class, 'show']);
    $router->post('/admin/tickets/{id}/reply', [AdminTicketController::class, 'reply'], [CsrfMiddleware::class]);
    $router->post('/admin/tickets/{id}/status', [AdminTicketController::class, 'updateStatus'], [CsrfMiddleware::class]);

    // Settings
    $router->get('/admin/settings', [AdminSettingController::class, 'index']);
    $router->post('/admin/settings', [AdminSettingController::class, 'update'], [CsrfMiddleware::class]);

    // --- PART 2 ADMIN FEATURES ---
    // 1. Advanced Provider Management & Health
    $router->get('/admin/providers/{id}/importer', [\App\Controllers\Admin\AdminImporterController::class, 'index']);
    $router->post('/admin/providers/{id}/importer', [\App\Controllers\Admin\AdminImporterController::class, 'import'], [CsrfMiddleware::class]);

    // 3. Pricing & Markup Management
    $router->get('/admin/pricing', [\App\Controllers\Admin\AdminPricingController::class, 'index']);
    $router->post('/admin/pricing/global', [\App\Controllers\Admin\AdminPricingController::class, 'applyGlobal'], [CsrfMiddleware::class]);
    $router->post('/admin/pricing/category/{id}', [\App\Controllers\Admin\AdminPricingController::class, 'applyCategory'], [CsrfMiddleware::class]);

    // 5. Payment & Reconciliation
    $router->get('/admin/payments/reconciliation', [\App\Controllers\Admin\AdminReconciliationController::class, 'index']);
    $router->post('/admin/payments/{id}/reconcile', [\App\Controllers\Admin\AdminReconciliationController::class, 'reconcile'], [CsrfMiddleware::class]);

    // 6. Advanced Wallet Management & Ledger
    $router->get('/admin/wallets', [\App\Controllers\Admin\AdminWalletManagementController::class, 'index']);
    $router->post('/admin/wallets/adjust', [\App\Controllers\Admin\AdminWalletManagementController::class, 'adjust'], [CsrfMiddleware::class]);

    // 7. Cron Management
    $router->get('/admin/cron', [\App\Controllers\Admin\AdminCronController::class, 'index']);
    $router->post('/admin/cron/{id}/toggle', [\App\Controllers\Admin\AdminCronController::class, 'toggle'], [CsrfMiddleware::class]);
    $router->post('/admin/cron/{id}/trigger', [\App\Controllers\Admin\AdminCronController::class, 'trigger'], [CsrfMiddleware::class]);

    // 8 & 9. Advanced Analytics Dashboard & User Behavior
    $router->get('/admin/analytics', [\App\Controllers\Admin\AdminAnalyticsController::class, 'index']);
    $router->get('/admin/analytics/users', [\App\Controllers\Admin\AdminAnalyticsController::class, 'users']);

    // 10. Notification Management
    $router->get('/admin/notifications', [\App\Controllers\Admin\AdminNotificationController::class, 'index']);
    $router->post('/admin/notifications/send', [\App\Controllers\Admin\AdminNotificationController::class, 'send'], [CsrfMiddleware::class]);

    // 12. Referral & Commission Management
    $router->get('/admin/referrals', [\App\Controllers\Admin\AdminReferralController::class, 'index']);
    $router->post('/admin/referrals/rate', [\App\Controllers\Admin\AdminReferralController::class, 'updateCommissionRate'], [CsrfMiddleware::class]);
    $router->post('/admin/referrals/{id}/payout', [\App\Controllers\Admin\AdminReferralController::class, 'processPayout'], [CsrfMiddleware::class]);

    // 13. Service Quality Management
    $router->get('/admin/services/quality', [\App\Controllers\Admin\AdminServiceQualityController::class, 'index']);

    // 14. Auto-Refill & Cancellation Management
    $router->get('/admin/refills', [\App\Controllers\Admin\AdminRefillController::class, 'index']);
    $router->post('/admin/refills/{id}/status', [\App\Controllers\Admin\AdminRefillController::class, 'updateRefill'], [CsrfMiddleware::class]);
    $router->post('/admin/cancellations/{id}/status', [\App\Controllers\Admin\AdminRefillController::class, 'updateCancellation'], [CsrfMiddleware::class]);

    // 15. Subscription & Scheduled Order Management
    $router->get('/admin/schedules', [\App\Controllers\Admin\AdminScheduleController::class, 'index']);
    $router->post('/admin/schedules/{id}/toggle', [\App\Controllers\Admin\AdminScheduleController::class, 'toggle'], [CsrfMiddleware::class]);

    // 16. Website Content Management (FAQs, KB)
    $router->get('/admin/content', [\App\Controllers\Admin\AdminContentController::class, 'index']);
    $router->post('/admin/content/article', [\App\Controllers\Admin\AdminContentController::class, 'saveArticle'], [CsrfMiddleware::class]);
    $router->post('/admin/content/faq', [\App\Controllers\Admin\AdminContentController::class, 'saveFaq'], [CsrfMiddleware::class]);

    // 17. Email & Communication Management
    $router->get('/admin/email', [\App\Controllers\Admin\AdminEmailController::class, 'index']);
    $router->post('/admin/email/template/{id}', [\App\Controllers\Admin\AdminEmailController::class, 'updateTemplate'], [CsrfMiddleware::class]);
    $router->post('/admin/email/test', [\App\Controllers\Admin\AdminEmailController::class, 'testEmail'], [CsrfMiddleware::class]);

    // 18. System Health Dashboard
    $router->get('/admin/system/health', [\App\Controllers\Admin\AdminHealthController::class, 'index']);

    // 19. Reports & Data Export
    $router->get('/admin/reports', [\App\Controllers\Admin\AdminReportsController::class, 'index']);
    $router->get('/admin/reports/{type}/download', [\App\Controllers\Admin\AdminReportsController::class, 'download']);

    // 20. Admin Roles & Permissions RBAC
    $router->get('/admin/roles', [\App\Controllers\Admin\AdminRoleController::class, 'index']);
    $router->post('/admin/roles/{id}/permissions', [\App\Controllers\Admin\AdminRoleController::class, 'updatePermissions'], [CsrfMiddleware::class]);
    $router->post('/admin/roles/staff', [\App\Controllers\Admin\AdminRoleController::class, 'assignStaffRole'], [CsrfMiddleware::class]);

    // 21. Fraud & Abuse Monitoring
    $router->get('/admin/fraud', [\App\Controllers\Admin\AdminFraudController::class, 'index']);

    // 22. Maintenance & Backup Management
    $router->get('/admin/maintenance', [\App\Controllers\Admin\AdminMaintenanceController::class, 'index']);
    $router->post('/admin/maintenance/toggle', [\App\Controllers\Admin\AdminMaintenanceController::class, 'toggleMaintenance'], [CsrfMiddleware::class]);
    $router->post('/admin/maintenance/backup', [\App\Controllers\Admin\AdminMaintenanceController::class, 'createBackup'], [CsrfMiddleware::class]);

    // Global API Monitoring
    $router->get('/admin/api-management', [\App\Controllers\Admin\AdminApiCenterController::class, 'index']);
});
