<?php

declare(strict_types=1);

namespace App\Providers\Drivers;

use App\Providers\Contracts\ProviderInterface;
use App\Support\HttpClient;
use App\Exceptions\AppException;

class GenericProvider implements ProviderInterface
{
    private string $apiUrl;
    private string $apiKey;
    private HttpClient $http;

    public function __construct(string $apiUrl, string $apiKey, ?HttpClient $http = null)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->http = $http ?? new HttpClient((int)config('providers.timeout_seconds', 30));
    }

    /**
     * Get account balance from provider
     */
    public function getBalance(): array
    {
        $res = $this->http->post($this->apiUrl, [
            'key' => $this->apiKey,
            'action' => 'balance',
        ]);

        if (isset($res['data']['balance'])) {
            return [
                'success' => true,
                'balance' => (string)$res['data']['balance'],
                'currency' => $res['data']['currency'] ?? 'USD',
                'raw' => $res['data'],
            ];
        }

        return [
            'success' => false,
            'error' => $res['data']['error'] ?? ($res['body'] ?: 'Failed to retrieve balance from provider'),
            'raw' => $res['data'],
        ];
    }

    /**
     * Retrieve full list of wholesale services from provider
     */
    public function getServices(): array
    {
        $res = $this->http->post($this->apiUrl, [
            'key' => $this->apiKey,
            'action' => 'services',
        ]);

        if (is_array($res['data'])) {
            return [
                'success' => true,
                'services' => $res['data'],
            ];
        }

        return [
            'success' => false,
            'error' => $res['data']['error'] ?? ($res['body'] ?: 'Failed to fetch services'),
            'services' => [],
        ];
    }

    /**
     * Submit an order to the provider
     */
    public function addOrder(array $orderData): array
    {
        $payload = array_merge([
            'key' => $this->apiKey,
            'action' => 'add',
        ], $orderData);

        $res = $this->http->post($this->apiUrl, $payload);
        $data = $res['data'];

        if (isset($data['order'])) {
            return [
                'success' => true,
                'order' => (string)$data['order'],
                'raw' => $data,
            ];
        }

        return [
            'success' => false,
            'error' => $data['error'] ?? ($res['body'] ?: 'Order placement rejected by provider'),
            'raw' => $data,
        ];
    }

    /**
     * Retrieve status of a single order
     */
    public function getOrderStatus(string|int $providerOrderId): array
    {
        $res = $this->http->post($this->apiUrl, [
            'key' => $this->apiKey,
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
            'error' => $data['error'] ?? ($res['body'] ?: 'Failed to fetch order status'),
            'raw' => $data,
        ];
    }

    /**
     * Retrieve statuses of multiple orders
     */
    public function getMultipleOrdersStatus(array $providerOrderIds): array
    {
        $res = $this->http->post($this->apiUrl, [
            'key' => $this->apiKey,
            'action' => 'status',
            'orders' => implode(',', $providerOrderIds),
        ]);

        if (is_array($res['data'])) {
            return [
                'success' => true,
                'statuses' => $res['data'],
            ];
        }

        return [
            'success' => false,
            'error' => $res['data']['error'] ?? 'Failed to retrieve multi-order statuses',
            'statuses' => [],
        ];
    }
}
