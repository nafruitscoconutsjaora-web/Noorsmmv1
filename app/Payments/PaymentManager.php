<?php

declare(strict_types=1);

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Drivers\RazorpayGateway;
use App\Exceptions\AppException;

class PaymentManager
{
    private static ?self $instance = null;
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    private function __construct()
    {
        $this->registerDefaultGateways();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerDefaultGateways(): void
    {
        $this->register(new RazorpayGateway());
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getName()] = $gateway;
    }

    public function gateway(string $name = 'razorpay'): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new AppException("Payment gateway '{$name}' is not registered.");
        }
        return $this->gateways[$name];
    }

    /**
     * @return array<string, PaymentGatewayInterface>
     */
    public function all(): array
    {
        return $this->gateways;
    }
}
