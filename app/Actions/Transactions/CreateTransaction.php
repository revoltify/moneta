<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateTransaction
{
    public function __construct(private ApplyTransactionBalance $applyBalance) {}

    public function handle(
        Company $company,
        TransactionType $type,
        Wallet $wallet,
        int $amount,
        CarbonInterface $date,
        ?Category $category = null,
        ?string $description = null,
        ?string $reference = null,
        ?User $creator = null,
    ): Transaction {
        $this->validate($company, $type, $wallet, $amount, $category);

        return DB::transaction(function () use ($company, $type, $wallet, $amount, $date, $category, $description, $reference, $creator) {
            $transaction = Transaction::create([
                'company_id' => $company->id,
                'type' => $type,
                'wallet_id' => $wallet->id,
                'category_id' => $category?->id,
                'currency' => $wallet->currency,
                'amount' => $amount,
                'date' => $date->toDateString(),
                'description' => $description,
                'reference' => $reference,
                'status' => TransactionStatus::Posted,
                'created_by' => $creator?->id,
            ]);

            $this->applyBalance->handle($transaction);

            return $transaction;
        });
    }

    private function validate(Company $company, TransactionType $type, Wallet $wallet, int $amount, ?Category $category): void
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        if ($type === TransactionType::Transfer) {
            throw new InvalidArgumentException('Use CreateTransfer for transfers.');
        }

        if ($wallet->company_id !== $company->id) {
            throw new InvalidArgumentException('Wallet belongs to another company.');
        }

        if ($type->requiresCategory()) {
            if ($category === null) {
                throw new InvalidArgumentException('This transaction type requires a category.');
            }

            if ($category->company_id !== $company->id) {
                throw new InvalidArgumentException('Category belongs to another company.');
            }

            if ($category->kind !== $type->categoryKind()) {
                throw new InvalidArgumentException('Category kind does not match the transaction type.');
            }
        } elseif ($category !== null) {
            throw new InvalidArgumentException('This transaction type does not take a category.');
        }
    }
}
