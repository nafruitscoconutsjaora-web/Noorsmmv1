<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class AuthorizeNetGateway extends AbstractGateway
{
    protected string $code = 'authorizenet';
    protected string $name = 'Authorize.Net';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'CAD', 'GBP', 'EUR'];

    public function getCredentialFields(): array
    {
        return [
            'api_login_id' => [
                'label' => 'API Login ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter API Login ID',
                'help' => 'From Authorize.Net Settings -> API Credentials & Keys.',
            ],
            'transaction_key' => [
                'label' => 'Transaction Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Transaction Key',
                'help' => 'Secret Transaction Key.',
            ],
            'signature_key' => [
                'label' => 'Signature Key',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'Enter Signature Key',
                'help' => 'For Webhook signature verification.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api2.authorize.net)',
                    'sandbox' => 'Sandbox (apitest.authorize.net)',
                ],
                'help' => 'Authorize.Net environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'sandbox'
            ? 'https://apitest.authorize.net/xml/v1/request.api'
            : 'https://api2.authorize.net/xml/v1/request.api';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $loginId = trim((string)($creds['api_login_id'] ?? ''));
        $txnKey = trim((string)($creds['transaction_key'] ?? ''));

        if (empty($loginId) || empty($txnKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Authorize.Net credentials are not configured.',
            ];
        }

        $orderId = 'AN_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $endpoint = $this->getBaseUrl($creds);

        // Request Hosted Payment Form token (Accept Hosted)
        $payload = [
            'getHostedPaymentPageRequest' => [
                'merchantAuthentication' => [
                    'name' => $loginId,
                    'transactionKey' => $txnKey,
                ],
                'transactionRequest' => [
                    'transactionType' => 'authCaptureTransaction',
                    'amount' => $amount,
                    'order' => [
                        'invoiceNumber' => $orderId,
                        'description' => 'Wallet Deposit #' . $paymentData['order_id'],
                    ],
                ],
                'hostedPaymentSettings' => [
                    'setting' => [
                        [
                            'settingName' => 'hostedPaymentReturnOptions',
                            'settingValue' => json_encode([
                                'showReceipt' => true,
                                'url' => $paymentData['return_url'],
                                'urlText' => 'Continue to SMM Panel',
                                'cancelUrl' => $paymentData['cancel_url'],
                                'cancelUrlText' => 'Cancel and Return',
                            ]),
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->httpRequest('POST', $endpoint, $payload, ['Content-Type: application/json']);

        if ($res['success'] && !empty($res['data']['token'])) {
            $token = $res['data']['token'];
            $env = $creds['environment'] ?? 'production';
            $formUrl = $env === 'sandbox'
                ? 'https://test.authorize.net/payment/payment'
                : 'https://accept.authorize.net/payment/payment';

            return [
                'success' => true,
                'action_type' => 'form',
                'form_action' => $formUrl,
                'form_method' => 'POST',
                'form_fields' => [
                    'token' => $token,
                ],
                'gateway_order_id' => $orderId,
                'checkout_data' => ['token' => $token],
                'message' => 'Authorize.Net Accept Hosted token generated.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Authorize.Net token error: ' . ($res['data']['messages']['message'][0]['text'] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $responseCode = (string)$request->input('responseCode', '1');
        $transId = (string)$request->input('transId', $request->input('order_id', ''));

        if ($responseCode === '1') {
            return [
                'success' => true,
                'gateway_order_id' => $transId,
                'transaction_id' => $transId,
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $transId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $request->all(),
            'error' => 'Authorize.Net payment declined.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
