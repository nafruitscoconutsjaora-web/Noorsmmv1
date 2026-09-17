<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\UserRepository;
use App\Services\AuthService;

class AuthTest
{
    public function run(): void
    {
        $authService = new AuthService();
        $userRepo = new UserRepository();

        $testUsername = 'testuser_' . bin2hex(random_bytes(3));
        $testEmail = $testUsername . '@example.com';
        $testPassword = 'Password123!';

        // 1. Register user
        $user = $authService->register([
            'username' => $testUsername,
            'email' => $testEmail,
            'password' => $testPassword,
            'password_confirmation' => $testPassword,
        ]);

        assert(!empty($user['id']), 'User registration failed to create user ID');
        assert($user['username'] === $testUsername, 'Registered username mismatch');
        assert(!empty($user['api_key']), 'API key was not generated on registration');

        // 2. Successful Login
        $loggedUser = $authService->login($testUsername, $testPassword);
        assert($loggedUser !== null, 'Authentication failed for valid credentials');
        assert((int)$loggedUser['id'] === (int)$user['id'], 'Authenticated user ID mismatch');

        // 3. Failed Login (Wrong Password)
        try {
            $authService->login($testUsername, 'WrongPassword!');
            assert(false, 'Expected authentication failure for invalid password');
        } catch (\Throwable $e) {
            // Expected
            assert(true);
        }
    }
}
