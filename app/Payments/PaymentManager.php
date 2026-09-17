<?php

declare(strict_types=1);

namespace App\Payments;

use App\Core\Database;
use App\Core\Request;
use App\Payments\Gateways\AdyenGateway;
use App\Payments\Gateways\AirwallexGateway;
use App\Payments\Gateways\AlipayGateway;
use App\Payments\Gateways\AmazonPayGateway;
use App\Payments\Gateways\AuthorizeNetGateway;
use App\Payments\Gateways\BankTransferGateway;
use App\Payments\Gateways\BillDeskGateway;
use App\Payments\Gateways\BinancePayGateway;
use App\Payments\Gateways\BraintreeGateway;
use App\Payments\Gateways\CashfreeGateway;
use App\Payments\Gateways\CCAvenueGateway;
use App\Payments\Gateways\CheckoutComGateway;
use App\Payments\Gateways\CoinbaseCommerceGateway;
use App\Payments\Gateways\CoinPaymentsGateway;
use App\Payments\Gateways\CryptoComPayGateway;
use App\Payments\Gateways\CustomManualGateway;
use App\Payments\Gateways\CybersourceGateway;
use App\Payments\Gateways\DLocalGateway;
use App\Payments\Gateways\EasebuzzGateway;
use App\Payments\Gateways\FlutterwaveGateway;
use App\Payments\Gateways\GlobalPaymentsGateway;
use App\Payments\Gateways\InstamojoGateway;
use App\Payments\Gateways\JuspayGateway;
use App\Payments\Gateways\KlarnaGateway;
use App\Payments\Gateways\MercadoPagoGateway;
use App\Payments\Gateways\MobiKwikGateway;
use App\Payments\Gateways\MollieGateway;
use App\Payments\Gateways\NuveiGateway;
use App\Payments\Gateways\PayPalGateway;
use App\Payments\Gateways\PayGlocalGateway;
use App\Payments\Gateways\PayoneerGateway;
use App\Payments\Gateways\PaystackGateway;
use App\Payments\Gateways\PaytmGateway;
use App\Payments\Gateways\PayUGateway;
use App\Payments\Gateways\PayUGlobalGateway;
use App\Payments\Gateways\PhonePeGateway;
use App\Payments\Gateways\PluralGateway;
use App\Payments\Gateways\QrPaymentGateway;
use App\Payments\Gateways\RapydGateway;
use App\Payments\Gateways\RazorpayGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\Gateways\TwoCheckoutGateway;
use App\Payments\Gateways\WeChatPayGateway;
use App\Payments\Gateways\WorldlineGateway;
use App\Payments\Gateways\WorldpayGateway;
use App\Payments\Gateways\ZaakpayGateway;
use App\Support\Encryption;
use App\Support\Logger;
use PDO;

class PaymentManager
{
    /**
     * Complete registry of all supported gateway adapters.
     *
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    protected static array $gateways = [
        // Indian Gateways
        'razorpay' => RazorpayGateway::class,
        'cashfree' => CashfreeGateway::class,
        'phonepe' => PhonePeGateway::class,
        'payu' => PayUGateway::class,
        'paytm' => PaytmGateway::class,
        'ccavenue' => CCAvenueGateway::class,
        'billdesk' => BillDeskGateway::class,
        'easebuzz' => EasebuzzGateway::class,
        'plural' => PluralGateway::class,
        'worldline' => WorldlineGateway::class,
        'zaakpay' => ZaakpayGateway::class,
        'instamojo' => InstamojoGateway::class,
        'mobikwik' => MobiKwikGateway::class,
        'juspay' => JuspayGateway::class,
        'payglocal' => PayGlocalGateway::class,

        // International Gateways
        'stripe' => StripeGateway::class,
        'paypal' => PayPalGateway::class,
        'braintree' => BraintreeGateway::class,
        'adyen' => AdyenGateway::class,
        'checkoutcom' => CheckoutComGateway::class,
        'worldpay' => WorldpayGateway::class,
        'twocheckout' => TwoCheckoutGateway::class,
        'airwallex' => AirwallexGateway::class,
        'nuvei' => NuveiGateway::class,
        'rapyd' => RapydGateway::class,
        'mollie' => MollieGateway::class,
        'dlocal' => DLocalGateway::class,
        'payu_global' => PayUGlobalGateway::class,
        'payoneer' => PayoneerGateway::class,
        'globalpayments' => GlobalPaymentsGateway::class,
        'cybersource' => CybersourceGateway::class,
        'authorizenet' => AuthorizeNetGateway::class,
        'amazonpay' => AmazonPayGateway::class,
        'klarna' => KlarnaGateway::class,
        'mercadopago' => MercadoPagoGateway::class,
        'paystack' => PaystackGateway::class,
        'flutterwave' => FlutterwaveGateway::class,
        'alipay' => AlipayGateway::class,
        'wechatpay' => WeChatPayGateway::class,

        // Crypto Gateways
        'binancepay' => BinancePayGateway::class,
        'coinbase_commerce' => CoinbaseCommerceGateway::class,
        'cryptocom_pay' => CryptoComPayGateway::class,
        'coinpayments' => CoinPaymentsGateway::class,

        // Manual Gateways
        'bank_transfer' => BankTransferGateway::class,
        'custom_qr' => QrPaymentGateway::class,
        'custom_manual' => CustomManualGateway::class,
    ];

    /**
     * Resolve a gateway adapter instance by code.
     */
    public static function getGateway(string $code): ?PaymentGatewayInterface
    {
        $code = strtolower(trim($code));
        if (isset(self::$gateways[$code])) {
            $class = self::$gateways[$code];
            return new $class();
        }
        return null;
    }

