<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\AuthService;

class AccountController extends BaseController
{
    private UserRepository $userRepo;
    private AuthService $authService;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->authService = new AuthService();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $this->authService->refreshUserSession((int)$user['id']);
        $freshUser = $this->userRepo->findById((int)$user['id']);

        return view('user/account/index', [
            'user' => $freshUser,
        ], 'user');
    }

    public function changePassword(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        $user = $this->user();
        $this->authService->changePassword((int)$user['id'], $data['old_password'], $data['new_password']);

        Session::setFlash('success', 'Your password has been changed successfully.');
        return $this->redirect('/account');
    }

    public function generateApiKey(Request $request): Response
    {
        $user = $this->user();
        $newKey = $this->authService->regenerateApiKey((int)$user['id']);

        Session::setFlash('success', 'Your API Key has been regenerated. Update your external integrations immediately.');
        return $this->redirect('/account');
    }
}
