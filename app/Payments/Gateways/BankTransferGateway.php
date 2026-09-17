<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;

class BankTransferGateway extends AbstractGateway
{
    protected string $code = 'bank_transfer';
    protected string $name = 'Direct Bank Transfer / Wire';
    protected string $category = 'manual';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD'];

    public function getCredentialFields(): array
    {
        return [
            'bank_name' => [
                'label' => 'Bank Name',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'e.g. State Bank of India / HDFC Bank / Chase',
                'help' => 'Name of your bank.',
            ],
            'account_holder' => [
                'label' => 'Account Beneficiary Name',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'e.g. SMM Tech Private Limited',
                'help' => 'Exact account holder name.',
            ],
            'account_number' => [
                'label' => 'Account Number / IBAN',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter account number or IBAN',
                'help' => 'Bank account number or IBAN.',
            ],
            'ifsc_swift' => [
                'label' => 'IFSC Code / SWIFT / BIC',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'e.g. HDFC0001234 or CHASUS33',
                'help' => 'IFSC for domestic Indian transfers, SWIFT/BIC for international.',
            ],
            'branch' => [
                'label' => 'Branch Address / Notes',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => 'Branch name, city or additional instructions for the sender',
                'help' => 'Optional branch details and instructions.',
            ],
            'instructions' => [
                'label' => 'Deposit Instructions',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => 'Transfer exact amount, include order ID in the transaction remarks/notes.',
                'help' => 'Displayed to the user when depositing.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $orderId = 'BT_' . $paymentData['order_id'];

        return [
            'success' => true,
            'action_type' => 'instructions',
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'bank_name' => $creds['bank_name'] ?? '',
                'account_holder' => $creds['account_holder'] ?? '',
                'account_number' => $creds['account_number'] ?? '',
                'ifsc_swift' => $creds['ifsc_swift'] ?? '',
                'branch' => $creds['branch'] ?? '',
                'instructions' => $creds['instructions'] ?? 'Please transfer the exact amount and submit your transaction reference number below.',
                'amount' => $paymentData['payable_amount'],
                'currency' => $paymentData['currency'] ?? 'INR',
                'order_id' => $paymentData['order_id'],
            ],
            'message' => 'Please complete the bank transfer and submit the transaction reference.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $txnRef = trim((string)$request->input('transaction_reference', $request->input('utr_number', '')));

        if (empty($txnRef)) {
            return [
                'success' => false,
                'gateway_order_id' => (string)$request->input('order_id', ''),
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Please provide the transaction reference / UTR number for manual bank transfer verification.',
            ];
        }

        // For manual gateways, the initial user submission marks it pending manual review
        return [
            'success' => false,
            'gateway_order_id' => (string)$request->input('order_id', ''),
            'transaction_id' => $txnRef,
            'amount' => (string)$request->input('amount', '0'),
            'currency' => (string)$request->input('currency', 'INR'),
            'raw_response' => $request->all(),
            'error' => 'Your bank transfer reference has been submitted and is awaiting administrator verification.',
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
            'error' => 'Webhooks are not applicable for manual bank transfers.',
        ];
    }
}
