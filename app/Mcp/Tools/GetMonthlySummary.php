<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Services\Reports\IncomeStatementReport;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class GetMonthlySummary extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Income, expense and profit for each of the last 12 months. Amounts are integers in minor units (1/100 of the currency).';

    public function __construct(private IncomeStatementReport $report) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $company = $this->company($request);

        $months = collect(range(11, 0))->map(function (int $monthsAgo) use ($company) {
            $month = now($company->timezone)->subMonthsNoOverflow($monthsAgo);
            $statement = $this->report->generate($company, $month->copy()->startOfMonth(), $month->copy()->endOfMonth());

            return [
                'month' => $month->format('M Y'),
                'income' => $statement['totalIncome'],
                'expense' => $statement['totalExpense'],
                'profit' => $statement['netProfit'],
            ];
        });

        return Response::json(['company' => $company->slug, 'months' => $months]);
    }
}
