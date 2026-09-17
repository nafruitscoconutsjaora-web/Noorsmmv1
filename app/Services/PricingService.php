<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Support\Money;

class PricingService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Calculate final selling rate per 1000 units
     *
     * @param string $providerCost Provider cost per 1000 in provider currency
     * @param string $providerCurrency e.g. USD
     * @param string $targetCurrency e.g. INR
     * @param string $marginType 'percentage' | 'fixed'
     * @param string $marginValue percentage (e.g. 25.0) or fixed amount
     * @return string Final rate per 1000 in target currency
     */
    public function calculateRate(
        string $providerCost,
        string $providerCurrency = 'USD',
        string $targetCurrency = 'INR',
        string $marginType = 'percentage',
        string $marginValue = '20.00000000'
    ): string {
        // Convert provider cost to panel target currency
        $costInTargetCurrency = $this->convertCurrency($providerCost, $providerCurrency, $targetCurrency);

        if ($marginType === 'percentage') {
            // Rate = cost + (cost * marginValue / 100)
            $multiplier = Money::div($marginValue, '100');
            $marginAmount = Money::mul($costInTargetCurrency, $multiplier);
            $rate = Money::add($costInTargetCurrency, $marginAmount);
        } else {
            // Fixed margin added in target currency
            $rate = Money::add($costInTargetCurrency, $marginValue);
        }

        // Apply minimum rate safety check
        if (Money::lte($rate, '0')) {
            $rate = '0.01000000';
        }

        return $rate;
    }

    public function convertCurrency(string $amount, string $from, string $to): string
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        // If from USD to INR, look up USD -> INR rate
        $rateRow = $this->db->fetchOne(
            "SELECT `rate` FROM `exchange_rates` WHERE `from_currency` = :from AND `to_currency` = :to",
            [':from' => $from, ':to' => $to]
        );

        if ($rateRow) {
            return Money::mul($amount, $rateRow['rate']);
        }

        // Check inverse
        $inverseRow = $this->db->fetchOne(
            "SELECT `rate` FROM `exchange_rates` WHERE `from_currency` = :to AND `to_currency` = :from",
            [':to' => $from, ':from' => $to]
        );

        if ($inverseRow && Money::gt($inverseRow['rate'], '0')) {
            return Money::div($amount, $inverseRow['rate']);
        }

        // Fallback default: if USD to INR assume default 83.5
        if ($from === 'USD' && $to === 'INR') {
            return Money::mul($amount, '83.50000000');
        }

        return $amount;
    }

    /**
     * Calculate total charge for an order based on service rate per 1000 and quantity
     */
    public function calculateOrderCharge(string $ratePer1000, int $quantity): string
    {
        // charge = (ratePer1000 * quantity) / 1000
        $qtyStr = (string)$quantity;
        $total = Money::mul($ratePer1000, $qtyStr);
        return Money::div($total, '1000');
    }
}
