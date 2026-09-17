<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;

class MoneyTest
{
    public function run(): void
    {
        // 1. Test addition
        assert(Money::add('10.50', '5.25') === '15.75000000', 'Money::add failed');

        // 2. Test subtraction
        assert(Money::sub('10.50', '5.25') === '5.25000000', 'Money::sub failed');

        // 3. Test multiplication
        assert(Money::mul('10.00', '2.5') === '25.00000000', 'Money::mul failed');

        // 4. Test division
        assert(Money::div('100.00', '4') === '25.00000000', 'Money::div failed');

        // 5. Test comparisons
        assert(Money::gt('15.00', '10.00') === true, 'Money::gt failed');
        assert(Money::lt('5.00', '10.00') === true, 'Money::lt failed');
        assert(Money::eq('10.00000000', '10.00') === true, 'Money::eq failed');
        assert(Money::gte('10.00', '10.00') === true, 'Money::gte failed');

        // 6. Test formatting
        assert(Money::format('1234.5678') === '1,234.57', 'Money::format failed');
        assert(Money::format('0.00') === '0.00', 'Money::format zero failed');
    }
}
