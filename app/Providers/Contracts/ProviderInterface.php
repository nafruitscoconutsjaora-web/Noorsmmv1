<?php

declare(strict_types=1);

namespace App\Providers\Contracts;

interface ProviderInterface
{
    /**
     * Get account balance from provider
     */
    public function getBalance(): array;

    /**
     * Retrieve full list of wholesale services from provider
     */
    public function getServices(): array;

    /**
     * Submit an order to the provider
     *
     * @param array $orderData [service, link, quantity, custom_data, etc.]
     */
    public function addOrder(array $orderData): array;

    /**
     * Retrieve status of a single order
     */
    public function getOrderStatus(string|int $providerOrderId): array;

    /**
     * Retrieve statuses of multiple orders
     *
     * @param array $providerOrderIds
     */
    public function getMultipleOrdersStatus(array $providerOrderIds): array;
}
