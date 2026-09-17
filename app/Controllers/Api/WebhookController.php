<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\PaymentService;

class WebhookController extends BaseController
{
    private PaymentService $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    public function razorpay(Request $request): Response
    {
        $rawPayload = (string)file_get_contents('php://input');
        $signature = (string)$request->header('X-Razorpay-Signature', '');

        $handled = $this->paymentService->handleRazorpayWebhook($rawPayload, $signature);
        if ($handled) {
            return $this->json(['status' => 'success']);
        }

        return $this->json(['error' => 'Webhook processing failed or invalid signature'], 400);
    }
}
