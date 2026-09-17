<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;

class QrPaymentGateway extends AbstractGateway
{
    protected string $code = 'custom_qr';
    protected string $name = 'Custom QR / Static UPI';
    protected string $category = 'manual';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD', 'USDT', 'THB', 'VND'];

    public function getCredentialFields(): array
    {
        return [
            'qr_image_url' => [
                'label' => 'QR Code Image URL',
                'type' => 'text',
                'required' => true,
                'placeholder' => '/uploads/qr/upi_scanner.png or https://...',
                'help' => 'Public URL or local path to your static QR image.',
            ],
            'upi_id_or_address' => [
                'label' => 'VPA / Address / Account ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'e.g. merchant@icici or TRC20 Wallet Address',
                'help' => 'Payment address displayed alongside QR code.',
            ],
            'payee_name' => [
                'label' => 'Payee Name / Title',
                'type' => 'text',
                'required' => false,
                'placeholder' => 'e.g. SMM Fast Pay',
                'help' => 'Name displayed on customer scanner.',
            ],
            'instructions' => [
                'label' => 'Instructions for Customer',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => 'Scan QR with GPay, PhonePe, Paytm or any UPI app. Enter 12-digit UTR below.',
                'help' => 'Clear payment guidelines for users.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $orderId = 'QR_' . $paymentData['order_id'];

        return [
            'success' => true,
            'action_type' => 'qr',
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'qr_image_url' => $creds['qr_image_url'] ?? '',
                'address' => $creds['upi_id_or_address'] ?? '',
                'payee_name' => $creds['payee_name'] ?? '',
                'instructions' => $creds['instructions'] ?? 'Scan the QR code and submit the 12-digit transaction UTR / Reference ID.',
                'amount' => $paymentData['payable_amount'],
                'currency' => $paymentData['currency'] ?? 'INR',
                'order_id' => $paymentData['order_id'],
            ],
            'message' => 'Please scan the QR code to complete payment.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $utr = trim((string)$request->input('transaction_reference', $request->input('utr_number', '')));

        if (empty($utr)) {
            return [
                'success' => false,
                'gateway_order_id' => (string)$request->input('order_id', ''),
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Please enter the transaction reference / UTR number.',
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => (string)$request->input('order_id', ''),
            'transaction_id' => $utr,
            'amount' => (string)$request->input('amount', '0'),
            'currency' => (string)$request->input('currency', 'INR'),
            'raw_response' => $request->all(),
            'error' => 'Your payment reference has been recorded and submitted for administrator verification.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'status' => 'pending',
            'raw_payload' => [],
            'error' => 'Webhooks are not applicable for static QR.',
        ];
    }
}
