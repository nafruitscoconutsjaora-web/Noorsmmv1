<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\AppException;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Support\Money;

class WalletService
{
    private Database $db;
    private UserRepository $userRepo;
    private WalletRepository $walletRepo;

    public function __construct(
        ?Database $db = null,
        ?UserRepository $userRepo = null,
        ?WalletRepository $walletRepo = null
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
        $this->walletRepo = $walletRepo ?? new WalletRepository($this->db);
    }

    /**
     * Atomically debit user wallet with row lock (FOR UPDATE)
     */
    public function debit(
        int $userId,
        string $amount,
        string $referenceType,
        ?int $referenceId = null,
        string $description = ''
    ): string {
        if (Money::lte($amount, '0')) {
            throw new AppException('Debit amount must be strictly greater than zero.');
        }

        return $this->db->transaction(function () use ($userId, $amount, $referenceType, $referenceId, $description) {
            $user = $this->userRepo->findByIdForUpdate($userId);
            if (!$user) {
                throw new AppException('User not found during wallet operation.');
            }

            $currentBalance = $user['balance'];

            if (Money::lt($currentBalance, $amount)) {
                throw new AppException('Insufficient wallet balance. Please add funds to proceed.');
            }

            $newBalance = Money::sub($currentBalance, $amount);

            $this->userRepo->updateBalance($userId, $newBalance);

            $this->walletRepo->createTransaction([
                'user_id' => $userId,
                'amount' => $amount,
                'balance_before' => $currentBalance,
                'balance_after' => $newBalance,
                'type' => 'debit',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);

            return $newBalance;
        });
    }

    /**
     * Atomically credit user wallet with row lock (FOR UPDATE)
     */
    public function credit(
        int $userId,
        string $amount,
        string $referenceType,
        ?int $referenceId = null,
        string $description = ''
    ): string {
        if (Money::lte($amount, '0')) {
            throw new AppException('Credit amount must be strictly greater than zero.');
        }

        return $this->db->transaction(function () use ($userId, $amount, $referenceType, $referenceId, $description) {
            $user = $this->userRepo->findByIdForUpdate($userId);
            if (!$user) {
                throw new AppException('User not found during wallet credit.');
            }

            $currentBalance = $user['balance'];
            $newBalance = Money::add($currentBalance, $amount);

            $this->userRepo->updateBalance($userId, $newBalance);

            $this->walletRepo->createTransaction([
                'user_id' => $userId,
                'amount' => $amount,
                'balance_before' => $currentBalance,
                'balance_after' => $newBalance,
                'type' => 'credit',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);

            return $newBalance;
        });
    }

    /**
     * Atomically refund charge back to user wallet
     */
    public function refund(
        int $userId,
        string $amount,
        string $referenceType,
        ?int $referenceId = null,
        string $description = ''
    ): string {
        if (Money::lte($amount, '0')) {
            throw new AppException('Refund amount must be strictly greater than zero.');
        }

        return $this->db->transaction(function () use ($userId, $amount, $referenceType, $referenceId, $description) {
            $user = $this->userRepo->findByIdForUpdate($userId);
            if (!$user) {
                throw new AppException('User not found during refund operation.');
            }

            $currentBalance = $user['balance'];
            $newBalance = Money::add($currentBalance, $amount);

            $this->userRepo->updateBalance($userId, $newBalance);

            $this->walletRepo->createTransaction([
                'user_id' => $userId,
                'amount' => $amount,
                'balance_before' => $currentBalance,
                'balance_after' => $newBalance,
                'type' => 'refund',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);

            return $newBalance;
        });
    }

    /**
     * Get user current wallet balance
     */
    public function getBalance(int $userId): string
    {
        $user = $this->userRepo->findById($userId);
        return $user ? (string)$user['balance'] : '0.00000000';
    }
}
