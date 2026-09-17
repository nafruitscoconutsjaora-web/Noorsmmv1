<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Repositories\AdminRepository;
use App\Repositories\UserRepository;
use App\Repositories\AuditLogRepository;

class AuthService
{
    private UserRepository $userRepo;
    private AdminRepository $adminRepo;
    private AuditLogRepository $auditRepo;

    public function __construct(
        ?UserRepository $userRepo = null,
        ?AdminRepository $adminRepo = null,
        ?AuditLogRepository $auditRepo = null
    ) {
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->adminRepo = $adminRepo ?? new AdminRepository();
        $this->auditRepo = $auditRepo ?? new AuditLogRepository();
    }

    public function register(array $data): array
    {
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $userId = $this->userRepo->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'balance' => '0.00000000',
            'status' => 'active',
            'timezone' => $data['timezone'] ?? 'UTC',
        ]);

        $user = $this->userRepo->findById($userId);
        $this->loginAsUser($user);
        return $user;
    }

    public function login(string $login, string $password): array
    {
        $user = str_contains($login, '@') 
            ? $this->userRepo->findByEmail($login) 
            : $this->userRepo->findByUsername($login);

        if (!$user) {
            throw new AuthenticationException('Invalid username/email or password.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new AuthenticationException('Invalid username/email or password.');
        }

        if ($user['status'] === 'suspended') {
            throw new AuthenticationException('Your account has been suspended. Please contact support.');
        }

        $this->loginAsUser($user);
        return $user;
    }

    public function adminLogin(string $login, string $password, ?string $ip = null): array
    {
        $admin = str_contains($login, '@') 
            ? $this->adminRepo->findByEmail($login) 
            : $this->adminRepo->findByUsername($login);

        if (!$admin) {
            throw new AuthenticationException('Invalid administrator credentials.');
        }

        if (!password_verify($password, $admin['password_hash'])) {
            throw new AuthenticationException('Invalid administrator credentials.');
        }

        if ($admin['status'] === 'suspended') {
            throw new AuthenticationException('This administrator account is deactivated.');
        }

        $this->adminRepo->updateLastLogin((int)$admin['id']);
        Session::regenerate();
        Session::set('admin', [
            'id' => (int)$admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'role_id' => (int)$admin['role_id'],
            'status' => $admin['status'],
        ]);

        $this->auditRepo->log((int)$admin['id'], 'admin_login', 'admin', (int)$admin['id'], ['ip' => $ip], $ip);

        return $admin;
    }

    public function loginAsUser(array $user): void
    {
        Session::regenerate();
        Session::set('user', [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'balance' => $user['balance'],
            'status' => $user['status'],
            'api_key' => $user['api_key'],
            'timezone' => $user['timezone'],
        ]);
    }

    public function refreshUserSession(int $userId): void
    {
        $user = $this->userRepo->findById($userId);
        if ($user) {
            Session::set('user', [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'balance' => $user['balance'],
                'status' => $user['status'],
                'api_key' => $user['api_key'],
                'timezone' => $user['timezone'],
            ]);
        }
    }

    public function changePassword(int $userId, string $oldPassword, string $newPassword): void
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found.');
        }

        if (!password_verify($oldPassword, $user['password_hash'])) {
            throw new ValidationException(['old_password' => 'Current password is incorrect.']);
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepo->updatePassword($userId, $newHash);
    }

    public function regenerateApiKey(int $userId): string
    {
        $newKey = bin2hex(random_bytes(24));
        $this->userRepo->updateApiKey($userId, $newKey);
        $this->refreshUserSession($userId);
        return $newKey;
    }

    public function logout(): void
    {
        Session::remove('user');
    }

    public function adminLogout(): void
    {
        Session::remove('admin');
    }
}
