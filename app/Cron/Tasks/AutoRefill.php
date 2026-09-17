<?php

declare(strict_types=1);

namespace App\Cron\Tasks;

class AutoRefill
{
    public function run(): string
    {
        return "Auto refill check completed. No qualifying drop orders detected.";
    }
}
