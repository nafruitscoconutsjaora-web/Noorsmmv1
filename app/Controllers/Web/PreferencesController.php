<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SecurityRepository;

class PreferencesController extends BaseController
{
    private SecurityRepository $security;

    public function __construct()
    {
        $this->security = new SecurityRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $prefs = $this->security->getPreferences($user['id']);

        return view('user/account/preferences', [
            'preferences' => $prefs,
            'user' => $user,
        ], 'user');
    }

    public function update(Request $request): Response
    {
        $user = $this->user();
        $data = [
            'notify_order_status' => $request->input('notify_order_status'),
            'notify_wallet_deposit' => $request->input('notify_wallet_deposit'),
            'notify_ticket_reply' => $request->input('notify_ticket_reply'),
            'notify_news' => $request->input('notify_news'),
            'language' => $request->input('language', 'en'),
            'timezone' => $request->input('timezone', 'UTC'),
            'display_density' => $request->input('display_density', 'normal'),
        ];

        $this->security->updatePreferences($user['id'], $data);
        flash('success', 'Preferences updated successfully.');
        return $this->redirect('/account/preferences');
    }
}
