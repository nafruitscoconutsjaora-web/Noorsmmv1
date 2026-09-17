<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\Validator;

class ValidatorTest
{
    public function run(): void
    {
        // 1. Valid data passes
        $v = Validator::make([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'amount' => '50.00',
            'status' => 'active',
        ], [
            'username' => 'required|min:3|max:20',
            'email' => 'required|email',
            'amount' => 'required|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        assert($v->fails() === false, 'Validator failed on valid data');

        // 2. Missing required field fails
        $v2 = Validator::make([
            'email' => 'invalid-email',
        ], [
            'username' => 'required',
            'email' => 'required|email',
        ]);

        assert($v2->fails() === true, 'Validator should fail on missing username and invalid email');
        $errors = $v2->errors();
        assert(isset($errors['username']), 'Username required error missing');
        assert(isset($errors['email']), 'Email error missing');

        // 3. Same rule check (password confirmation)
        $v3 = Validator::make([
            'password' => 'secret123',
            'password_confirmation' => 'mismatch',
        ], [
            'password_confirmation' => 'same:password',
        ]);

        assert($v3->fails() === true, 'Validator should fail on mismatched password confirmation');
    }
}
