<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PricingService;
use App\Support\Money;

class PricingServiceTest
{
    public function run(): void
    {
        $pricing = new PricingService();

        // 1. Test order charge calculation
        // Rate = 100.00 per 1000. Qty = 500. Expected = 50.00
        $charge = $pricing->calculateOrderCharge('100.00', 500);
        assert(Money::eq($charge, '50.00'), "Expected charge 50.00, got: {$charge}");

        // Rate = 79.50 per 1000. Qty = 1000. Expected = 79.50
        $charge2 = $pricing->calculateOrderCharge('79.50', 1000);
        assert(Money::eq($charge2, '79.50'), "Expected charge 79.50, got: {$charge2}");

        // Rate = 4.50 per 1000. Qty = 200. Expected = 0.90
        $charge3 = $pricing->calculateOrderCharge('4.50', 200);
        assert(Money::eq($charge3, '0.90'), "Expected charge 0.90, got: {$charge3}");

        // 2. Test calculate rate with percentage margin (same currency INR -> INR)
        // Cost = 10.00. Margin = 50%. Expected = 15.00
        $ratePerc = $pricing->calculateRate('10.00', 'INR', 'INR', 'percentage', '50.0');
        assert(Money::eq($ratePerc, '15.00'), "Expected rate 15.00, got: {$ratePerc}");

        // 3. Test calculate rate with fixed margin (INR -> INR)
        // Cost = 20.00. Fixed margin = 5.00. Expected = 25.00
        $rateFixed = $pricing->calculateRate('20.00', 'INR', 'INR', 'fixed', '5.00');
        assert(Money::eq($rateFixed, '25.00'), "Expected rate 25.00, got: {$rateFixed}");
    }
}
