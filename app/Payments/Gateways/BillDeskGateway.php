<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class BillDeskGateway extends AbstractGateway
{
    protected string $code = 'billdesk';
    protected string $name = 'BillDesk';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter BillDesk Merchant ID',
                'help' => 'Assigned BillDesk merchant identifier.',
            ],
            'client_id' => [
                'label' => 'Client ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter BillDesk Client ID',
                'help' => 'Client identification code.',
            ],
            'secret_key' => [
                'label' => 'Secret Key / Checksum Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Secret Key',
                'help' => 'Secret key for HMAC-SHA256 signature generation.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.billdesk.com)',
                    'uat' => 'UAT (uat1.billdesk.com)',
                ],
                'help' => 'BillDesk API environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'uat'
            ? 'https://uat1.billdesk.com/u2'
            : 'https://api.billdesk.com/u2';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mercid = trim((string)($creds['merchant_id'] ?? ''));
        $clientId = trim((string)($creds['client_id'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($mercid) || empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'BillDesk credentials are not configured.',
            ];
        }

        $orderId = 'BD_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $date = date('Y-m-d\TH:i:sP');

        $payload = [
            'mercid' => $mercid,
            'orderid' => $orderId,
            'amount' => $amount,
            'order_date' => $date,
            'currency' => '356', // INR ISO code
            'ru' => $paymentData['return_url'],
            'itemcode' => 'DIRECT',
            'device' => [
                'init_channel' => 'internet',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
            ],
        ];

        // Sign payload with HMAC-SHA256
        $header = base64_encode(json_encode(['alg' => 'HS256', 'clientid' => $clientId]));
        $body = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$header}.{$body}", $secretKey, true);
        $jwsToken = "{$header}.{$body}." . base64_encode($signature);

        $baseUrl = $this->getBaseUrl($creds);
        $headers = [
            'Content-Type' => 'application/jose',
            'Accept' => 'application/jose',
            'BD-Traceid' => 'TRC_' . bin2hex(random_bytes(8)),
            'BD-Timestamp' => (string)time(),
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/payments/ve1_2/orders/create", $jwsToken, $headers);

        if (!$res['success'] && !empty($res['raw'])) {
            // BillDesk returns JWS token on success or error
            $parts = explode('.', $res['raw']);
            if (count($parts) >= 2) {
                $decodedBody = json_decode(base64_decode($parts[1]), true);
                if (!empty($decodedBody['links'][1]['href'])) {
                    return [
                        'success' => true,
                        'action_type' => 'redirect',
                        'redirect_url' => $decodedBody['links'][1]['href'],
                        'gateway_order_id' => $orderId,
                        'checkout_data' => $decodedBody,
                        'message' => 'BillDesk order initiated.',
                    ];
                }
            }
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Unable to initialize BillDesk payment: Provider connection requires live authorization.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));
        $transactionResponse = (string)$request->input('transaction_response', $request->input('msg', ''));

        if (empty($transactionResponse) || empty($secretKey)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing BillDesk verification response.',
            ];
        }

        // BillDesk responses can be JWS or pipe-delimited
        $parts = explode('.', $transactionResponse);
        if (count($parts) === 3) {
            $expectedSig = base64_encode(hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", $secretKey, true));
            if (!hash_equals($expectedSig, $parts[2])) {
                return [
                    'success' => false,
                    'gateway_order_id' => '',
                    'transaction_id' => '',
                    'amount' => '0',
                    'currency' => 'INR',
                    'raw_response' => $request->all(),
                    'error' => 'Invalid BillDesk token signature.',
                ];
            }

            $data = json_decode(base64_decode($parts[1]), true);
            if (($data['auth_status'] ?? '') === '0300') {
                return [
                    'success' => true,
                    'gateway_order_id' => (string)($data['orderid'] ?? ''),
                    'transaction_id' => (string)($data['transactionid'] ?? ''),
                    'amount' => (string)($data['amount'] ?? '0'),
                    'currency' => 'INR',
                    'raw_response' => $data,
                    'error' => null,
                ];
            }
        }

        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => 'BillDesk transaction verification failed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
