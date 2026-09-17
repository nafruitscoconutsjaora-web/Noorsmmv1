<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Repositories\OrderRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\Support\Logger;
use App\Support\Money;

class OrderService
{
    private Database $db;
    private OrderRepository $orderRepo;
    private ServiceRepository $serviceRepo;
    private UserRepository $userRepo;
    private ProviderRepository $providerRepo;
    private WalletService $walletService;
    private PricingService $pricingService;
    private ProviderService $providerService;

    public function __construct(
        ?Database $db = null,
        ?OrderRepository $orderRepo = null,
        ?ServiceRepository $serviceRepo = null,
        ?UserRepository $userRepo = null,
        ?ProviderRepository $providerRepo = null,
        ?WalletService $walletService = null,
        ?PricingService $pricingService = null,
        ?ProviderService $providerService = null
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->orderRepo = $orderRepo ?? new OrderRepository($this->db);
        $this->serviceRepo = $serviceRepo ?? new ServiceRepository($this->db);
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
        $this->providerRepo = $providerRepo ?? new ProviderRepository($this->db);
        $this->walletService = $walletService ?? new WalletService($this->db);
        $this->pricingService = $pricingService ?? new PricingService($this->db);
        $this->providerService = $providerService ?? new ProviderService($this->db);
    }

    /**
     * Place a new order with atomic balance deduction and provider dispatch
     */
    public function placeOrder(int $userId, int $serviceId, string $link, int $quantity): array
    {
        $service = $this->serviceRepo->findById($serviceId);
        if (!$service || $service['status'] !== 'active') {
            throw new ValidationException(['service_id' => 'Selected service is currently inactive or unavailable.']);
        }

        if ($quantity < (int)$service['min_quantity']) {
            throw new ValidationException(['quantity' => "Minimum quantity for this service is {$service['min_quantity']}."]);
        }

        if ($quantity > (int)$service['max_quantity']) {
            throw new ValidationException(['quantity' => "Maximum quantity for this service is {$service['max_quantity']}."]);
        }

        $link = trim($link);
        if (empty($link) || !filter_var($link, FILTER_VALIDATE_URL)) {
            throw new ValidationException(['link' => 'A valid URL link is required for order target.']);
        }

        // Calculate total cost
        $charge = $this->pricingService->calculateOrderCharge($service['rate'], $quantity);

        // Atomic transaction: lock user, debit wallet, insert order
        $orderId = $this->db->transaction(function () use ($userId, $service, $link, $quantity, $charge) {
            $user = $this->userRepo->findByIdForUpdate($userId);
            if (!$user) {
                throw new AppException('User account not found.');
            }

            if (Money::lt($user['balance'], $charge)) {
                $diff = Money::sub($charge, $user['balance']);
                throw new AppException("Insufficient balance. You need ₹" . number_format((float)$diff, 2) . " more to place this order.");
            }

            // Create pending order
            $orderId = $this->orderRepo->create([
                'user_id' => $userId,
                'service_id' => $service['id'],
                'provider_id' => $service['provider_id'] ?? null,
                'provider_order_id' => null,
                'link' => $link,
                'quantity' => $quantity,
                'charge' => $charge,
                'status' => 'pending',
                'provider_response' => null,
            ]);

            // Atomic debit
            $this->walletService->debit(
                $userId,
                $charge,
                'order',
                $orderId,
                "Payment for Order #{$orderId} ({$service['name']})"
            );

            return $orderId;
        });

        // If service is automated through an upstream provider, dispatch immediately
        $order = $this->orderRepo->findById($orderId);
        if (!empty($service['provider_id']) && !empty($service['provider_service_id'])) {
            $provider = $this->providerRepo->findById((int)$service['provider_id']);
            if ($provider && $provider['status'] === 'active') {
                $dispatch = $this->providerService->sendOrder($provider, $order, $service);
                if ($dispatch['success']) {
                    $this->orderRepo->updateProviderInfo($orderId, $dispatch['provider_order_id'], $dispatch['response']);
                    $this->orderRepo->updateStatus($orderId, 'in_progress', 'Order accepted by upstream provider');
                } else {
                    $this->orderRepo->updateProviderInfo($orderId, null, $dispatch['response'], $dispatch['error']);
                    Logger::warning("Order #{$orderId} placed locally but upstream provider dispatch failed: {$dispatch['error']}", [], 'orders');
                }
            }
        }

        return $this->orderRepo->findById($orderId);
    }

    /**
     * Cancel an order and automatically refund the user
     */
    public function cancelOrder(int $orderId, ?string $reason = null, string $cancelledBy = 'admin'): bool
    {
        return $this->db->transaction(function () use ($orderId, $reason, $cancelledBy) {
            $order = $this->orderRepo->findByIdForUpdate($orderId);
            if (!$order) {
                throw new AppException("Order #{$orderId} not found");
            }

            if (in_array($order['status'], ['cancelled', 'refunded'], true)) {
                return true; // Already processed
            }

            // Refund user
            $this->walletService->refund(
                (int)$order['user_id'],
                $order['charge'],
                'order_refund',
                $orderId,
                "Full refund for cancelled Order #{$orderId}" . ($reason ? ": {$reason}" : "")
            );

            $this->orderRepo->updateStatus($orderId, 'cancelled', $reason ?? 'Order cancelled and refunded', $cancelledBy);
            return true;
        });
    }

    /**
     * Process partial completion and partial refund
     */
    public function partialOrder(int $orderId, int $remains, ?string $reason = null): bool
    {
        return $this->db->transaction(function () use ($orderId, $remains, $reason) {
            $order = $this->orderRepo->findByIdForUpdate($orderId);
            if (!$order) {
                throw new AppException("Order #{$orderId} not found");
            }

            if (in_array($order['status'], ['cancelled', 'refunded', 'partial'], true)) {
                return true;
            }

            $quantity = (int)$order['quantity'];
            if ($remains > 0 && $remains <= $quantity) {
                // Calculate refund for undelivered portion
                // refund = (charge * remains) / quantity
                $remainsRatio = Money::div((string)$remains, (string)$quantity);
                $refundAmount = Money::mul($order['charge'], $remainsRatio);

                if (Money::gt($refundAmount, '0')) {
                    $this->walletService->refund(
                        (int)$order['user_id'],
                        $refundAmount,
                        'order_partial_refund',
                        $orderId,
                        "Partial refund for Order #{$orderId} ({$remains} undelivered items)"
                    );
                }
            }

            $this->orderRepo->updateCounters($orderId, null, $remains);
            $this->orderRepo->updateStatus($orderId, 'partial', $reason ?? "Order completed partially ({$remains} remaining)");
            return true;
        });
    }
}
