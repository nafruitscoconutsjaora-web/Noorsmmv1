<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ReferralRepository;

class ReferralController extends BaseController
{
    private ReferralRepository $referrals;

    public function __construct()
    {
        $this->referrals = new ReferralRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $code = $this->referrals->getReferralCode($user['id']);
        $stats = $this->referrals->getStats($user['id']);

        $baseUrl = config('app.url', 'http://localhost:3000');
        $referralLink = rtrim($baseUrl, '/') . '/register?ref=' . urlencode($code);

        return view('user/referrals/index', [
            'referral_code' => $code,
            'referral_link' => $referralLink,
            'total_referred' => $stats['total_referred'],
            'total_earnings' => $stats['total_earnings'],
            'referrals' => $stats['referrals'],
            'payouts' => $stats['payouts'],
            'commission_rate' => (float)setting('referral_commission_rate', 5.0),
        ], 'user');
    }

    public function requestPayout(Request $request): Response
    {
        $user = $this->user();
        $amount = (float)$request->input('amount');
        $method = (string)$request->input('method', 'wallet_credit');
        $details = (string)$request->input('details', '');

        $stats = $this->referrals->getStats($user['id']);
        $minPayout = 100.0;

        if ($amount < $minPayout) {
            flash('error', "Minimum referral payout amount is ₹" . number_format($minPayout, 2));
            return $this->redirect('/referrals');
        }

        if ($amount > $stats['total_earnings']) {
            flash('error', "Requested amount exceeds total referral earnings.");
            return $this->redirect('/referrals');
        }

        $this->referrals->requestPayout($user['id'], $amount, $method, $details);
        flash('success', 'Payout request submitted successfully. Our billing team will review it shortly.');
        return $this->redirect('/referrals');
    }
}
