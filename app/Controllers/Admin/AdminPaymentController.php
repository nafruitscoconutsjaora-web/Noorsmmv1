<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PaymentRepository;

class AdminPaymentController extends BaseController
{
    private PaymentRepository $paymentRepo;

    public function __construct()
    {
        $this->paymentRepo = new PaymentRepository();
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query('page', 1));
        $status = (string)$request->query('status', 'all');
        $search = trim((string)$request->query('search', ''));

        $payments = $this->paymentRepo->getAdminPaginated($page, 20, $status, $search);

        return view('admin/payments/index', [
            'payments' => $payments,
            'current_status' => $status,
            'search' => $search,
        ], 'admin');
    }
}
