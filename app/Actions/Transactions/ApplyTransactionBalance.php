<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;

final class ApplyTransactionBalance
{
    public function handle(Transaction $transaction, int $direction = 1): void
    {
        foreach ($this->walletDeltas($transaction) as $walletId => $delta) {
            if ($delta !== 0) {
                Wallet::whereKey($walletId)->increment('cached_balance', $direction * $delta);
            }
        }
    }

    /**
     * @return array<int, int>
     */
    private function walletDeltas(Transaction $transaction): array
    {
        return match ($transaction->type) {
            TransactionType::Income,
            TransactionType::CapitalInvestment => [$transaction->wallet_id => $transaction->amount],
            TransactionType::Expense,
            TransactionType::CapitalWithdrawal => [$transaction->wallet_id => -$transaction->amount],
            TransactionType::Transfer => [
                $transaction->wallet_id => -$transaction->amount,
                (int) $transaction->counter_wallet_id => $transaction->amount,
            ],
        };
    }
}
