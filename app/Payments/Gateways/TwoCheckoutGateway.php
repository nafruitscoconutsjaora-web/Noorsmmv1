<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class TwoCheckoutGateway extends AbstractGateway
{
    protected string $code = 'twocheckout';
    protected string $name = '2Checkout / Verifone';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_code' => [
                'label' => 'Merchant Code',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter 2Checkout Merchant Code',
                'help' => 'Assigned numeric/alphanumeric merchant code.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter 2Checkout Secret Key',
                'help' => 'Secret key for API authorization.',
            ],
            'buy_link_secret' => [
                'label' => 'Buy-Link Secret Word',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Buy-Link Secret Word',
                'help' => 'Secret word configured in Webhooks & API -> Buy-link secret.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $merchantCode = trim((string)($creds['merchant_code'] ?? ''));
        $secretWord = trim((string)($creds['buy_link_secret'] ?? ''));

        if (empty($merchantCode)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => '2Checkout credentials are not configured.',
            ];
        }

        $orderId = '2CO_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');

        $params = [
            'merchant' => $merchantCode,
            'order-ext-ref' => $orderId,
            'item-ext-ref' => 'WALLET',
            'item-name' => 'Wallet Deposit #' . $paymentData['order_id'],
            'item-price' => $amount,
            'item-currency' => $currency,
            'return-url' => $paymentData['return_url'],
            'return-type' => 'redirect',
            'customer-ext-ref' => (string)($paymentData['user']['id'] ?? '1'),
            'name' => $paymentData['customer_name'] ?? 'Customer',
            'email' => $paymentData['customer_email'] ?? 'customer@example.com',
        ];

        $redirectUrl = 'https://secure.2checkout.com/checkout/buy?' . http_build_query($params);

        return [
            'success' => true,
            'action_type' => 'redirect',
            'redirect_url' => $redirectUrl,
            'gateway_order_id' => $orderId,
            'checkout_data' => $params,
            'message' => '2Checkout buy-link prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $orderRef = (string)$request->input('refno', $request->input('order-ext-ref', ''));
        $total = (string)$request->input('total', $request->input('item-price', '0'));

        if (!empty($orderRef)) {
            return [
                'success' => true,
                'gateway_order_id' => $orderRef,
                'transaction_id' => (string)$request->input('refno', $orderRef),
                'amount' => $total,
                'currency' => (string)$request->input('currency', 'USD'),
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $request->all(),
            'error' => '2Checkout verification failed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
