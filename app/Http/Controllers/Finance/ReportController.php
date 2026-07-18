<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reports\BalanceSheetReport;
use App\Services\Reports\CashFlowReport;
use App\Services\Reports\IncomeStatementReport;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ReportController extends Controller
{
    public function index(Request $request, Company $current_company): Response
    {
        return Inertia::render('reports/index');
    }

    public function categoryBreakdown(Request $request, Company $current_company, IncomeStatementReport $report): Response
    {
        [$from, $to] = $this->period($request, $current_company);

        $kind = $request->string('kind')->toString() === 'income' ? 'income' : 'expense';
        $statement = $report->generate($current_company, $from, $to);

        return Inertia::render('reports/category-breakdown', [
            'rows' => $statement[$kind],
            'total' => $kind === 'income' ? $statement['totalIncome'] : $statement['totalExpense'],
            'kind' => $kind,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function dailySummary(Request $request, Company $current_company): Response
    {
        $today = today($current_company->timezone);
        $from = $today->copy()->subDays(29);

        $sums = DB::table('transactions')
            ->where('company_id', $current_company->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('currency', $current_company->currency)
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $today->toDateString())
            ->groupBy('date', 'type')
            ->selectRaw('date, type, COALESCE(SUM(amount), 0) as total')
            ->get()
            ->groupBy(fn (object $row): string => Date::parse($row->date)->toDateString());

        $days = collect(range(29, 0))->map(function (int $daysAgo) use ($today, $sums): array {
            $day = $today->copy()->subDays($daysAgo);
            $rows = $sums->get($day->toDateString(), collect());
            $income = (int) ($rows->firstWhere('type', TransactionType::Income->value)->total ?? 0);
            $expense = (int) ($rows->firstWhere('type', TransactionType::Expense->value)->total ?? 0);

            return [
                'date' => $day->toDateString(),
                'day' => $day->format('d M'),
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
            ];
        });

        return Inertia::render('reports/daily-summary', [
            'days' => $days,
            'totals' => [
                'income' => $days->sum('income'),
                'expense' => $days->sum('expense'),
                'profit' => $days->sum('profit'),
            ],
        ]);
    }

    public function monthlySummary(Request $request, Company $current_company, IncomeStatementReport $report): Response
    {
        $months = collect(range(11, 0))->map(function (int $monthsAgo) use ($current_company, $report): array {
            $month = now($current_company->timezone)->subMonthsNoOverflow($monthsAgo);
            $statement = $report->generate($current_company, $month->copy()->startOfMonth(), $month->copy()->endOfMonth());

            return [
                'month' => $month->format('M Y'),
                'income' => $statement['totalIncome'],
                'expense' => $statement['totalExpense'],
                'profit' => $statement['netProfit'],
            ];
        });

        return Inertia::render('reports/monthly-summary', [
            'months' => $months,
            'totals' => [
                'income' => $months->sum('income'),
                'expense' => $months->sum('expense'),
                'profit' => $months->sum('profit'),
            ],
        ]);
    }

    public function incomeStatement(Request $request, Company $current_company, IncomeStatementReport $report): Response
    {
        [$from, $to] = $this->period($request, $current_company);

        return Inertia::render('reports/income-statement', [
            'report' => $report->generate($current_company, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function balanceSheet(Request $request, Company $current_company, BalanceSheetReport $report): Response
    {
        $asOf = $request->filled('as_of')
            ? Date::parse($request->string('as_of')->toString())
            : now($current_company->timezone);

        return Inertia::render('reports/balance-sheet', [
            'report' => $report->generate($current_company, $asOf),
            'asOf' => $asOf->toDateString(),
        ]);
    }

    public function cashFlow(Request $request, Company $current_company, CashFlowReport $report): Response
    {
        [$from, $to] = $this->period($request, $current_company);

        return Inertia::render('reports/cash-flow', [
            'report' => $report->generate($current_company, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    private function period(Request $request, Company $company): array
    {
        $from = $request->filled('from')
            ? Date::parse($request->string('from')->toString())
            : now($company->timezone)->startOfMonth();

        $to = $request->filled('to')
            ? Date::parse($request->string('to')->toString())
            : now($company->timezone)->endOfMonth();

        return [$from, $to];
    }
}
