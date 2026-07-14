<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Services\Reports\CashFlowReport;
use App\Services\Reports\IncomeStatementReport;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class GetDashboardSummary extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Key numbers for a period: total cash, income, expense, profit and net cash flow. Defaults to the current month. Amounts are integers in minor units (1/100 of the currency).';

    public function __construct(
        private IncomeStatementReport $incomeStatement,
        private CashFlowReport $cashFlow,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'from' => $schema->string()->description('Start date YYYY-MM-DD. Defaults to start of the current month.'),
            'to' => $schema->string()->description('End date YYYY-MM-DD. Defaults to end of the current month.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);

        $company = $this->company($request);
        [$from, $to] = $this->period($request, $company->timezone);

        $statement = $this->incomeStatement->generate($company, $from, $to);
        $cashFlow = $this->cashFlow->generate($company, $from, $to);
        $totalCash = (int) $company->wallets()->active()->where('currency', $company->currency)->sum('cached_balance');

        return Response::json([
            'company' => $company->slug,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totalCash' => $totalCash,
            'totalCashFormatted' => Money::format($totalCash, $company->currency),
            'income' => $statement['totalIncome'],
            'expense' => $statement['totalExpense'],
            'profit' => $statement['netProfit'],
            'cashFlow' => $cashFlow['netChange'],
        ]);
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    private function period(Request $request, string $timezone): array
    {
        $from = $request->get('from') !== null
            ? Carbon::parse((string) $request->get('from'))
            : now($timezone)->startOfMonth();

        $to = $request->get('to') !== null
            ? Carbon::parse((string) $request->get('to'))
            : now($timezone)->endOfMonth();

        return [$from, $to];
    }
}
