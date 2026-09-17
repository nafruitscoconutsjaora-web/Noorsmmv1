<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class WorldlineGateway extends AbstractGateway
{
    protected string $code = 'worldline';
    protected string $name = 'Worldline';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_code' => [
                'label' => 'Merchant Code',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Worldline Merchant Code',
                'help' => 'Assigned Worldline merchant code.',
            ],
            'encryption_key' => [
                'label' => 'Encryption Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Encryption Key',
                'help' => 'Key for encrypting request payload.',
            ],
            'salt' => [
                'label' => 'Checksum / Salt Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Salt',
                'help' => 'Salt for SHA-256 token verification.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $merchantCode = trim((string)($creds['merchant_code'] ?? ''));
        $salt = trim((string)($creds['salt'] ?? ''));

        if (empty($merchantCode) || empty($salt)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Worldline credentials are not configured.',
            ];
        }

        $orderId = 'WL_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $date = date('d-m-Y');

        // Checksum: merchantCode|orderId|amount|||||||||||salt
        $checksum = hash('sha256', "{$merchantCode}|{$orderId}|{$amount}|{$date}|{$salt}");

        return [
            'success' => true,
            'action_type' => 'form',
            'form_action' => 'https://ipg.worldline-india.com/opps/MerchantPaymentRequest',
            'form_method' => 'POST',
            'form_fields' => [
                'mrctCode' => $merchantCode,
                'orderId' => $orderId,
                'amount' => $amount,
                'currency' => 'INR',
                'txnDate' => $date,
                'returnUrl' => $paymentData['return_url'],
                'checkSum' => $checksum,
                'custEmail' => $paymentData['customer_email'] ?? '',
                'custMobile' => $paymentData['customer_phone'] ?? '',
            ],
            'gateway_order_id' => $orderId,
            'message' => 'Worldline payment prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $status = (string)$request->input('status', $request->input('statusCode', ''));
        $orderId = (string)$request->input('orderId', '');
        $amount = (string)$request->input('amount', '0');
        $txnId = (string)$request->input('pgTxnNo', $orderId);

        if ($status === 'SUCCESS' || $status === '0300' || $status === 'F') {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $txnId,
                'amount' => $amount,
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => $txnId,
            'amount' => $amount,
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => 'Worldline payment unsuccessful: ' . ($request->input('statusDesc') ?: $status),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
