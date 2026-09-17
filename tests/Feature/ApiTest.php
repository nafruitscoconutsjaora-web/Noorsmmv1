<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Api\ApiController;
use App\Core\Request;
use App\Repositories\UserRepository;

class ApiTest
{
    public function run(): void
    {
        $userRepo = new UserRepository();
        $user = $userRepo->findByUsername('demo');
        assert($user !== null, 'Demo user must exist for ApiTest');

        $apiKey = $user['api_key'];
        $controller = new ApiController();

        // 1. Test Action: Balance
        $reqBalance = new Request([], ['key' => $apiKey, 'action' => 'balance']);
        $respBalance = $controller->handle($reqBalance);
        assert($respBalance->getStatusCode() === 200, 'Balance request failed with status ' . $respBalance->getStatusCode());
        $data = json_decode($respBalance->getContent(), true);
        assert(isset($data['balance']) && isset($data['currency']), 'Balance response format invalid');

        // 2. Test Action: Services
        $reqServices = new Request([], ['key' => $apiKey, 'action' => 'services']);
        $respServices = $controller->handle($reqServices);
        assert($respServices->getStatusCode() === 200, 'Services request failed');
        $services = json_decode($respServices->getContent(), true);
        assert(is_array($services) && count($services) > 0, 'Services list empty');

        // 3. Test Invalid API Key
        $reqInvalid = new Request([], ['key' => 'invalid_key_xyz', 'action' => 'balance']);
        $respInvalid = $controller->handle($reqInvalid);
        assert($respInvalid->getStatusCode() === 401, 'Expected 401 for invalid key');

        // 4. Test Missing Action
        $reqNoAction = new Request([], ['key' => $apiKey]);
        $respNoAction = $controller->handle($reqNoAction);
        assert($respNoAction->getStatusCode() === 400, 'Expected 400 for missing action');
    }
}
