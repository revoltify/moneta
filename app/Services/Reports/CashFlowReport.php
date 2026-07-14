<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CashFlowReport
{
    /**
     * @return array{
     *     operatingInflow: int,
     *     operatingOutflow: int,
     *     financingInflow: int,
     *     financingOutflow: int,
     *     netOperating: int,
     *     netFinancing: int,
     *     netChange: int,
     *     openingBalance: int,
     *     closingBalance: int
     * }
     */
    public function generate(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        $sums = $this->typeSums($company, fn (Builder $query) => $query
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString()));

        $operatingInflow = $sums[TransactionType::Income->value] ?? 0;
        $operatingOutflow = $sums[TransactionType::Expense->value] ?? 0;
        $financingInflow = $sums[TransactionType::CapitalInvestment->value] ?? 0;
        $financingOutflow = $sums[TransactionType::CapitalWithdrawal->value] ?? 0;

        $walletOpenings = (int) DB::table('wallets')
            ->where('company_id', $company->id)
            ->where('currency', $company->currency)
            ->sum('opening_balance');

        $beforeSums = $this->typeSums($company, fn (Builder $query) => $query
            ->whereDate('date', '<', $from->toDateString()));

        $openingBalance = $walletOpenings
            + ($beforeSums[TransactionType::Income->value] ?? 0)
            + ($beforeSums[TransactionType::CapitalInvestment->value] ?? 0)
            - ($beforeSums[TransactionType::Expense->value] ?? 0)
            - ($beforeSums[TransactionType::CapitalWithdrawal->value] ?? 0);

        $netOperating = $operatingInflow - $operatingOutflow;
        $netFinancing = $financingInflow - $financingOutflow;

        return [
            'operatingInflow' => $operatingInflow,
            'operatingOutflow' => $operatingOutflow,
            'financingInflow' => $financingInflow,
            'financingOutflow' => $financingOutflow,
            'netOperating' => $netOperating,
            'netFinancing' => $netFinancing,
            'netChange' => $netOperating + $netFinancing,
            'openingBalance' => $openingBalance,
            'closingBalance' => $openingBalance + $netOperating + $netFinancing,
        ];
    }

    /**
     * @param  callable(Builder): Builder  $constrain
     * @return array<string, int>
     */
    private function typeSums(Company $company, callable $constrain): array
    {
        $query = DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('currency', $company->currency);

        return $constrain($query)
            ->groupBy('type')
            ->selectRaw('type, COALESCE(SUM(amount), 0) as total')
            ->pluck('total', 'type')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
