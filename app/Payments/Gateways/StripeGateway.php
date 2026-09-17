<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class StripeGateway extends AbstractGateway
{
    protected string $code = 'stripe';
    protected string $name = 'Stripe';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'INR', 'CAD', 'AUD', 'SGD', 'JPY'];

    public function getCredentialFields(): array
    {
        return [
            'publishable_key' => [
                'label' => 'Publishable Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'pk_live_...',
                'help' => 'Stripe Publishable Key.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'sk_live_...',
                'help' => 'Stripe Secret Key.',
            ],
            'webhook_secret' => [
                'label' => 'Webhook Signing Secret',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'whsec_...',
                'help' => 'Secret from Stripe Webhooks dashboard.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Stripe secret key is not configured.',
            ];
        }

        $currency = strtolower($paymentData['currency'] ?? 'usd');
        $multiplier = in_array($currency, ['jpy', 'krw', 'vnd']) ? 1 : 100;
        $unitAmount = (int)round((float)$paymentData['payable_amount'] * $multiplier);

        $payload = [
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'client_reference_id' => $paymentData['order_id'],
            'customer_email' => $paymentData['customer_email'] ?? null,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $unitAmount,
                        'product_data' => [
                            'name' => 'Wallet Deposit #' . $paymentData['order_id'],
                            'description' => 'Account balance top-up',
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'success_url' => $paymentData['return_url'] . '?session_id={CHECKOUT_SESSION_ID}&order_id=' . $paymentData['order_id'],
            'cancel_url' => $paymentData['cancel_url'] . '?order_id=' . $paymentData['order_id'],
            'metadata' => [
                'order_id' => $paymentData['order_id'],
                'user_id' => (string)($paymentData['user']['id'] ?? ''),
            ],
        ];

        // Stripe API accepts standard form-urlencoded with nested arrays
        $encodedData = http_build_query($payload);

        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $res = $this->httpRequest('POST', 'https://api.stripe.com/v1/checkout/sessions', $encodedData, $headers);

        if ($res['success'] && !empty($res['data']['url'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['url'],
                'gateway_order_id' => (string)$res['data']['id'],
                'checkout_data' => [
                    'session_id' => $res['data']['id'],
                    'publishable_key' => $creds['publishable_key'] ?? '',
                ],
                'message' => 'Stripe checkout session initialized.',
            ];
        }

        $errMsg = $res['data']['error']['message'] ?? $res['error'] ?? 'Stripe request failed.';
        Logger::error("Stripe session error: {$errMsg}", [], 'payments');
        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Unable to initiate Stripe payment: ' . $errMsg,
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));
        $sessionId = (string)$request->input('session_id');

        if (empty($sessionId) || empty($secretKey)) {
            return [
                'success' => false,
                'gateway_order_id' => $sessionId,
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => 'Missing Stripe session identifier.',
            ];
        }

        $headers = [
            'Authorization: Bearer ' . $secretKey,
        ];

        $res = $this->httpRequest('GET', "https://api.stripe.com/v1/checkout/sessions/{$sessionId}", null, $headers);

        if ($res['success'] && !empty($res['data']['payment_status']) && $res['data']['payment_status'] === 'paid') {
            $currency = strtoupper($res['data']['currency'] ?? 'USD');
            $multiplier = in_array(strtolower($currency), ['jpy', 'krw', 'vnd']) ? 1 : 100;
            $amount = (string)(((float)($res['data']['amount_total'] ?? 0)) / $multiplier);
            $paymentIntent = (string)($res['data']['payment_intent'] ?? $sessionId);

            return [
                'success' => true,
                'gateway_order_id' => $sessionId,
                'transaction_id' => $paymentIntent,
                'amount' => $amount,
                'currency' => $currency,
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $sessionId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Stripe session not paid or pending.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $webhookSecret = trim((string)($creds['webhook_secret'] ?? ''));
        $rawPayload = (string)file_get_contents('php://input');
        $sigHeader = (string)$request->header('Stripe-Signature', '');

        if (!empty($webhookSecret) && !empty($sigHeader)) {
            // Signature verification: t=timestamp,v1=signature
            $items = explode(',', $sigHeader);
            $timestamp = null;
            $signature = null;
            foreach ($items as $item) {
                $parts = explode('=', trim($item), 2);
                if (count($parts) === 2) {
                    if ($parts[0] === 't') {
                        $timestamp = $parts[1];
                    } elseif ($parts[0] === 'v1') {
                        $signature = $parts[1];
                    }
                }
            }

            if ($timestamp && $signature) {
                $signedPayload = "{$timestamp}.{$rawPayload}";
                $expected = hash_hmac('sha256', $signedPayload, $webhookSecret);
                if (!hash_equals($expected, $signature)) {
                    return [
                        'success' => false,
                        'gateway_order_id' => '',
                        'transaction_id' => '',
                        'amount' => '0',
                        'currency' => 'USD',
                        'status' => 'failed',
                        'raw_payload' => $rawPayload,
                        'error' => 'Stripe webhook signature mismatch.',
                    ];
                }
            }
        }

        $event = json_decode($rawPayload, true);
        if (!$event || empty($event['type'])) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'status' => 'failed',
                'raw_payload' => $rawPayload,
                'error' => 'Malformed Stripe event payload.',
            ];
        }

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $currency = strtoupper($session['currency'] ?? 'USD');
            $multiplier = in_array(strtolower($currency), ['jpy', 'krw', 'vnd']) ? 1 : 100;
            $amount = (string)(((float)($session['amount_total'] ?? 0)) / $multiplier);

            return [
                'success' => true,
                'gateway_order_id' => (string)($session['id'] ?? ''),
                'transaction_id' => (string)($session['payment_intent'] ?? $session['id'] ?? ''),
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'success',
                'raw_payload' => $event,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'status' => 'pending',
            'raw_payload' => $event,
            'error' => 'Ignored event: ' . $event['type'],
        ];
    }
}
