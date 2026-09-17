<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AuditLogRepository;
use App\Repositories\CronLogRepository;
use App\Repositories\SettingRepository;

class AdminSettingController extends BaseController
{
    private SettingRepository $settingRepo;
    private CronLogRepository $cronRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->settingRepo = new SettingRepository();
        $this->cronRepo = new CronLogRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    public function index(Request $request): Response
    {
        $settings = $this->settingRepo->getAll();
        $cronLogs = $this->cronRepo->getRecent(15);

        return view('admin/settings/index', [
            'settings' => $settings,
            'cron_logs' => $cronLogs,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $post = $request->post();
        unset($post['_csrf_token']);

        // Handle checkbox fields that might not be in POST
        if (!isset($post['maintenance_mode'])) {
            $post['maintenance_mode'] = '0';
        }

        $this->settingRepo->updateBatch($post);

        $admin = $this->admin();
        $this->auditRepo->log((int)$admin['id'], 'update_settings', 'settings', null, array_keys($post), $request->ip());

        Session::setFlash('success', 'System settings saved successfully.');
        return $this->redirect('/admin/settings');
    }
}