    /**
     * Check if a gateway code is supported.
     */
    public static function hasGateway(string $code): bool
    {
        return isset(self::$gateways[strtolower(trim($code))]);
    }

    /**
     * Get all registered gateway classes and metadata.
     *
     * @return array<string, array>
     */
    public static function getRegisteredGateways(): array
    {
        $list = [];
        foreach (self::$gateways as $code => $class) {
            /** @var PaymentGatewayInterface $instance */
            $instance = new $class();
            $list[$code] = [
                'code' => $instance->getCode(),
                'name' => $instance->getName(),
                'category' => $instance->getCategory(),
                'default_currency' => $instance->getDefaultCurrency(),
                'supported_currencies' => $instance->getSupportedCurrencies(),
                'credential_fields' => $instance->getCredentialFields(),
                'class' => $class,
            ];
        }
        return $list;
    }

    /**
     * Fetch a gateway record from the database.
     */
    public static function getGatewayRow(string $code): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM payment_gateways WHERE code = :code LIMIT 1');
        $stmt->execute(['code' => strtolower(trim($code))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Decode config JSON
        $row['config'] = !empty($row['config']) ? json_decode($row['config'], true) : [];
        $row['is_active'] = !empty($row['is_enabled']);
        $row['min_deposit'] = (float)($row['min_amount'] ?? 10.00);
        $row['max_deposit'] = (float)($row['max_amount'] ?? 50000.00);
        $row['fee_fixed'] = (float)($row['fixed_fee'] ?? 0.00);
        $row['fee_percentage'] = (float)($row['percent_fee'] ?? 0.00);

        $adapter = self::getGateway($code);
        $row['category'] = $adapter ? $adapter->getCategory() : 'international';

        return $row;
    }

    /**
     * Fetch active gateways available for customer checkout.
     *
     * @param string|null $category
     * @return array
     */
    public static function getActiveGateways(?string $category = null): array
    {
        self::syncRegisteredGatewaysToDatabase();

        $db = Database::getInstance();
        $sql = 'SELECT * FROM payment_gateways WHERE is_enabled = 1 ORDER BY sort_order ASC, name ASC';
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $code = $row['code'];
            $adapter = self::getGateway($code);
            if (!$adapter) {
                continue;
            }

            $adapterCategory = $adapter->getCategory();
            if (!empty($category) && $category !== 'all' && $adapterCategory !== $category) {
                continue;
            }

            $row['config'] = !empty($row['config']) ? json_decode($row['config'], true) : [];
            $row['category'] = $adapterCategory;
            $row['is_active'] = !empty($row['is_enabled']);
            $row['min_deposit'] = (float)($row['min_amount'] ?? 10.00);
            $row['max_deposit'] = (float)($row['max_amount'] ?? 50000.00);
            $row['fee_fixed'] = (float)($row['fixed_fee'] ?? 0.00);
            $row['fee_percentage'] = (float)($row['percent_fee'] ?? 0.00);
            $row['adapter_name'] = $adapter->getName();
            $row['credential_fields'] = $adapter->getCredentialFields();
            $row['supported_currencies'] = $adapter->getSupportedCurrencies();
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Fetch all gateways for Admin Management (active and inactive).
     */
    public static function getAllGatewaysForAdmin(): array
    {
        self::syncRegisteredGatewaysToDatabase();

        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM payment_gateways ORDER BY sort_order ASC, name ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $code = $row['code'];
            $adapter = self::getGateway($code);

            $row['config'] = !empty($row['config']) ? json_decode($row['config'], true) : [];
            $row['category'] = $adapter ? $adapter->getCategory() : 'international';
            $row['is_active'] = !empty($row['is_enabled']);
            $row['min_deposit'] = (float)($row['min_amount'] ?? 10.00);
            $row['max_deposit'] = (float)($row['max_amount'] ?? 50000.00);
            $row['fee_fixed'] = (float)($row['fixed_fee'] ?? 0.00);
            $row['fee_percentage'] = (float)($row['percent_fee'] ?? 0.00);
            $row['adapter_exists'] = $adapter !== null;
            $row['adapter_name'] = $adapter ? $adapter->getName() : $row['name'];
            $row['credential_fields'] = $adapter ? $adapter->getCredentialFields() : [];
            $row['supported_currencies'] = $adapter ? $adapter->getSupportedCurrencies() : [];

            // Mask credentials for display
            $row['has_credentials'] = false;
            if (!empty($row['credentials'])) {
                try {
                    $dec = Encryption::decrypt($row['credentials']);
                    $credArray = json_decode($dec, true);
                    $row['has_credentials'] = !empty($credArray);
                } catch (\Throwable) {
                    $row['has_credentials'] = false;
                }
            }

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Synchronize registry with the payment_gateways table so that all
     * adapters have a database row with configurable fees, limits, and status.
     */
    public static function syncRegisteredGatewaysToDatabase(): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->query('SELECT code FROM payment_gateways');
            $existingCodes = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $insertStmt = $db->prepare('
                INSERT INTO payment_gateways 
                (name, code, type, description, instructions, is_enabled, sort_order, currency, min_amount, max_amount, fixed_fee, percent_fee, bonus_enabled, bonus_type, bonus_value, max_bonus, credentials, config, created_at, updated_at)
                VALUES 
                (:name, :code, :type, :description, :instructions, :is_enabled, :sort_order, :currency, :min_amount, :max_amount, :fixed_fee, :percent_fee, :bonus_enabled, :bonus_type, :bonus_value, :max_bonus, :credentials, :config, NOW(), NOW())
            ');

            $order = 10;
            foreach (self::$gateways as $code => $class) {
                if (in_array($code, $existingCodes, true)) {
                    continue;
                }

                /** @var PaymentGatewayInterface $instance */
                $instance = new $class();
                $insertStmt->execute([
                    'name' => $instance->getName(),
                    'code' => $code,
                    'type' => in_array($code, ['bank_transfer', 'custom_qr', 'custom_manual'], true) ? 'custom' : 'builtin',
                    'description' => "Instant deposit via {$instance->getName()}",
                    'instructions' => '',
                    'is_enabled' => $code === 'razorpay' ? 1 : 0, // Keep legacy active by default
                    'sort_order' => $order++,
                    'currency' => $instance->getDefaultCurrency(),
                    'min_amount' => 10.00,
                    'max_amount' => 50000.00,
                    'fixed_fee' => 0.00,
                    'percent_fee' => 0.00,
                    'bonus_enabled' => 0,
                    'bonus_type' => 'percentage',
                    'bonus_value' => 0.00,
                    'max_bonus' => 0.00,
                    'credentials' => Encryption::encrypt('{}'),
                    'config' => json_encode([
                        'default_currency' => $instance->getDefaultCurrency(),
                        'instructions' => '',
                    ]),
                ]);
            }
        } catch (\Throwable $e) {
            Logger::error('Failed to sync payment gateways: ' . $e->getMessage());
        }
    }

    /**
     * Calculate financial totals for a proposed deposit.
     *
     * @param array $gatewayRow
     * @param float $requestedAmount
     * @return array{
     *   requested_amount: float,
     *   fee: float,
     *   bonus: float,
     *   payable_amount: float,
     *   wallet_credit: float,
     *   error: ?string
     * }
     */
    public static function calculateAmounts(array $gatewayRow, float $requestedAmount): array
    {
        $min = (float)($gatewayRow['min_amount'] ?? $gatewayRow['min_deposit'] ?? 0);
        $max = (float)($gatewayRow['max_amount'] ?? $gatewayRow['max_deposit'] ?? 0);

        if ($min > 0 && $requestedAmount < $min) {
            return [
                'requested_amount' => $requestedAmount,
                'fee' => 0.0,
                'bonus' => 0.0,
                'payable_amount' => 0.0,
                'wallet_credit' => 0.0,
                'error' => "Minimum deposit amount for {$gatewayRow['name']} is " . number_format($min, 2),
            ];
        }

        if ($max > 0 && $requestedAmount > $max) {
            return [
                'requested_amount' => $requestedAmount,
                'fee' => 0.0,
                'bonus' => 0.0,
                'payable_amount' => 0.0,
                'wallet_credit' => 0.0,
                'error' => "Maximum deposit amount for {$gatewayRow['name']} is " . number_format($max, 2),
            ];
        }

        $feePercent = (float)($gatewayRow['percent_fee'] ?? $gatewayRow['fee_percentage'] ?? 0);
        $feeFixed = (float)($gatewayRow['fixed_fee'] ?? $gatewayRow['fee_fixed'] ?? 0);

        $fee = round(($requestedAmount * ($feePercent / 100)) + $feeFixed, 2);
        $payableAmount = round($requestedAmount + $fee, 2);

        $bonus = 0.0;
        if (!empty($gatewayRow['bonus_enabled'])) {
            $bonusType = $gatewayRow['bonus_type'] ?? 'percentage';
            $bonusVal = (float)($gatewayRow['bonus_value'] ?? 0);
            if ($bonusType === 'percentage') {
                $bonus = round($requestedAmount * ($bonusVal / 100), 2);
            } else {
                $bonus = round($bonusVal, 2);
            }
            $maxBonus = (float)($gatewayRow['max_bonus'] ?? 0);
            if ($maxBonus > 0 && $bonus > $maxBonus) {
                $bonus = $maxBonus;
            }
        }

        $walletCredit = round($requestedAmount + $bonus, 2);

        return [
            'requested_amount' => $requestedAmount,
            'fee' => $fee,
            'bonus' => $bonus,
            'payable_amount' => $payableAmount,
            'wallet_credit' => $walletCredit,
            'error' => null,
        ];
    }

    /**
     * Initiate payment using the adapter matching the gateway code.
     */
    public static function initiate(string $gatewayCode, array $paymentData): array
    {
        $gatewayRow = self::getGatewayRow($gatewayCode);
        if (!$gatewayRow || (empty($gatewayRow['is_enabled']) && empty($gatewayRow['is_active']))) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'The selected payment gateway is currently unavailable or disabled.',
            ];
        }

        $adapter = self::getGateway($gatewayCode);
        if (!$adapter) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => "Payment adapter for '{$gatewayCode}' not found.",
            ];
        }

        return $adapter->initiatePayment($paymentData, $gatewayRow);
    }

    /**
     * Verify payment return from client-side or gateway redirect.
     */
    public static function verify(string $gatewayCode, Request $request): array
    {
        $gatewayRow = self::getGatewayRow($gatewayCode);
        if (!$gatewayRow) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => "Gateway '{$gatewayCode}' not found.",
            ];
        }

        $adapter = self::getGateway($gatewayCode);
        if (!$adapter) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => "Payment adapter for '{$gatewayCode}' not found.",
            ];
        }

        return $adapter->verifyPayment($request, $gatewayRow);
    }

    /**
     * Handle incoming asynchronous webhook from gateway.
     */
    public static function webhook(string $gatewayCode, Request $request): array
    {
        $gatewayRow = self::getGatewayRow($gatewayCode);
        if (!$gatewayRow) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'status' => 'failed',
                'raw_payload' => [],
                'error' => "Gateway '{$gatewayCode}' not found.",
            ];
        }

        $adapter = self::getGateway($gatewayCode);
        if (!$adapter) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'status' => 'failed',
                'raw_payload' => [],
                'error' => "Adapter for '{$gatewayCode}' not found.",
            ];
        }

        return $adapter->handleWebhook($request, $gatewayRow);
    }
}
