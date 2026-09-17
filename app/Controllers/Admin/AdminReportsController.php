<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\ExportService;

class AdminReportsController extends BaseController
{
    private ExportService $exporter;

    public function __construct()
    {
        $this->exporter = new ExportService();
    }

    public function index(Request $request): Response
    {
        return view('admin/reports/index', [], 'admin');
    }

    public function download(Request $request, string $type): Response
    {
        if (!in_array($type, ['orders', 'users', 'payments'])) {
            $type = 'orders';
        }
        return $this->exporter->exportAdminReportCsv($type);
    }
}
