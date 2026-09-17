<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CategoryRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ServiceRepository;
use App\Services\OrderService;

class OrderController extends BaseController
{
    private OrderService $orderService;
    private OrderRepository $orderRepo;
    private CategoryRepository $categoryRepo;
    private ServiceRepository $serviceRepo;

    public function __construct()
    {
        $this->orderService = new OrderService();
        $this->orderRepo = new OrderRepository();
        $this->categoryRepo = new CategoryRepository();
        $this->serviceRepo = new ServiceRepository();
    }

    public function create(Request $request): Response
    {
        $categories = $this->categoryRepo->getAllActive();
        $services = $this->serviceRepo->getAllActiveWithCategory();
        $preselectedServiceId = (int)$request->query('service_id', 0);

        return view('user/orders/new', [
            'categories' => $categories,
            'services' => $services,
            'preselected_service_id' => $preselectedServiceId,
        ], 'user');
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'service_id' => 'required|integer',
            'link' => 'required|url',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $this->user();
        $order = $this->orderService->placeOrder(
            (int)$user['id'],
            (int)$data['service_id'],
            $data['link'],
            (int)$data['quantity']
        );

        Session::setFlash('success', "Order #{$order['id']} placed successfully! Status: " . ucfirst($order['status']));
        return $this->redirect('/orders');
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $page = max(1, (int)$request->query('page', 1));
        $status = (string)$request->query('status', 'all');
        $search = trim((string)$request->query('search', ''));

        $orders = $this->orderRepo->getUserOrders((int)$user['id'], $page, 20, $status, $search);

        return view('user/orders/index', [
            'orders' => $orders,
            'current_status' => $status,
            'search' => $search,
        ], 'user');
    }

    public function cancel(Request $request, string $id): Response
    {
        $orderId = (int)$id;
        $order = $this->orderRepo->findById($orderId);
        $user = $this->user();

        if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
            Session::setFlash('error', 'Order not found.');
            return $this->redirect('/orders');
        }

        if ($order['status'] !== 'pending') {
            Session::setFlash('error', 'Only pending orders can be cancelled.');
            return $this->redirect('/orders');
        }

        $this->orderService->cancelOrder($orderId, 'Cancelled by user request', 'user');
        Session::setFlash('success', "Order #{$orderId} has been cancelled and refunded to your wallet.");
        return $this->redirect('/orders');
    }

    public function serviceInfo(Request $request, string $id): Response
    {
        $service = $this->serviceRepo->findById((int)$id);
        if (!$service) {
            return $this->json(['error' => 'Service not found'], 404);
        }

        return $this->json([
            'id' => $service['id'],
            'name' => $service['name'],
            'rate' => $service['rate'],
            'min' => (int)$service['min_quantity'],
            'max' => (int)$service['max_quantity'],
            'description' => $service['description'],
            'drip_feed' => (bool)$service['drip_feed'],
            'refill' => (bool)$service['refill'],
            'cancel' => (bool)$service['cancel'],
        ]);
    }
}
