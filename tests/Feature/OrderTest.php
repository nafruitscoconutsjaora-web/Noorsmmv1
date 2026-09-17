<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use App\Services\WalletService;
use App\Support\Money;

class OrderTest
{
    public function run(): void
    {
        $userRepo = new UserRepository();
        $serviceRepo = new ServiceRepository();
        $orderRepo = new OrderRepository();
        $orderService = new OrderService();
        $walletService = new WalletService();

        $user = $userRepo->findByUsername('demo');
        assert($user !== null, 'Demo user must exist for OrderTest');

        $services = $serviceRepo->getAllActiveWithCategory();
        assert(!empty($services), 'Active services must exist');
        $testService = $services[0];

        $initialBalance = $walletService->getBalance((int)$user['id']);

        // Quantity: min_quantity
        $qty = (int)$testService['min_quantity'];
        $order = $orderService->placeOrder(
            (int)$user['id'],
            (int)$testService['id'],
            'https://instagram.com/test_account_verify',
            $qty
        );

        assert(!empty($order['id']), 'Order placement failed to generate order id');
        assert($order['status'] === 'pending' || $order['status'] === 'in_progress', 'Unexpected order initial status');

        // Check new balance is decremented by charge
        $newBalance = $walletService->getBalance((int)$user['id']);
        $expectedBalance = Money::sub($initialBalance, (string)$order['charge']);
        assert(Money::eq($newBalance, $expectedBalance), "Balance mismatch: expected {$expectedBalance}, got {$newBalance}");

        // Verify order exists in repository
        $savedOrder = $orderRepo->findById((int)$order['id']);
        assert($savedOrder !== null, 'Saved order could not be retrieved from DB');
        assert((int)$savedOrder['user_id'] === (int)$user['id'], 'User ID does not match on order');
    }
}
