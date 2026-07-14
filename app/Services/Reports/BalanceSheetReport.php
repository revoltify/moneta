<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Wallet;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class BalanceSheetReport
{
    /**
     * @return array{
     *     assets: list<array{id: int, name: string, amount: int}>,
     *     liabilities: list<array{id: int, name: string, amount: int}>,
     *     equity: list<array{id: int, name: string, amount: int}>,
     *     retainedEarnings: int,
     *     totalAssets: int,
     *     totalLiabilities: int,
     *     totalEquity: int
     * }
     */
    public function generate(Company $company, CarbonInterface $asOf): array
    {
        $wallets = Wallet::query()
            ->forCompany($company)
            ->where('currency', $company->currency)
            ->orderBy('name')
            ->get();

        $assets = [];
        $openingTotal = 0;

        foreach ($wallets as $wallet) {
            $balance = $wallet->opening_balance + $this->walletDelta($company, $wallet->id, $asOf);
            $openingTotal += $wallet->opening_balance;

            if ($balance !== 0) {
                $assets[] = ['id' => $wallet->id, 'name' => $wallet->name, 'amount' => $balance];
            }
        }

        $sums = $this->typeSums($company, $asOf);

        $capital = $sums[TransactionType::CapitalInvestment->value] ?? 0;
        $withdrawals = $sums[TransactionType::CapitalWithdrawal->value] ?? 0;
        $retainedEarnings = ($sums[TransactionType::Income->value] ?? 0)
            - ($sums[TransactionType::Expense->value] ?? 0);

        $equity = array_values(array_filter([
            ['id' => 1, 'name' => 'Capital', 'amount' => $capital],
            ['id' => 2, 'name' => 'Withdrawals', 'amount' => -$withdrawals],
            ['id' => 3, 'name' => 'Opening Balances', 'amount' => $openingTotal],
        ], fn (array $row) => $row['amount'] !== 0));

        $totalAssets = array_sum(array_column($assets, 'amount'));
        $totalEquity = $capital - $withdrawals + $openingTotal + $retainedEarnings;

        return [
            'assets' => $assets,
            'liabilities' => [],
            'equity' => $equity,
            'retainedEarnings' => $retainedEarnings,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => 0,
            'totalEquity' => $totalEquity,
        ];
    }

    private function walletDelta(Company $company, int $walletId, CarbonInterface $asOf): int
    {
        $base = fn () => DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('status', TransactionStatus::Posted->value)
            ->whereDate('date', '<=', $asOf->toDateString());

        $incoming = (int) $base()
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner
                    ->where('wallet_id', $walletId)
                    ->whereIn('type', [TransactionType::Income->value, TransactionType::CapitalInvestment->value]))
                ->orWhere(fn ($inner) => $inner
                    ->where('counter_wallet_id', $walletId)
                    ->where('type', TransactionType::Transfer->value)))
            ->sum('amount');

        $outgoing = (int) $base()
            ->where('wallet_id', $walletId)
            ->whereIn('type', [
                TransactionType::Expense->value,
                TransactionType::CapitalWithdrawal->value,
                TransactionType::Transfer->value,
            ])
            ->sum('amount');

        return $incoming - $outgoing;
    }

    /**
     * @return array<string, int>
     */
    private function typeSums(Company $company, CarbonInterface $asOf): array
    {
        return DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('currency', $company->currency)
            ->whereDate('date', '<=', $asOf->toDateString())
            ->groupBy('type')
            ->selectRaw('type, COALESCE(SUM(amount), 0) as total')
            ->pluck('total', 'type')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
