<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Get the unique gateway identifier slug
     */
    public function getName(): string;

    /**
     * Initialize a payment intent/order
     *
     * @param int $userId
     * @param string $amount Formatted decimal amount (e.g. "500.00")
     * @param string $currency E.g. "INR", "USD"
     * @param array $metadata Additional options
     * @return array Gateway response parameters (order id, client keys, checkout payload)
     */
    public function initiatePayment(int $userId, string $amount, string $currency = 'INR', array $metadata = []): array;

    /**
     * Verify payment signature / callback data
     *
     * @param array $payload Callback data from frontend or gateway
     * @return bool
     */
    public function verifyPayment(array $payload): bool;

    /**
     * Handle incoming asynchronous webhook notifications
     *
     * @param string $rawPayload
     * @param string $signatureHeader
     * @return array [verified => bool, gateway_order_id => string, transaction_id => string, event => string]
     */
    public function handleWebhook(string $rawPayload, string $signatureHeader): array;
}
