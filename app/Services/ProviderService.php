<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\AppException;
use App\Repositories\ProviderRepository;
use App\Support\HttpClient;
use App\Support\Logger;

class ProviderService
{
    private Database $db;
    private ProviderRepository $providerRepo;
    private HttpClient $http;

    public function __construct(
        ?Database $db = null,
        ?ProviderRepository $providerRepo = null,
        ?HttpClient $http = null
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->providerRepo = $providerRepo ?? new ProviderRepository($this->db);
        $this->http = $http ?? new HttpClient((int)config('providers.timeout_seconds', 30));
    }

    /**
     * Test connection and retrieve balance from provider API
     */
    public function testConnection(int $providerId): array
    {
        $provider = $this->providerRepo->findById($providerId);
        if (!$provider) {
            throw new AppException("Provider #{$providerId} not found");
        }

        $res = $this->request($provider['api_url'], [
            'key' => $provider['api_key'],
            'action' => 'balance',
        ]);

        if (isset($res['data']['balance'])) {
            $balance = (string)$res['data']['balance'];
            $this->providerRepo->updateBalance($providerId, $balance);
            $this->providerRepo->updateSyncStatus($providerId, 'success', null);
            return [
                'success' => true,
                'balance' => $balance,
                'currency' => $res['data']['currency'] ?? $provider['currency'],
            ];
        }

        $err = $res['data']['error'] ?? 'Unexpected API response format';
        $this->providerRepo->updateSyncStatus($providerId, 'failed', (string)$err);
        return [
            'success' => false,
            'error' => $err,
        ];
    }

    /**
     * Fetch all services from provider
     */
    public function fetchServices(int $providerId): array
    {
        $provider = $this->providerRepo->findById($providerId);
        if (!$provider) {
            throw new AppException("Provider #{$providerId} not found");
        }

        $res = $this->request($provider['api_url'], [
            'key' => $provider['api_key'],
            'action' => 'services',
        ]);

        if (!is_array($res['data'])) {
            $err = $res['data']['error'] ?? 'Failed to fetch services list';
            $this->providerRepo->updateSyncStatus($providerId, 'failed', (string)$err);
            throw new AppException("Provider error: {$err}");
        }

        $this->providerRepo->updateSyncStatus($providerId, 'success', null);
        return $res['data'];
    }

    /**
     * Place order with provider API
     */
    public function sendOrder(array $provider, array $order, array $service): array
    {
        $params = [
            'key' => $provider['api_key'],
            'action' => 'add',
            'service' => $service['provider_service_id'],
            'link' => $order['link'],
            'quantity' => $order['quantity'],
        ];

        try {
            $res = $this->request($provider['api_url'], $params);
            $data = $res['data'];

            if (isset($data['order'])) {
                Logger::info("Order #{$order['id']} placed successfully with provider #{$provider['id']}", [
                    'provider_order_id' => $data['order']
                ], 'provider');
                return [
                    'success' => true,
                    'provider_order_id' => (string)$data['order'],
                    'response' => json_encode($data),
                ];
            }

            $errMsg = $data['error'] ?? ($res['body'] ?: 'Unknown error from provider');
            Logger::error("Order #{$order['id']} failed with provider #{$provider['id']}: {$errMsg}", [], 'provider');
            return [
                'success' => false,
                'error' => (string)$errMsg,
                'response' => json_encode($data ?: ['raw' => $res['body']]),
            ];
        } catch (\Throwable $e) {
            Logger::error("Exception placing order #{$order['id']} with provider: " . $e->getMessage(), [], 'provider');
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null,
            ];
        }
    }

    /**
     * Check status of an order on provider
     */
    public function checkOrderStatus(array $provider, string $providerOrderId): array
    {
        $res = $this->request($provider['api_url'], [
            'key' => $provider['api_key'],
            'action' => 'status',
            'order' => $providerOrderId,
        ]);

        $data = $res['data'];
        if (isset($data['status'])) {
            return [
                'success' => true,
                'status' => strtolower((string)$data['status']),
                'charge' => $data['charge'] ?? null,
                'start_count' => isset($data['start_count']) ? (int)$data['start_count'] : null,
                'remains' => isset($data['remains']) ? (int)$data['remains'] : null,
                'raw' => $data,
            ];
        }

        return [
            'success' => false,
            'error' => $data['error'] ?? 'Unable to fetch status',
        ];
    }

    /**
     * Request refill on provider
     */
    public function sendRefill(array $provider, string $providerOrderId): array
    {
        $res = $this->request($provider['api_url'], [
            'key' => $provider['api_key'],
            'action' => 'refill',
            'order' => $providerOrderId,
        ]);

        return $res['data'] ?? ['error' => 'No response from provider'];
    }

    /**
     * Request cancel on provider
     */
    public function sendCancel(array $provider, string $providerOrderId): array
    {
        $res = $this->request($provider['api_url'], [
            'key' => $provider['api_key'],
            'action' => 'cancel',
            'order' => $providerOrderId,
        ]);

        return $res['data'] ?? ['error' => 'No response from provider'];
    }

    private function request(string $url, array $params): array
    {
        return $this->http->post($url, $params);
    }
}
