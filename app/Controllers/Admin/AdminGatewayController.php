<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Payments\PaymentManager;
use App\Support\Encryption;
use App\Support\Logger;
use PDO;

class AdminGatewayController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * List all payment gateways grouped by category with live status & metrics.
     */
    public function index(Request $request): Response
    {
        PaymentManager::syncRegisteredGatewaysToDatabase();

        $category = (string)$request->query('category', 'all');
        $search = trim((string)$request->query('search', ''));

        $rawGateways = PaymentManager::getAllGatewaysForAdmin();

        // Aggregate summary counts across all gateways
        $totalCount = count($rawGateways);
        $activeCount = count(array_filter($rawGateways, fn($g) => !empty($g['is_enabled'])));
        $indianCount = count(array_filter($rawGateways, fn($g) => in_array($g['category'] ?? '', ['india', 'indian'], true)));
        $intlCount = count(array_filter($rawGateways, fn($g) => ($g['category'] ?? '') === 'international'));
        $cryptoCount = count(array_filter($rawGateways, fn($g) => ($g['category'] ?? '') === 'crypto'));
        $manualCount = count(array_filter($rawGateways, fn($g) => ($g['category'] ?? '') === 'manual'));

        $filteredGateways = $rawGateways;

        if ($category !== 'all' && !empty($category)) {
            $catMatches = ($category === 'indian' || $category === 'india') ? ['india', 'indian'] : [$category];
            $filteredGateways = array_filter($filteredGateways, fn($g) => in_array($g['category'] ?? '', $catMatches, true));
        }

        if (!empty($search)) {
            $searchLower = strtolower($search);
            $filteredGateways = array_filter(
                $filteredGateways,
                fn($g) => str_contains(strtolower($g['name']), $searchLower) || str_contains(strtolower($g['code']), $searchLower)
            );
        }

        return view('admin/gateways/index', [
            'gateways' => $filteredGateways,
            'category' => $category,
            'search' => $search,
            'metrics' => [
                'total' => $totalCount,
                'active' => $activeCount,
                'indian' => $indianCount,
                'international' => $intlCount,
                'crypto' => $cryptoCount,
                'manual' => $manualCount,
            ],
        ], 'admin');
    }

    /**
     * Edit gateway modal or configuration page.
     */
    public function edit(Request $request, string $id): Response
    {
        $stmt = $this->db->prepare('SELECT * FROM payment_gateways WHERE id = :id OR code = :code LIMIT 1');
        $stmt->execute(['id' => (int)$id, 'code' => $id]);
        $gateway = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gateway) {
            Session::setFlash('error', 'Payment gateway not found.');
            return $this->redirect('/admin/gateways');
        }

        $code = $gateway['code'];
        $adapter = PaymentManager::getGateway($code);

        $gateway['config'] = !empty($gateway['config']) ? json_decode($gateway['config'], true) : [];
        $gateway['category'] = $adapter ? $adapter->getCategory() : 'international';
        $gateway['credential_fields'] = $adapter ? $adapter->getCredentialFields() : [];
        $gateway['supported_currencies'] = $adapter ? $adapter->getSupportedCurrencies() : [];

        // Decrypt stored credentials for editing
        $decryptedCredentials = [];
        if (!empty($gateway['credentials'])) {
            try {
                $dec = Encryption::decrypt($gateway['credentials']);
                $decryptedCredentials = json_decode($dec, true) ?: [];
            } catch (\Throwable $e) {
                $decryptedCredentials = [];
            }
        }
        $gateway['decrypted_credentials'] = $decryptedCredentials;

        $appUrl = rtrim(config('app.url', 'http://localhost:3000'), '/');
        $gateway['webhook_url'] = $appUrl . '/webhook/' . $code;

        return view('admin/gateways/edit', [
            'gateway' => $gateway,
            'adapter' => $adapter,
        ], 'admin');
    }

    /**
     * Save/Update gateway configuration, fees, bonus, and encrypted credentials.
     */
    public function update(Request $request, string $id): Response
    {
        $stmt = $this->db->prepare('SELECT * FROM payment_gateways WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int)$id]);
        $gateway = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gateway) {
            Session::setFlash('error', 'Payment gateway not found.');
            return $this->redirect('/admin/gateways');
        }

        $code = $gateway['code'];
        $adapter = PaymentManager::getGateway($code);
        $fields = $adapter ? $adapter->getCredentialFields() : [];

        // Process incoming credentials
        $existingCredentials = [];
        if (!empty($gateway['credentials'])) {
            try {
                $dec = Encryption::decrypt($gateway['credentials']);
                $existingCredentials = json_decode($dec, true) ?: [];
            } catch (\Throwable) {
                $existingCredentials = [];
            }
        }

        $incomingCreds = (array)$request->input('credentials', []);
        $mergedCredentials = $existingCredentials;

        foreach ($fields as $fieldKey => $fieldMeta) {
            if (isset($incomingCreds[$fieldKey])) {
                $val = trim((string)$incomingCreds[$fieldKey]);
                // If password/secret field is left blank, keep existing value
                if ($fieldMeta['type'] === 'password' && $val === '') {
                    continue;
                }
                $mergedCredentials[$fieldKey] = $val;
            }
        }

        $encryptedCreds = Encryption::encrypt(json_encode($mergedCredentials));

        $name = trim((string)$request->input('name', $gateway['name']));
        $isEnabled = $request->has('is_enabled') ? 1 : 0;
        $sortOrder = (int)$request->input('sort_order', $gateway['sort_order']);
        $currency = trim((string)$request->input('currency', $gateway['currency']));
        $minAmount = max(0.01, (float)$request->input('min_amount', 10.00));
        $maxAmount = max($minAmount, (float)$request->input('max_amount', 50000.00));
        $fixedFee = max(0.00, (float)$request->input('fixed_fee', 0.00));
        $percentFee = max(0.00, (float)$request->input('percent_fee', 0.00));

        $bonusEnabled = $request->has('bonus_enabled') ? 1 : 0;
        $bonusType = (string)$request->input('bonus_type', 'percentage');
        if (!in_array($bonusType, ['percentage', 'fixed'], true)) {
            $bonusType = 'percentage';
        }
        $bonusValue = max(0.00, (float)$request->input('bonus_value', 0.00));
        $maxBonus = max(0.00, (float)$request->input('max_bonus', 0.00));
        $instructions = trim((string)$request->input('instructions', ''));
        $description = trim((string)$request->input('description', ''));

        // Update database row
        $updateSql = '
            UPDATE payment_gateways SET
                name = :name,
                is_enabled = :is_enabled,
                sort_order = :sort_order,
                currency = :currency,
                min_amount = :min_amount,
                max_amount = :max_amount,
                fixed_fee = :fixed_fee,
                percent_fee = :percent_fee,
                bonus_enabled = :bonus_enabled,
                bonus_type = :bonus_type,
                bonus_value = :bonus_value,
                max_bonus = :max_bonus,
                instructions = :instructions,
                description = :description,
                credentials = :credentials,
                updated_at = NOW()
            WHERE id = :id
        ';

        $this->db->execute($updateSql, [
            ':name' => $name,
            ':is_enabled' => $isEnabled,
            ':sort_order' => $sortOrder,
            ':currency' => $currency,
            ':min_amount' => $minAmount,
            ':max_amount' => $maxAmount,
            ':fixed_fee' => $fixedFee,
            ':percent_fee' => $percentFee,
            ':bonus_enabled' => $bonusEnabled,
            ':bonus_type' => $bonusType,
            ':bonus_value' => $bonusValue,
            ':max_bonus' => $maxBonus,
            ':instructions' => $instructions,
            ':description' => $description,
            ':credentials' => $encryptedCreds,
            ':id' => (int)$id,
        ]);

        Logger::info("Admin updated payment gateway configuration [{$code}]", [
            'id' => $id,
            'is_enabled' => $isEnabled,
            'admin_id' => $this->user()['id'] ?? 1,
        ]);

        Session::setFlash('success', "Payment gateway '{$name}' updated successfully.");
        return $this->redirect('/admin/gateways');
    }

    /**
     * Quick toggle enabled/disabled status.
     */
    public function toggle(Request $request, string $id): Response
    {
        $stmt = $this->db->prepare('SELECT id, name, is_enabled FROM payment_gateways WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int)$id]);
        $gateway = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gateway) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'error' => 'Gateway not found'], 404);
            }
            Session::setFlash('error', 'Gateway not found.');
            return $this->redirect('/admin/gateways');
        }

        $newStatus = empty($gateway['is_enabled']) ? 1 : 0;
        $this->db->execute('UPDATE payment_gateways SET is_enabled = :status, updated_at = NOW() WHERE id = :id', [
            ':status' => $newStatus,
            ':id' => (int)$id,
        ]);

        $statusText = $newStatus ? 'enabled' : 'disabled';
        if ($request->isAjax()) {
            return $this->json([
                'success' => true,
                'is_enabled' => $newStatus,
                'message' => "Gateway '{$gateway['name']}' {$statusText}.",
            ]);
        }

        Session::setFlash('success', "Gateway '{$gateway['name']}' {$statusText}.");
        return $this->redirect('/admin/gateways');
    }
}
