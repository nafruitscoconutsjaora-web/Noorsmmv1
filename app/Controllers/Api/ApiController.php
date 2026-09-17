<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\OrderRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\Services\OrderService;

class ApiController extends BaseController
{
    private UserRepository $userRepo;
    private ServiceRepository $serviceRepo;
    private OrderRepository $orderRepo;
    private OrderService $orderService;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->orderRepo = new OrderRepository();
        $this->orderService = new OrderService();
    }

    public function handle(Request $request): Response
    {
        $apiKey = $request->input('key');
        if (empty($apiKey)) {
            return $this->json(['error' => 'API key is required'], 401);
        }

        $user = $this->userRepo->findByApiKey($apiKey);
        if (!$user) {
            return $this->json(['error' => 'Invalid API key'], 401);
        }

        if ($user['status'] === 'suspended') {
            return $this->json(['error' => 'Account is suspended'], 403);
        }

        $action = (string)$request->input('action', '');

        return match ($action) {
            'services' => $this->actionServices(),
            'add' => $this->actionAdd($request, $user),
            'status' => $this->actionStatus($request, $user),
            'balance' => $this->actionBalance($user),
            default => $this->json(['error' => 'Incorrect request / unknown action'], 400),
        };
    }

    private function actionServices(): Response
    {
        $services = $this->serviceRepo->getAllActiveWithCategory();
        $formatted = [];

        foreach ($services as $srv) {
            $formatted[] = [
                'service' => (int)$srv['id'],
                'name' => $srv['name'],
                'type' => $srv['service_type'],
                'category' => $srv['category_name'],
                'rate' => (string)$srv['rate'],
                'min' => (int)$srv['min_quantity'],
                'max' => (int)$srv['max_quantity'],
                'dripfeed' => (bool)$srv['drip_feed'],
                'refill' => (bool)$srv['refill'],
                'cancel' => (bool)$srv['cancel'],
            ];
        }

        return $this->json($formatted);
    }

    private function actionAdd(Request $request, array $user): Response
    {
        $serviceId = (int)$request->input('service');
        $link = (string)$request->input('link');
        $quantity = (int)$request->input('quantity');

        if ($serviceId <= 0 || empty($link) || $quantity <= 0) {
            return $this->json(['error' => 'Missing or invalid parameters (service, link, quantity)'], 422);
        }

        try {
            $order = $this->orderService->placeOrder((int)$user['id'], $serviceId, $link, $quantity);
            return $this->json(['order' => (int)$order['id']]);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function actionStatus(Request $request, array $user): Response
    {
        $orderId = $request->input('order');
        $ordersParam = $request->input('orders');

        // Multiple orders lookup
        if (!empty($ordersParam)) {
            $ids = is_array($ordersParam) ? $ordersParam : explode(',', (string)$ordersParam);
            $results = [];
            foreach ($ids as $id) {
                $id = (int)trim((string)$id);
                $order = $this->orderRepo->findById($id);
                if ($order && (int)$order['user_id'] === (int)$user['id']) {
                    $results[$id] = [
                        'charge' => (string)$order['charge'],
                        'start_count' => (string)($order['start_counter'] ?? '0'),
                        'status' => strtoupper($order['status']),
                        'remains' => (string)($order['remains'] ?? '0'),
                        'currency' => 'INR',
                    ];
                } else {
                    $results[$id] = ['error' => 'Incorrect order ID'];
                }
            }
            return $this->json($results);
        }

        if (empty($orderId)) {
            return $this->json(['error' => 'Order ID is required'], 422);
        }

        $order = $this->orderRepo->findById((int)$orderId);
        if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
            return $this->json(['error' => 'Incorrect order ID'], 404);
        }

        return $this->json([
            'charge' => (string)$order['charge'],
            'start_count' => (string)($order['start_counter'] ?? '0'),
            'status' => strtoupper($order['status']),
            'remains' => (string)($order['remains'] ?? '0'),
            'currency' => 'INR',
        ]);
    }

    private function actionBalance(array $user): Response
    {
        // Re-read latest balance from db
        $fresh = $this->userRepo->findById((int)$user['id']);
        return $this->json([
            'balance' => (string)($fresh['balance'] ?? '0.00'),
            'currency' => 'INR',
        ]);
    }
}
