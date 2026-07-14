<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Company;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;

final class TransactionFilters
{
    /**
     * @param  array{type?: string, wallet_id?: int|string, category_id?: int|string, from?: string, to?: string, search?: string, status?: string}  $filters
     * @return Builder<Transaction>
     */
    public static function apply(Company $company, array $filters): Builder
    {
        $type = (string) ($filters['type'] ?? '');
        $walletId = (string) ($filters['wallet_id'] ?? '');
        $categoryId = (string) ($filters['category_id'] ?? '');
        $from = (string) ($filters['from'] ?? '');
        $to = (string) ($filters['to'] ?? '');
        $search = (string) ($filters['search'] ?? '');
        $status = (string) ($filters['status'] ?? '');

        return $company->transactions()->getQuery()
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($walletId !== '', fn ($query) => $query->where(
                fn ($inner) => $inner->where('wallet_id', $walletId)->orWhere('counter_wallet_id', $walletId),
            ))
            ->when($categoryId !== '', fn ($query) => $query->where('category_id', $categoryId))
            ->when($from !== '', fn ($query) => $query->whereDate('date', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('date', '<=', $to))
            ->when($search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner->where('description', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%"),
            ))
            ->when(
                $status === 'voided',
                fn ($query) => $query->where('status', 'voided'),
                fn ($query) => $query->posted(),
            );
    }
}
