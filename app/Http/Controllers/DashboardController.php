<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Budgets\EvaluateBudgetAlert;
use App\Models\Budget;
use App\Models\Company;
use App\Models\Transaction;
use App\Services\Reports\CashFlowReport;
use App\Services\Reports\IncomeStatementReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, Company $current_company, IncomeStatementReport $incomeStatement, CashFlowReport $cashFlow, EvaluateBudgetAlert $budgetEvaluator): Response
    {
        $timezone = $current_company->timezone;
        $from = $request->filled('from')
            ? Carbon::parse($request->string('from')->toString())
            : now($timezone)->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to')->toString())
            : now($timezone)->endOfMonth();

        $periodStatement = $incomeStatement->generate($current_company, $from, $to);
        $periodCashFlow = $cashFlow->generate($current_company, $from, $to);
        $totalCash = (int) $current_company->wallets()->active()->where('currency', $current_company->currency)->sum('cached_balance');

        $trend = collect(range(5, 0))->map(function (int $monthsAgo) use ($current_company, $incomeStatement, $timezone) {
            $month = now($timezone)->subMonthsNoOverflow($monthsAgo);
            $report = $incomeStatement->generate($current_company, $month->copy()->startOfMonth(), $month->copy()->endOfMonth());

            return [
                'month' => $month->format('M Y'),
                'income' => $report['totalIncome'],
                'expense' => $report['totalExpense'],
                'profit' => $report['netProfit'],
            ];
        });

        $budgets = Budget::query()
            ->forCompany($current_company)
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->map(fn (Budget $budget) => [
                'id' => $budget->id,
                'categoryName' => $budget->category->name,
                'categoryColor' => $budget->category->color,
                'amount' => $budget->amount,
                'period' => $budget->period,
                'spent' => $budgetEvaluator->periodSpend($current_company, $budget->category, now($timezone), $budget->period),
            ])
            ->sortByDesc(fn (array $budget) => $budget['amount'] > 0 ? $budget['spent'] / $budget['amount'] : 0)
            ->take(4)
            ->values();

        return Inertia::render('dashboard', [
            'totalCash' => $totalCash,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'periodIncome' => $periodStatement['totalIncome'],
            'periodExpense' => $periodStatement['totalExpense'],
            'periodProfit' => $periodStatement['netProfit'],
            'periodCashFlow' => $periodCashFlow['netChange'],
            'trend' => $trend,
            'budgets' => $budgets,
            'topExpenseCategories' => collect($periodStatement['expense'])->take(5)->values(),
            'recentTransactions' => $current_company->transactions()
                ->posted()
                ->with(['wallet', 'category'])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->map(fn (Transaction $transaction) => [
                    'id' => $transaction->id,
                    'type' => $transaction->type->value,
                    'typeLabel' => $transaction->type->label(),
                    'description' => $transaction->description,
                    'categoryName' => $transaction->category?->name,
                    'walletName' => $transaction->wallet->name,
                    'amount' => $transaction->amount,
                    'signedAmount' => $transaction->signedAmount(),
                    'date' => $transaction->date->toDateString(),
                ]),
        ]);
    }
}
