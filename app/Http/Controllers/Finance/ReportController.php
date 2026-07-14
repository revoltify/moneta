<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reports\BalanceSheetReport;
use App\Services\Reports\CashFlowReport;
use App\Services\Reports\IncomeStatementReport;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function monthlySummary(Request $request, Company $current_company, IncomeStatementReport $report): Response
    {
        $months = collect(range(11, 0))->map(function (int $monthsAgo) use ($current_company, $report) {
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
            ? Carbon::parse($request->string('as_of')->toString())
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
            ? Carbon::parse($request->string('from')->toString())
            : now($company->timezone)->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->string('to')->toString())
            : now($company->timezone)->endOfMonth();

        return [$from, $to];
    }
}
