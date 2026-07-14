<?php

declare(strict_types=1);

namespace App\Actions\Wallets;

use App\Actions\Categories\CreateCategory;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

final class ReconcileWallet
{
    public const string ADJUSTMENT_CATEGORY = 'Balance Adjustment';

    public function __construct(
        private CreateTransaction $createTransaction,
        private CreateCategory $createCategory,
    ) {}

    public function handle(Wallet $wallet, int $actualBalance, ?User $user = null): ?Transaction
    {
        $wallet->refresh();

        $difference = $actualBalance - $wallet->cached_balance;

        if ($difference === 0) {
            return null;
        }

        return DB::transaction(function () use ($wallet, $difference, $user) {
            $kind = $difference > 0 ? CategoryKind::Income : CategoryKind::Expense;
            $category = $this->adjustmentCategory($wallet, $kind);

            return $this->createTransaction->handle(
                $wallet->company,
                $difference > 0 ? TransactionType::Income : TransactionType::Expense,
                $wallet,
                abs($difference),
                now($wallet->company->timezone),
                $category,
                "Balance reconciliation for {$wallet->name}",
                creator: $user,
            );
        });
    }

    private function adjustmentCategory(Wallet $wallet, CategoryKind $kind): Category
    {
        $existing = Category::query()
            ->forCompany($wallet->company)
            ->where('kind', $kind)
            ->where('name', self::ADJUSTMENT_CATEGORY)
            ->first();

        return $existing ?? $this->createCategory->handle(
            $wallet->company,
            self::ADJUSTMENT_CATEGORY,
            $kind,
            icon: 'scale',
            color: '#64748b',
        );
    }
}
