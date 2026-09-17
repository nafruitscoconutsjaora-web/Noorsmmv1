<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\LoyaltyRepository;

class LoyaltyController extends BaseController
{
    private LoyaltyRepository $loyalty;

    public function __construct()
    {
        $this->loyalty = new LoyaltyRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $account = $this->loyalty->getOrCreateAccount($user['id']);
        $transactions = $this->loyalty->getTransactions($user['id'], 20);

        // Conversion rate: 100 points = 1 INR
        $pointValueInr = 0.01;
        $points = (int)($account['points'] ?? 0);
        $lifetimeSpent = (float)($account['lifetime_spent'] ?? 0);
        $redeemableValue = $points * $pointValueInr;
        $tierName = strtolower($account['tier'] ?? 'bronze');

        $tier = [
            'name' => $tierName,
            'threshold' => $tierName === 'platinum' ? 100000 : ($tierName === 'gold' ? 25000 : ($tierName === 'silver' ? 5000 : 0)),
            'next_threshold' => $tierName === 'platinum' ? null : ($tierName === 'gold' ? 100000 : ($tierName === 'silver' ? 25000 : 5000)),
            'next_tier' => $tierName === 'platinum' ? null : ($tierName === 'gold' ? 'platinum' : ($tierName === 'silver' ? 'gold' : 'silver')),
        ];

        return view('user/rewards/index', [
            'account' => $account,
            'tier' => $tier,
            'points' => $points,
            'lifetimeSpent' => $lifetimeSpent,
            'transactions' => $transactions,
            'point_value_inr' => $pointValueInr,
            'redeemable_value' => $redeemableValue,
        ], 'user');
    }

    public function redeem(Request $request): Response
    {
        $user = $this->user();
        $points = (int)$request->input('points');

        if ($points < 100) {
            flash('error', 'Minimum redemption is 100 points.');
            return $this->redirect('/rewards');
        }

        // 100 points = 1.00 INR
        $creditAmount = round($points * 0.01, 2);

        $success = $this->loyalty->redeemPoints($user['id'], $points, $creditAmount);

        if (!$success) {
            flash('error', 'Insufficient loyalty points to redeem.');
            return $this->redirect('/rewards');
        }

        flash('success', "Successfully redeemed {$points} points! ₹" . number_format($creditAmount, 2) . " has been credited to your wallet.");
        return $this->redirect('/rewards');
    }
}
