<?php

declare(strict_types=1);

namespace App\Payments;

use App\Core\Request;

interface PaymentGatewayInterface
{
    /**
     * Unique identifier code for this gateway (e.g. 'razorpay', 'stripe', 'cashfree').
     */
    public function getCode(): string;

    /**
     * Human-readable gateway brand name.
     */
    public function getName(): string;

    /**
     * Gateway category (e.g. 'india', 'international', 'regional', 'crypto', 'custom').
     */
    public function getCategory(): string;

    /**
     * Default ISO-4217 currency code (e.g. 'INR', 'USD', 'EUR').
     */
    public function getDefaultCurrency(): string;

    /**
     * List of supported ISO-4217 currency codes.
     */
    public function getSupportedCurrencies(): array;

    /**
     * Schema definition of required and optional credentials for this gateway.
     * Returns an associative array of field definitions:
     * [
     *     'key' => [
     *         'label' => 'API Key ID',
     *         'type' => 'text' | 'password' | 'textarea' | 'select',
     *         'required' => true,
     *         'placeholder' => 'rzp_live_...',
     *         'help' => 'Obtained from your merchant dashboard.'
     *     ]
     * ]
     */
    public function getCredentialFields(): array;

    /**
     * Check whether this gateway is adequately configured with required credentials to accept payments.
     */
    public function isConfigured(array $credentials, array $config = []): bool;

    /**
     * Initiate a payment transaction with the external provider.
     * 
     * @param array $paymentData [
     *     'user' => array,
     *     'order_id' => string,
     *     'amount' => string,
     *     'payable_amount' => string,
     *     'currency' => string,
     *     'bonus' => string,
     *     'fee' => string,
     *     'return_url' => string,
     *     'cancel_url' => string,
     *     'notify_url' => string,
     *     'customer_name' => string,
     *     'customer_email' => string,
     *     'customer_phone' => string,
     * ]
     * @param array $gatewayRow The full database record from `payment_gateways`
     * 
     * @return array [
     *     'success' => bool,
     *     'action_type' => 'redirect' | 'form' | 'qr' | 'sdk' | 'instructions',
     *     'redirect_url' => ?string,
     *     'form_action' => ?string,
     *     'form_method' => 'POST' | 'GET',
     *     'form_fields' => array,
     *     'gateway_order_id' => ?string,
     *     'checkout_data' => array,
     *     'message' => ?string,
     *     'error' => ?string,
     * ]
     */
    public function initiatePayment(array $paymentData, array $gatewayRow): array;

    /**
     * Verify payment return/callback from browser.
     * 
     * @param Request $request
     * @param array $gatewayRow
     * 
     * @return array [
     *     'success' => bool,
     *     'gateway_order_id' => string,
     *     'transaction_id' => string,
     *     'amount' => float|string,
     *     'currency' => string,
     *     'raw_response' => array|string,
     *     'error' => ?string,
     * ]
     */
    public function verifyPayment(Request $request, array $gatewayRow): array;

    /**
     * Handle asynchronous webhook notification directly from payment provider.
     * 
     * @param Request $request
     * @param array $gatewayRow
     * 
     * @return array [
     *     'success' => bool,
     *     'gateway_order_id' => string,
     *     'transaction_id' => string,
     *     'amount' => float|string,
     *     'currency' => string,
     *     'status' => 'success' | 'failed' | 'pending',
     *     'raw_payload' => array|string,
     *     'error' => ?string,
     * ]
     */
    public function handleWebhook(Request $request, array $gatewayRow): array;
}
