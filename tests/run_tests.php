<?php

declare(strict_types=1);

// Apex SMM Platform - Test Runner

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/helpers.php';

// Enable assert exceptions
@ini_set('assert.exception', '1');

$app = new App\Core\Application(dirname(__DIR__));

echo "=================================================\n";
echo " Apex SMM Platform - Automated Test Suite\n";
echo "=================================================\n\n";

$tests = [
    'Unit: Money (BCMath Financial Precision)' => Tests\Unit\MoneyTest::class,
    'Unit: Pricing Service & Rate Calculation' => Tests\Unit\PricingServiceTest::class,
    'Unit: Validator & Input Rules' => Tests\Unit\ValidatorTest::class,
    'Feature: SMM API v2 Endpoints' => Tests\Feature\ApiTest::class,
    'Feature: Order Placement & Balance Accounting' => Tests\Feature\OrderTest::class,
    'Feature: User Authentication & Registration' => Tests\Feature\AuthTest::class,
];

$passed = 0;
$failed = 0;

foreach ($tests as $title => $class) {
    echo "• Running {$title}... ";
    try {
        $runner = new $class();
        $runner->run();
        echo "\033[32m[PASSED]\033[0m\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "\033[31m[FAILED]\033[0m\n";
        echo "  Error: " . $e->getMessage() . "\n";
        echo "  In: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
        $failed++;
    }
}

echo "\n-------------------------------------------------\n";
echo "Results: {$passed} passed, {$failed} failed.\n";
echo "=================================================\n";

exit($failed > 0 ? 1 : 0);
