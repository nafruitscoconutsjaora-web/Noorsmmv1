<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;

class CustomManualGateway extends AbstractGateway
{
    protected string $code = 'custom_manual';
    protected string $name = 'Custom Manual Payment';
    protected string $category = 'manual';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'USDT', 'AED'];

    public function getCredentialFields(): array
    {
        return [
            'payment_title' => [
                'label' => 'Payment Method Title',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'e.g. Western Union / PayTM Wallet / Easypaisa / JazzCash',
                'help' => 'Title shown to users.',
            ],
            'account_details' => [
                'label' => 'Account / Contact / Wallet Details',
                'type' => 'textarea',
                'required' => true,
                'placeholder' => "Account Number: ...\nAccount Name: ...\nPhone / ID: ...",
                'help' => 'Payment credentials and recipient info.',
            ],
            'instructions' => [
                'label' => 'Instructions for Customers',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => 'Send funds to the details above and provide transaction proof.',
                'help' => 'Step-by-step instructions.',
            ],
            'proof_required' => [
                'label' => 'Proof Screenshot Required',
                'type' => 'select',
                'required' => true,
                'options' => [
                    '1' => 'Yes - Require Transaction ID & Screenshot Upload',
                    '0' => 'No - Only Transaction ID required',
                ],
                'help' => 'Whether screenshot attachment is mandatory.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $orderId = 'MAN_' . $paymentData['order_id'];

        return [
            'success' => true,
            'action_type' => 'instructions',
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'title' => $creds['payment_title'] ?? 'Manual Payment',
                'details' => $creds['account_details'] ?? '',
                'instructions' => $creds['instructions'] ?? '',
                'proof_required' => ($creds['proof_required'] ?? '1') === '1',
                'amount' => $paymentData['payable_amount'],
                'currency' => $paymentData['currency'] ?? 'INR',
                'order_id' => $paymentData['order_id'],
            ],
            'message' => 'Please review manual payment details and submit your reference.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $ref = trim((string)$request->input('transaction_reference', $request->input('utr_number', '')));

        if (empty($ref)) {
            return [
                'success' => false,
                'gateway_order_id' => (string)$request->input('order_id', ''),
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Please provide the transaction reference ID.',
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => (string)$request->input('order_id', ''),
            'transaction_id' => $ref,
            'amount' => (string)$request->input('amount', '0'),
            'currency' => (string)$request->input('currency', 'INR'),
            'raw_response' => $request->all(),
            'error' => 'Your manual payment reference has been submitted and is pending administrator review.',
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
            'error' => 'Webhooks are not applicable for manual payments.',
        ];
    }
}
