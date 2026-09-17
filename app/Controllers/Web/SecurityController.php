<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\SecurityRepository;
use App\Services\AuthService;
use PDO;

class SecurityController extends BaseController
{
    private SecurityRepository $security;
    private PDO $db;

    public function __construct()
    {
        $this->security = new SecurityRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $logins = $this->security->getLoginHistory($user['id'], 15);
        $currentSessionId = session_id();

        return view('user/account/security', [
            'logins' => $logins,
            'current_session_id' => $currentSessionId,
            'user' => $user,
        ], 'user');
    }

    public function updatePassword(Request $request): Response
    {
        $user = $this->user();
        $currentPassword = (string)$request->input('current_password');
        $newPassword = (string)$request->input('new_password');
        $confirmPassword = (string)$request->input('new_password_confirmation');

        if (empty($currentPassword) || empty($newPassword)) {
            flash('error', 'All password fields are required.');
            return $this->redirect('/account/security');
        }

        if (strlen($newPassword) < 8) {
            flash('error', 'New password must be at least 8 characters.');
            return $this->redirect('/account/security');
        }

        if ($newPassword !== $confirmPassword) {
            flash('error', 'New password and confirmation do not match.');
            return $this->redirect('/account/security');
        }

        // Verify current password against DB
        $stmt = $this->db->prepare("SELECT password_hash FROM `users` WHERE `id` = :id");
        $stmt->execute([':id' => $user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($currentPassword, (string)$hash)) {
            flash('error', 'Current password is incorrect.');
            return $this->redirect('/account/security');
        }

        // Update password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmtUpdate = $this->db->prepare("UPDATE `users` SET `password_hash` = :hash, `updated_at` = NOW() WHERE `id` = :id");
        $stmtUpdate->execute([':hash' => $newHash, ':id' => $user['id']]);

        $this->security->logSecurityEvent('password_change', 'medium', $user['id'], null, $request->ip(), $request->userAgent());

        flash('success', 'Password updated successfully.');
        return $this->redirect('/account/security');
    }

    public function logoutOtherSessions(Request $request): Response
    {
        $user = $this->user();
        $currentSessionId = session_id();

        // Expire all other login records in database
        $stmt = $this->db->prepare("UPDATE `user_logins` SET `session_id` = NULL WHERE `user_id` = :user_id AND `session_id` != :sid");
        $stmt->execute([':user_id' => $user['id'], ':sid' => $currentSessionId]);

        $this->security->logSecurityEvent('logout_other_sessions', 'low', $user['id'], null, $request->ip(), $request->userAgent());

        flash('success', 'Other sessions have been invalidated.');
        return $this->redirect('/account/security');
    }
}
