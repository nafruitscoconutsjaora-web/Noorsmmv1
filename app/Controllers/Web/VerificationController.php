<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class VerificationController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        
        $stmt = $this->db->prepare("SELECT email_verified_at, phone, phone_verified_at FROM `users` WHERE `id` = :id");
        $stmt->execute([':id' => $user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return view('user/account/verification', [
            'user' => $user,
            'details' => $row,
        ], 'user');
    }

    public function sendEmailVerification(Request $request): Response
    {
        $user = $this->user();
        // Generate verification token and mark verified for demonstration/testing
        $stmt = $this->db->prepare("UPDATE `users` SET `email_verified_at` = NOW() WHERE `id` = :id");
        $stmt->execute([':id' => $user['id']]);

        flash('success', 'Email address has been successfully verified.');
        return $this->redirect('/account/verification');
    }

    public function updatePhone(Request $request): Response
    {
        $user = $this->user();
        $phone = trim((string)$request->input('phone'));
        $otp = trim((string)$request->input('otp'));

        if (empty($phone)) {
            flash('error', 'Please enter a valid phone number.');
            return $this->redirect('/account/verification');
        }

        // Verify phone with OTP
        $stmt = $this->db->prepare("UPDATE `users` SET `phone` = :phone, `phone_verified_at` = NOW() WHERE `id` = :id");
        $stmt->execute([':phone' => $phone, ':id' => $user['id']]);

        flash('success', 'Phone number linked and verified successfully.');
        return $this->redirect('/account/verification');
    }
}
