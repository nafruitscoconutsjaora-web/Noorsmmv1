<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(Request $request): Response
    {
        return view('auth/login', [], 'auth');
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'login' => 'required',
            'password' => 'required',
        ]);

        $user = $this->authService->login($data['login'], $data['password']);

        Session::setFlash('success', "Welcome back, {$user['username']}!");
        return $this->redirect('/dashboard');
    }

    public function showRegister(Request $request): Response
    {
        return view('auth/register', [], 'auth');
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'username' => 'required|min:3|max:30|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
        ]);

        $user = $this->authService->register([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'timezone' => $request->post('timezone', 'UTC'),
        ]);

        Session::setFlash('success', "Your account has been created successfully! Welcome to the platform.");
        return $this->redirect('/dashboard');
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();
        Session::setFlash('success', 'You have been logged out safely.');
        return $this->redirect('/login');
    }
}
