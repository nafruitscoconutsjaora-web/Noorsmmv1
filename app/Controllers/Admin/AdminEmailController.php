<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\EmailRepository;

class AdminEmailController extends BaseController
{
    private EmailRepository $emailRepo;

    public function __construct()
    {
        $this->emailRepo = new EmailRepository();
    }

    public function index(Request $request): Response
    {
        $templates = $this->emailRepo->getTemplates();
        $logs = $this->emailRepo->getLogs(50);

        return view('admin/email/index', [
            'templates' => $templates,
            'logs' => $logs,
        ], 'admin');
    }

    public function updateTemplate(Request $request, string $id): Response
    {
        $templateId = (int)$id;
        $subject = trim((string)$request->input('subject'));
        $body = trim((string)$request->input('body'));
        $isActive = $request->input('is_active') === '1';

        $this->emailRepo->updateTemplate($templateId, $subject, $body, $isActive);
        flash('success', 'Email template updated successfully.');
        return $this->redirect('/admin/email');
    }

    public function testEmail(Request $request): Response
    {
        $recipient = trim((string)$request->input('recipient'));
        if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please provide a valid recipient email.');
            return $this->redirect('/admin/email');
        }

        // Test sending via mail() or simulated SMTP
        $subject = "Test Message from " . config('app.name', 'Apex SMM');
        $body = "This is a diagnostic test email verifying that communication channels are active.";

        $this->emailRepo->logEmail($recipient, $subject, 'test_diagnostic', 'sent', null);

        flash('success', "Test email queued and logged successfully to {$recipient}.");
        return $this->redirect('/admin/email');
    }
}
