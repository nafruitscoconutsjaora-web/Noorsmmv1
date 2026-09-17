<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AuditLogRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ServiceRepository;
use App\Services\OrderService;
use App\Services\ProviderService;

class AdminOrderController extends BaseController
{
    private OrderRepository $orderRepo;
    private OrderService $orderService;
    private ProviderRepository $providerRepo;
    private ServiceRepository $serviceRepo;
    private ProviderService $providerService;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
        $this->orderService = new OrderService();
        $this->providerRepo = new ProviderRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->providerService = new ProviderService();
        $this->auditRepo = new AuditLogRepository();
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query('page', 1));
        $status = (string)$request->query('status', 'all');
        $search = trim((string)$request->query('search', ''));

        $orders = $this->orderRepo->getAdminPaginated($page, 20, $status, $search);

        return view('admin/orders/index', [
            'orders' => $orders,
            'current_status' => $status,
            'search' => $search,
        ], 'admin');
    }

    public function show(Request $request, string $id): Response
    {
        $orderId = (int)$id;
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            Session::setFlash('error', 'Order not found.');
            return $this->redirect('/admin/orders');
        }

        $history = $this->orderRepo->getStatusHistory($orderId);

        return view('admin/orders/show', [
            'order' => $order,
            'history' => $history,
        ], 'admin');
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $orderId = (int)$id;
        $status = (string)$request->post('status');
        $note = (string)$request->post('note', '');

        $valid = ['pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'failed'];
        if (!in_array($status, $valid, true)) {
            Session::setFlash('error', 'Invalid status selected.');
            return $this->redirect("/admin/orders/{$orderId}");
        }

        $admin = $this->admin();

        if ($status === 'cancelled' || $status === 'refunded') {
            $this->orderService->cancelOrder($orderId, $note ?: 'Cancelled by administrator', 'admin:' . $admin['username']);
        } else {
            $this->orderRepo->updateStatus($orderId, $status, $note ?: 'Status updated by administrator', 'admin:' . $admin['username']);
        }

        $this->auditRepo->log((int)$admin['id'], 'update_order_status', 'order', $orderId, ['status' => $status, 'note' => $note], $request->ip());

        Session::setFlash('success', "Order #{$orderId} status updated to " . ucfirst($status));
        return $this->redirect("/admin/orders/{$orderId}");
    }

    public function retryProvider(Request $request, string $id): Response
    {
        $orderId = (int)$id;
        $order = $this->orderRepo->findById($orderId);
        if (!$order || empty($order['provider_id'])) {
            Session::setFlash('error', 'Order does not have an active provider mapped.');
            return $this->redirect('/admin/orders');
        }

        $provider = $this->providerRepo->findById((int)$order['provider_id']);
        $service = $this->serviceRepo->findById((int)$order['service_id']);

        if (!$provider || !$service) {
            Session::setFlash('error', 'Provider or service configuration missing.');
            return $this->redirect("/admin/orders/{$orderId}");
        }

        $dispatch = $this->providerService->sendOrder($provider, $order, $service);
        if ($dispatch['success']) {
            $this->orderRepo->updateProviderInfo($orderId, $dispatch['provider_order_id'], $dispatch['response']);
            $this->orderRepo->updateStatus($orderId, 'in_progress', 'Resent to upstream provider', 'admin:' . $this->admin()['username']);
            Session::setFlash('success', "Order dispatched to provider! Provider Order ID: {$dispatch['provider_order_id']}");
        } else {
            $this->orderRepo->updateProviderInfo($orderId, null, $dispatch['response'], $dispatch['error']);
            Session::setFlash('error', "Provider dispatch failed: {$dispatch['error']}");
        }

        return $this->redirect("/admin/orders/{$orderId}");
    }
}
