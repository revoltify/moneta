<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class UpdateTransaction
{
    public function __construct(private ApplyTransactionBalance $applyBalance) {}

    public function handle(
        Transaction $transaction,
        Wallet $wallet,
        int $amount,
        CarbonInterface $date,
        ?Category $category = null,
        ?string $description = null,
        ?string $reference = null,
        ?User $editor = null,
    ): Transaction {
        if (! $transaction->isPosted()) {
            throw new InvalidArgumentException('A voided transaction cannot be edited.');
        }

        if ($amount < 1) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        return DB::transaction(function () use ($transaction, $wallet, $amount, $date, $category, $description, $reference) {
            $this->applyBalance->handle($transaction, direction: -1);

            $transaction->update([
                'wallet_id' => $wallet->id,
                'category_id' => $category?->id,
                'currency' => $wallet->currency,
                'amount' => $amount,
                'date' => $date->toDateString(),
                'description' => $description,
                'reference' => $reference,
            ]);

            $this->applyBalance->handle($transaction->refresh());

            return $transaction;
        });
    }
}
