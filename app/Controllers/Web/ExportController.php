<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\ExportService;

class ExportController extends BaseController
{
    private ExportService $exporter;

    public function __construct()
    {
        $this->exporter = new ExportService();
    }

    public function index(Request $request): Response
    {
        return view('user/reports/export', [], 'user');
    }

    public function exportOrders(Request $request): Response
    {
        $user = $this->user();
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        return $this->exporter->exportUserOrdersCsv($user['id'], $startDate, $endDate);
    }

    public function exportWallet(Request $request): Response
    {
        $user = $this->user();
        return $this->exporter->exportUserWalletCsv($user['id']);
    }
}
