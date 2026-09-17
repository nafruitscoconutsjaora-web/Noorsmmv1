<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

class AdminAuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(Request $request): Response
    {
        if (Session::get('admin')) {
            return $this->redirect('/admin/dashboard');
        }

        return view('admin/auth/login', [], 'auth');
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'login' => 'required',
            'password' => 'required',
        ]);

        $admin = $this->authService->adminLogin($data['login'], $data['password'], $request->ip());

        Session::setFlash('success', "Welcome back, {$admin['username']}.");
        return $this->redirect('/admin/dashboard');
    }

    public function logout(Request $request): Response
    {
        $this->authService->adminLogout();
        Session::setFlash('success', 'Logged out of administrator portal.');
        return $this->redirect('/admin/login');
    }
}
