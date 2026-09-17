<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ReferralRepository;
use PDO;

class AdminReferralController extends BaseController
{
    private ReferralRepository $referrals;
    private PDO $db;

    public function __construct()
    {
        $this->referrals = new ReferralRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $allReferrals = $this->referrals->getAllAdmin();
        $payouts = $this->referrals->getPayoutsAdmin();
        $commissionRate = (float)setting('referral_commission_rate', 5.0);
        $totalCommissionPaid = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM `referral_payouts` WHERE `status` = 'paid'")->fetchColumn();

        $stats = [
            'total_referrals' => count($allReferrals),
            'total_commission_paid' => $totalCommissionPaid,
            'commission_rate' => $commissionRate,
        ];

        return view('admin/referrals/index', [
            'referrals' => $allReferrals,
            'payouts' => $payouts,
            'commission_rate' => $commissionRate,
            'stats' => $stats,
        ], 'admin');
    }

    public function updateCommissionRate(Request $request): Response
    {
        $rate = max(0, min(50, (float)$request->input('commission_rate')));
        
        $stmt = $this->db->prepare("INSERT INTO `settings` (`key`, `value`, `updated_at`) VALUES ('referral_commission_rate', :rate, NOW()) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()");
        $stmt->execute([':rate' => (string)$rate]);

        flash('success', "Affiliate commission rate updated to {$rate}%.");
        return $this->redirect('/admin/referrals');
    }

    public function processPayout(Request $request, string $id): Response
    {
        $payoutId = (int)$id;
        $action = (string)$request->input('action'); // 'approve' or 'reject'
        $note = (string)$request->input('admin_note', '');

        $status = $action === 'approve' ? 'paid' : 'rejected';
        $this->referrals->updatePayoutStatus($payoutId, $status, $note);

        flash('success', "Payout #{$payoutId} has been marked as {$status}.");
        return $this->redirect('/admin/referrals');
    }
}
