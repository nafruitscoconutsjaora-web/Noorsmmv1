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

    /**
     * Legacy Razorpay webhook endpoint
     */
    public function razorpay(Request $request): Response
    {
        return $this->handleGatewayWebhook($request, 'razorpay');
    }

    /**
     * Universal webhook handler for all 46+ payment gateways
     */
    public function handleGatewayWebhook(Request $request, string $gateway): Response
    {
        $gatewayCode = strtolower(trim($gateway));
        $handled = $this->paymentService->handleWebhook($gatewayCode, $request);

        if ($handled) {
            return $this->json([
                'status' => 'success',
                'gateway' => $gatewayCode,
                'timestamp' => time(),
            ]);
        }

        return $this->json([
            'status' => 'failed',
            'gateway' => $gatewayCode,
            'error' => 'Webhook verification failed or transaction not completed',
        ], 400);
    }
}
